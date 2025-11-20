<?php

namespace App\Modules\Invoices\Listeners;

use App\Modules\Invoices\Events\InvoiceSubmitted;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyAdminOnInvoiceSubmission
{
    /**
     * Handle the event.
     */
    public function handle(InvoiceSubmitted $event): void
    {
        $invoice = $event->invoice;
        $supplier = $invoice->supplier;

        // Create notification for admins
        $admins = User::role('Admin')->get();
        
        foreach ($admins as $admin) {
            // Create in-app notification
            Notification::create([
                'type' => 'invoice_submitted',
                'title' => 'New Invoice Submitted',
                'message' => "Invoice #{$invoice->invoice_number} for {$invoice->amount} {$invoice->currency} has been submitted by {$supplier->company_name}.",
                'recipient_type' => 'user',
                'recipient_id' => $admin->id,
                'status' => 'pending',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'supplier_id' => $supplier->id,
                ],
            ]);

            // Send email notification
            try {
                Mail::raw(
                    "A new invoice has been submitted:\n\n" .
                    "Invoice Number: {$invoice->invoice_number}\n" .
                    "Supplier: {$supplier->company_name}\n" .
                    "Amount: {$invoice->amount} {$invoice->currency}\n" .
                    "Due Date: {$invoice->due_date}\n\n" .
                    "Please review the invoice at: " . route('admin.invoice-review'),
                    function ($m) use ($admin) {
                        $m->to($admin->email)
                          ->subject('New Invoice Submitted - Requires Review');
                    }
                );
            } catch (\Exception $e) {
                Log::error('Failed to send invoice submission email', [
                    'admin_id' => $admin->id,
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

