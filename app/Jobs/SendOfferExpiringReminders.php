<?php

namespace App\Jobs;

use App\Modules\Offers\Models\Offer;
use App\Mail\OfferExpiringMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendOfferExpiringReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Find offers expiring in next 24 hours
        $expiringOffers = Offer::where('status', 'issued')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addHours(24))
            ->where('expires_at', '>', now())
            ->with('invoice.supplier')
            ->get();

        foreach ($expiringOffers as $offer) {
            try {
                $supplier = $offer->invoice->supplier;
                if ($supplier && $supplier->contact_email) {
                    Mail::to($supplier->contact_email)
                        ->send(new OfferExpiringMail($offer));

                    Log::info('Offer expiring reminder sent', [
                        'offer_id' => $offer->id,
                        'email' => $supplier->contact_email,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send offer expiring reminder', [
                    'offer_id' => $offer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}



