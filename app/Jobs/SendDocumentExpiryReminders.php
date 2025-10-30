<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDocumentExpiryReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $soon = now()->addDays(14);
        $docs = Document::query()
            ->whereNotNull('expiry_at')
            ->where('expiry_at', '<=', $soon)
            ->where('status', 'approved')
            ->get();

        foreach ($docs as $doc) {
            $locale = 'en';
            try {
                Mail::to('owner@example.com')->send(new \App\Mail\DocumentExpiryReminderMail($doc));
            } catch (\Throwable $e) {
                // swallow for now; production would log
            }
        }
    }
}


