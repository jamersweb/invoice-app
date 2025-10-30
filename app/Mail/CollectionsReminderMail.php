<?php

namespace App\Mail;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CollectionsReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function build()
    {
        $locale = app()->getLocale();
        $view = $locale === 'ar' ? 'emails.collections_reminder_ar' : 'emails.collections_reminder';

        return $this->subject(__('messages.collections_reminder_subject'))
            ->view($view)
            ->with([
                'invoice' => $this->invoice,
                'amount' => number_format($this->invoice->amount, 2),
                'due_date' => $this->invoice->due_date?->format('Y-m-d'),
                'days_overdue' => $this->invoice->due_date ? now()->diffInDays($this->invoice->due_date, false) : 0,
            ]);
    }
}
