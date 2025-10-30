<?php

namespace App\Modules\Offers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Offers\Models\Offer;
use App\Modules\Offers\Resources\OfferResource;
use App\Modules\Offers\Services\PricingEngineService;
use App\Modules\Offers\Services\OfferAcceptanceService;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Http\Request;

class OffersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:issue_offers')->only('issue');
        $this->middleware('permission:accept_offers')->only('accept');
    }

    public function issue(Request $request, PricingEngineService $pricing)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'supplier_grade' => ['nullable', 'string'],
            'buyer_grade' => ['nullable', 'string'],
            'historical_default_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $this->authorize('update', $invoice);

        // Block issuing if supplier has 3+ declined offers (any invoice)
        $declineCount = \App\Modules\Offers\Models\Offer::whereHas('invoice', function ($q) use ($invoice) {
            $q->where('supplier_id', $invoice->supplier_id);
        })->where('status', 'declined')->count();
        if ($declineCount >= 3) {
            abort(422, 'Supplier has exceeded decline limit.');
        }

        // Limit re-offers per invoice to max 3
        $offersForInvoice = \App\Modules\Offers\Models\Offer::where('invoice_id', $invoice->id)->count();
        if ($offersForInvoice >= 3) {
            abort(422, 'Maximum re-offers reached for this invoice.');
        }

        $supplier = \App\Models\Supplier::find($invoice->supplier_id);
        $isVip = false;
        if ($supplier) {
            $isVip = (bool) data_get($supplier->kyc_data, 'vip', false) || strcasecmp((string) ($supplier->grade ?? ''), 'VIP') === 0;
        }

        $snapshot = $pricing->price((float) $invoice->amount, $invoice->due_date, $data['supplier_grade'] ?? 'B', $data['buyer_grade'] ?? 'B', (float) ($data['historical_default_rate'] ?? 0));
        // VIP rate adjustment: -0.5%
        if ($isVip) {
            $snapshot['discount_rate'] = max(0, (float) $snapshot['discount_rate'] - 0.005);
            // Recompute dependent values if present in snapshot
            if (isset($snapshot['discount_amount'])) {
                $snapshot['discount_amount'] = round(((float)$snapshot['discount_rate']) * (float)$invoice->amount, 2);
            }
            if (isset($snapshot['net_amount'])) {
                $snapshot['net_amount'] = round((float)$invoice->amount - (float)($snapshot['discount_amount'] ?? 0) - (float)($snapshot['admin_fee'] ?? 0), 2);
            }
        }

        $offer = Offer::create([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'tenor_days' => $snapshot['tenor_days'],
            'discount_rate' => $snapshot['discount_rate'],
            'discount_amount' => $snapshot['discount_amount'],
            'admin_fee' => $snapshot['admin_fee'],
            'net_amount' => $snapshot['net_amount'],
            'pricing_snapshot' => $snapshot,
            'status' => 'issued',
            'issued_at' => now(),
            'expires_at' => now()->addHours($isVip ? 72 : 48),
        ]);

        return (new OfferResource($offer))->response()->setStatusCode(201);
    }

    public function accept(Request $request, OfferAcceptanceService $service)
    {
        $data = $request->validate([
            'offer_id' => ['required', 'integer', 'exists:offers,id'],
        ]);
        $offer = Offer::findOrFail($data['offer_id']);
        $this->authorize('update', $offer->invoice()->first());
        $funding = $service->accept($offer, optional($request->user())->id);
        return response()->json([
            'funding_id' => $funding->id,
            'invoice_id' => $funding->invoice_id,
            'offer_id' => $funding->offer_id,
            'amount' => (float) $funding->amount,
        ], 201);
    }

    public function decline(Request $request)
    {
        $data = $request->validate([
            'offer_id' => ['required', 'integer', 'exists:offers,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $offer = Offer::findOrFail($data['offer_id']);
        $this->authorize('update', $offer->invoice()->first());

        if ($offer->status !== 'issued') {
            abort(422, 'Offer cannot be declined.');
        }

        $offer->status = 'declined';
        $offer->responded_at = now();
        $offer->save();

        $invoice = $offer->invoice()->first();
        $oldStatus = $invoice->status;
        // Return invoice to 'approved'
        $invoice->status = 'approved';
        $invoice->save();

        \App\Models\AuditEvent::create([
            'actor_type' => 'user',
            'actor_id' => optional($request->user())->id,
            'entity_type' => Offer::class,
            'entity_id' => $offer->id,
            'action' => 'offer_declined',
            'diff_json' => ['new' => ['status' => 'declined'], 'reason' => $data['reason'] ?? null],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);

        \App\Models\AuditEvent::create([
            'actor_type' => 'user',
            'actor_id' => optional($request->user())->id,
            'entity_type' => get_class($invoice),
            'entity_id' => $invoice->id,
            'action' => 'invoice_status_changed',
            'diff_json' => ['old' => ['status' => $oldStatus], 'new' => ['status' => $invoice->status]],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);

        return response()->json(['ok' => true]);
    }
}


