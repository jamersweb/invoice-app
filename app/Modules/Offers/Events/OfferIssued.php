<?php

namespace App\Modules\Offers\Events;

use App\Modules\Offers\Models\Offer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OfferIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Offer $offer;

    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }
}


