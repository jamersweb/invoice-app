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
        // Find repayments due in next 7 days
        $dueRepayments = ExpectedRepayment::whereIn('status', ['open', 'partial'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
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
