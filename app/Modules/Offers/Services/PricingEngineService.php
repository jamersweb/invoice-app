<?php

namespace App\Modules\Offers\Services;

use Carbon\CarbonInterface;

class PricingEngineService
{
    public function price(float $amount, CarbonInterface $dueDate, string $supplierGrade = 'B', string $buyerGrade = 'B', float $historicalDefaultRate = 0.0): array
    {
        $today = now();
        $tenorDays = $today->diffInDays($dueDate);

        $baseRate = $this->baseRateForTenor($tenorDays);
        // Try pricing rules override
        try {
            $rule = \App\Models\PricingRule::query()
                ->where('is_active', true)
                ->where('tenor_min', '<=', $tenorDays)
                ->where('tenor_max', '>=', $tenorDays)
                ->where('amount_min', '<=', $amount)
                ->where('amount_max', '>=', $amount)
                ->orderByDesc('tenor_min')
                ->first();
            if ($rule) {
                $baseRate = (float) $rule->base_rate;
            }
        } catch (\Throwable $e) {}
        $gradeAdj = $this->gradeAdjustment($supplierGrade) + $this->gradeAdjustment($buyerGrade);
        $discountRate = max(0, $baseRate + $gradeAdj + ($historicalDefaultRate * 0.2));

        $discountAmount = round($amount * ($discountRate / 100) * ($tenorDays / 360), 2);
        $adminFee = 50.00; // fixed admin fee placeholder
        $netAmount = round($amount - $discountAmount - $adminFee, 2);

        return [
            'tenor_days' => $tenorDays,
            'base_rate' => $baseRate,
            'grade_adjustment' => $gradeAdj,
            'discount_rate' => round($discountRate, 4),
            'discount_amount' => $discountAmount,
            'admin_fee' => $adminFee,
            'net_amount' => $netAmount,
            'inputs' => compact('amount', 'supplierGrade', 'buyerGrade', 'historicalDefaultRate'),
        ];
    }

    private function baseRateForTenor(int $tenorDays): float
    {
        return match (true) {
            $tenorDays <= 30 => 6.0,
            $tenorDays <= 60 => 7.0,
            $tenorDays <= 90 => 8.0,
            default => 9.0,
        };
    }

    private function gradeAdjustment(string $grade): float
    {
        return match (strtoupper($grade)) {
            'A' => -0.5,
            'B' => 0.0,
            'C' => 0.5,
            default => 0.0,
        };
    }
}


