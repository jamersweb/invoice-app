<?php

namespace App\Services;

class MockKycProvider implements KycProviderInterface
{
    public function verify(array $payload): array
    {
        $lastDigit = (int) substr(preg_replace('/\D/','', (string)($payload['id_number'] ?? '0')), -1) % 3;
        $status = ['verified','failed','review'][$lastDigit] ?? 'review';
        return [
            'status' => $status,
            'reference' => 'MOCK-' . strtoupper(bin2hex(random_bytes(4))),
            'details' => ['score' => 0.8, 'provider' => 'mock']
        ];
    }
}


