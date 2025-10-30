<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule recurring jobs
Schedule::job(new \App\Jobs\SendOfferExpiringReminders)->hourly();
Schedule::job(new \App\Jobs\SendRepaymentDueReminders)->daily();
Schedule::command('data:retention')->monthly();
