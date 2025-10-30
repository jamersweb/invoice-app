<?php

namespace App\Modules\Offers\Jobs;

use App\Modules\Offers\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpireOffers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        Offer::query()
            ->where('status', 'issued')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}


