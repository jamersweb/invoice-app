<?php

namespace App\Mail;

use App\Modules\Repayments\Models\ExpectedRepayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RepaymentDueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ExpectedRepayment $repayment)
    {
    }

    public function build()
    {
        $locale = app()->getLocale();
        $subject = $locale === 'ar'
            ? 'تنبيه: تاريخ استحقاق السداد قريب - ' . $this->repayment->invoice->invoice_number
            : 'Reminder: Repayment Due Soon - ' . $this->repayment->invoice->invoice_number;

        return $this->subject($subject)
            ->view('emails.repayment-due', [
                'repayment' => $this->repayment,
                'locale' => $locale,
            ]);
    }
}



