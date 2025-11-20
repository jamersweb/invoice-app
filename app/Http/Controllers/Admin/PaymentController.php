<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Record manual payment for an invoice
     */
    public function recordPayment(Request $request, Invoice $invoice)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin']), 403);

        $validated = $request->validate([
            'funded_amount' => ['required', 'numeric', 'min:0.01'],
            'funded_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $oldStatus = $invoice->status;
        $oldFundedAmount = $invoice->funded_amount;

        $invoice->update([
            'funded_amount' => $validated['funded_amount'],
            'funded_date' => $validated['funded_date'],
            'funded_by' => auth()->id(),
            'status' => 'funded',
        ]);

        // Create funding log
        \App\Modules\Funding\Models\Funding::create([
            'invoice_id' => $invoice->id,
            'offer_id' => null, // Manual payment, no offer
            'amount' => $validated['funded_amount'],
            'status' => 'executed',
            'funded_at' => $validated['funded_date'],
        ]);

        // Send notification to supplier
        $supplier = $invoice->supplier;
        if ($supplier && $supplier->contact_email) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "Your invoice #{$invoice->invoice_number} has been funded.\n\n" .
                    "Amount: {$validated['funded_amount']} {$invoice->currency}\n" .
                    "Date: {$validated['funded_date']}\n\n" .
                    "Please check your dashboard for repayment schedule.",
                    function ($m) use ($supplier) {
                        $m->to($supplier->contact_email)
                          ->subject('Invoice Payment Received');
                    }
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send payment notification', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'payment_recorded',
            'diff_json' => [
                'old_values' => [
                    'status' => $oldStatus,
                    'funded_amount' => $oldFundedAmount,
                ],
                'new_values' => [
                    'status' => 'funded',
                    'funded_amount' => $validated['funded_amount'],
                    'funded_date' => $validated['funded_date'],
                ],
            ],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
        ]);
    }
}

