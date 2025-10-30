<?php

namespace App\Modules\Offers\Services;

use App\Models\AuditEvent;
use App\Modules\Offers\Models\Offer;
use App\Modules\Funding\Models\Funding;
use App\Modules\Repayments\Models\ExpectedRepayment;
use Illuminate\Support\Facades\DB;

class OfferAcceptanceService
{
    public function accept(Offer $offer, int $actorId = null): Funding
    {
        return DB::transaction(function () use ($offer, $actorId) {
            if ($offer->status !== 'issued' || ($offer->expires_at && $offer->expires_at->isPast())) {
                abort(422, 'Offer is not valid for acceptance.');
            }

            // Enforce KYB approval gate
            $invoice = $offer->invoice()->first();
            $supplier = \App\Models\Supplier::find($invoice?->supplier_id);
            if (!$supplier || $supplier->kyb_status !== 'approved') {
                abort(422, 'Supplier KYB is not approved. Funding is blocked.');
            }

            $offer->status = 'accepted';
            $offer->responded_at = now();
            $offer->save();

            $oldStatus = $invoice->status;
            // Move to pending_funding; actual funding execution will set funded status
            $invoice->status = 'pending_funding';
            $invoice->save();

            // Create queued funding record (funded_at null indicates queued)
            $funding = Funding::create([
                'invoice_id' => $invoice->id,
                'offer_id' => $offer->id,
                'amount' => $offer->net_amount,
                'funded_at' => null,
                'created_by' => $actorId,
            ]);

            // Expected repayment will be created upon funding execution step

            AuditEvent::create([
                'actor_type' => 'user',
                'actor_id' => $actorId,
                'entity_type' => Offer::class,
                'entity_id' => $offer->id,
                'action' => 'offer_accepted',
                'diff_json' => ['new' => ['status' => 'accepted']],
                'ip' => request()->ip(),
                'ua' => request()->userAgent(),
            ]);

            AuditEvent::create([
                'actor_type' => 'user',
                'actor_id' => $actorId,
                'entity_type' => get_class($invoice),
                'entity_id' => $invoice->id,
                'action' => 'invoice_status_changed',
                'diff_json' => ['old' => ['status' => $oldStatus], 'new' => ['status' => $invoice->status]],
                'ip' => request()->ip(),
                'ua' => request()->userAgent(),
            ]);

            return $funding;
        });
    }
}


