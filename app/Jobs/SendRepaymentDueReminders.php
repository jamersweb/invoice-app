<?php

namespace App\Jobs;

use App\Modules\Repayments\Models\ExpectedRepayment;
use App\Mail\RepaymentDueMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendRepaymentDueReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Get admin-configurable settings
        $daysBeforeDue = \App\Models\AppSetting::get('reminder_email_days_before_due', 7);
        
        // Find repayments due within the configured days
        $dueRepayments = ExpectedRepayment::whereIn('status', ['open', 'partial'])
            ->whereBetween('due_date', [now(), now()->addDays($daysBeforeDue)])
            ->with(['invoice.supplier'])
            ->get();

        foreach ($dueRepayments as $repayment) {
            try {
                $supplier = $repayment->invoice->supplier ?? null;
                if ($supplier && $supplier->contact_email) {
                    Mail::to($supplier->contact_email)
                        ->send(new RepaymentDueMail($repayment));

                    Log::info('Repayment due reminder sent', [
                        'repayment_id' => $repayment->id,
                        'email' => $supplier->contact_email,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send repayment due reminder', [
                    'repayment_id' => $repayment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
