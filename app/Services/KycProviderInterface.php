<?php

namespace App\Services;

interface KycProviderInterface
{
    /**
     * @param array $payload e.g., ['name'=>..., 'id_number'=>..., 'document_url'=>...]
     * @return array ['status'=>'verified|failed|review','reference'=>'...', 'details'=>[]]
     */
    public function verify(array $payload): array;
}


