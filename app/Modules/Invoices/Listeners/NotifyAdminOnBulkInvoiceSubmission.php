<?php

namespace App\Modules\Invoices\Listeners;

use App\Modules\Invoices\Events\BulkInvoicesSubmitted;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotifyAdminOnBulkInvoiceSubmission
{
    /**
     * Handle the event.
     */
    public function handle(BulkInvoicesSubmitted $event): void
    {
        try {
            $admins = User::role('Admin')->get();
            $count = count($event->invoices);
            $supplierName = $event->supplier->company_name ?? $event->supplier->legal_name ?? 'Unknown Supplier';

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(
                    new \App\Mail\BulkInvoicesSubmittedMail($event->invoices, $event->supplier, $count)
                );
            }

            Log::info('Bulk invoice submission notification sent', [
                'supplier_id' => $event->supplier->id,
                'invoice_count' => $count,
                'admin_count' => $admins->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send bulk invoice submission notification', [
                'error' => $e->getMessage(),
                'supplier_id' => $event->supplier->id,
            ]);
        }
    }
}

