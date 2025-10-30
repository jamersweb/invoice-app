<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplierWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $supplierName)
    {
    }

    public function build()
    {
        $view = app()->getLocale() === 'ar' ? 'emails.supplier_welcome_ar' : 'emails.supplier_welcome';
        return $this->subject(__('messages.welcome_subject'))
            ->view($view)
            ->with(['name' => $this->supplierName]);
    }
}


