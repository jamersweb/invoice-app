<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use Illuminate\Support\Facades\Log;

class InternalESignService implements ESignServiceInterface
{
    public function createEnvelope(Agreement $agreement, string $pdfPath, array $recipient): string
    {
        // Stub: generate a pseudo envelope id
        return 'env_'.base_convert(crc32($agreement->id.$pdfPath), 10, 36);
    }

    public function sendEnvelope(string $envelopeId): void
    {
        // Stub: log send
        Log::info('InternalESignService sending envelope', ['envelopeId' => $envelopeId]);
    }

    public function handleWebhook(array $payload): void
    {
        // Stub: just log payload
        Log::info('InternalESignService webhook', $payload);
    }
}


