<?php

namespace App\Modules\Invoices\Contracts;

use Illuminate\Http\UploadedFile;

interface OcrServiceInterface
{
    public function extract(UploadedFile|string $filePath): array;
}


