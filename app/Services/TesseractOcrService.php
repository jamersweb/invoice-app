<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class TesseractOcrService implements OcrServiceInterface
{
    public function extract(UploadedFile $file): array
    {
        // Minimal stub: return empty/defaults. Replace with real OCR call or CLI process.
        return [
            'invoice_number' => null,
            'invoice_date' => null,
            'due_date' => null,
            'amount' => null,
            'buyer_name' => null,
        ];
    }
}


