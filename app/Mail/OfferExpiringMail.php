<?php

namespace App\Mail;

use App\Modules\Offers\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OfferExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Offer $offer)
    {
    }

    public function build()
    {
        $locale = app()->getLocale();
        $subject = $locale === 'ar' 
            ? 'عرضك على وشك الانتهاء - ' . $this->offer->invoice->invoice_number
            : 'Your Offer is Expiring - ' . $this->offer->invoice->invoice_number;

        return $this->subject($subject)
            ->view('emails.offer-expiring', [
                'offer' => $this->offer,
                'locale' => $locale,
            ]);
    }
}










