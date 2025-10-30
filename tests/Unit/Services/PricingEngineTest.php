<?php

namespace Tests\Unit\Services;

use App\Modules\Offers\Services\PricingEngineService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PricingEngineTest extends TestCase
{
    public function test_pricing_outputs_expected_fields(): void
    {
        $svc = new PricingEngineService();
        $due = Carbon::now()->addDays(45);
        $out = $svc->price(10000, $due, 'A', 'C', 2.0);

        $this->assertArrayHasKey('tenor_days', $out);
        $this->assertArrayHasKey('discount_rate', $out);
        $this->assertArrayHasKey('discount_amount', $out);
        $this->assertArrayHasKey('admin_fee', $out);
        $this->assertArrayHasKey('net_amount', $out);
        $this->assertGreaterThan(0, $out['tenor_days']);
        $this->assertGreaterThan(0, $out['discount_amount']);
        $this->assertGreaterThan(0, $out['admin_fee']);
        $this->assertGreaterThan(0, $out['net_amount']);
    }
}


