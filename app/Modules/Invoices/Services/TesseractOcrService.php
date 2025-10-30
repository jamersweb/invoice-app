<?php

namespace App\Modules\Invoices\Services;

use App\Modules\Invoices\Contracts\OcrServiceInterface;
use Illuminate\Http\UploadedFile;

class TesseractOcrService implements OcrServiceInterface
{
    public function extract(UploadedFile|string $filePath): array
    {
        return [
            'invoice_number' => null,
            'date' => null,
            'amount' => null,
            'currency' => null,
            'supplier_name' => null,
            'buyer_name' => null,
            'line_items' => [],
            'confidence_score' => 0,
            'raw_response' => null,
        ];
    }
}


