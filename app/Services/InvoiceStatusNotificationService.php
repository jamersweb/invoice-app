<?php

namespace App\Services;

use App\Modules\Invoices\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class InvoiceStatusNotificationService
{
    /**
     * Send notification to supplier when invoice status changes
     */
    public function notifyStatusChange(Invoice $invoice, string $oldStatus, string $newStatus): void
    {
        $supplier = $invoice->supplier;
        if (!$supplier || !$supplier->contact_email) {
            return;
        }

        try {
            $locale = app()->getLocale();
            
            // Map status changes to appropriate email templates
            $emailClass = match($newStatus) {
                'approved' => \App\Mail\InvoiceApprovedMail::class,
                'rejected' => \App\Mail\InvoiceRejectedMail::class,
                'funded' => \App\Mail\InvoiceFundedMail::class,
                'settled' => \App\Mail\InvoiceSettledMail::class,
                default => null,
            };

            if ($emailClass) {
                Mail::to($supplier->contact_email)->send(new $emailClass($invoice, $oldStatus, $newStatus));
                
                Log::info('Invoice status notification sent', [
                    'invoice_id' => $invoice->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'supplier_email' => $supplier->contact_email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send invoice status notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when repayment schedule is created
     */
    public function notifyRepaymentScheduleCreated(Invoice $invoice, int $parts): void
    {
        $supplier = $invoice->supplier;
        if (!$supplier || !$supplier->contact_email) {
            return;
        }

        try {
            Mail::to($supplier->contact_email)->send(
                new \App\Mail\RepaymentScheduleCreatedMail($invoice, $parts)
            );

            Log::info('Repayment schedule notification sent', [
                'invoice_id' => $invoice->id,
                'parts' => $parts,
                'supplier_email' => $supplier->contact_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send repayment schedule notification', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

