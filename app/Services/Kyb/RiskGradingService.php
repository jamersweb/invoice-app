<?php

namespace App\Services\Kyb;

use App\Models\Document;

class RiskGradingService
{
    public function gradeForSupplier(int $ownerId): string
    {
        $docs = Document::where('owner_type', 'supplier')->where('owner_id', $ownerId)->get();
        $hasAll = $docs->count() >= 3; // CR, VAT, Bank Letter
        $allRecent = $docs->every(function ($d) {
            // require at least 6 months validity remaining when expiry exists
            return !$d->expiry_at || $d->expiry_at->gt(now()->addMonths(6));
        });
        $hasVipFlag = (bool) $docs->max('vip');

        if ($hasAll && $allRecent) {
            if ($hasVipFlag) return 'A';
            return 'A';
        }
        if ($hasAll) {
            return 'B';
        }
        return 'C';
    }
}


