<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

interface OcrServiceInterface
{
    /** @return array Extracted fields: ['invoice_number'=>..., 'invoice_date'=>..., 'due_date'=>..., 'amount'=>..., 'buyer_name'=>...] */
    public function extract(UploadedFile $file): array;
}


