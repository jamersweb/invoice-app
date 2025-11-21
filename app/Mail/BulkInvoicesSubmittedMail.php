<?php

namespace App\Mail;

use App\Modules\Invoices\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BulkInvoicesSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $invoices,
        public Supplier $supplier,
        public int $count
    ) {}

    public function build()
    {
        $locale = app()->getLocale();
        $subject = $locale === 'ar'
            ? "إشعار: تم تقديم {$this->count} فواتير جديدة"
            : "Notification: {$this->count} New Invoices Submitted";

        return $this->subject($subject)
            ->view('emails.bulk-invoices-submitted', [
                'invoices' => $this->invoices,
                'supplier' => $this->supplier,
                'count' => $this->count,
                'locale' => $locale,
            ]);
    }
}

