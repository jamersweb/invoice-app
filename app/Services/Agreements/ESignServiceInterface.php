<?php

namespace App\Services\Agreements;

use App\Models\Agreement;

interface ESignServiceInterface
{
    /** Create envelope and return provider envelope id */
    public function createEnvelope(Agreement $agreement, string $pdfPath, array $recipient): string;

    /** Send envelope for signature */
    public function sendEnvelope(string $envelopeId): void;

    /** Handle provider webhook payload */
    public function handleWebhook(array $payload): void;
}


