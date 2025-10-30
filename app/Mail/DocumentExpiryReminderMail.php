<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Document $document)
    {
    }

    public function build()
    {
        $view = app()->getLocale() === 'ar' ? 'emails.document_expiry_ar' : 'emails.document_expiry';
        return $this->subject(__('messages.document_expiry_subject'))
            ->view($view)
            ->with(['document' => $this->document]);
    }
}


