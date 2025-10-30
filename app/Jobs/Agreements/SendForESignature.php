<?php

namespace App\Jobs\Agreements;

use App\Models\Agreement;
use App\Services\Agreements\ESignServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendForESignature implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Agreement $agreement, public array $recipient) {}

    public function handle(ESignServiceInterface $eSign): void
    {
        $pdfPath = storage_path('app/public/'.$this->agreement->signed_pdf);
        $envelopeId = $eSign->createEnvelope($this->agreement, $pdfPath, $this->recipient);
        $eSign->sendEnvelope($envelopeId);
        $this->agreement->update(['status' => 'sent']);
    }
}


