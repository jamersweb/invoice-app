<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Funding\Models\Funding;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Repayments\Models\ReceivedRepayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        // API returns 401 for unauthenticated JSON
        $this->getJson('/api/v1/dashboard/metrics')->assertStatus(401);
    }

    public function test_returns_correct_aggregates(): void
    {
        $user = User::factory()->create();

        Funding::create(['invoice_id' => 1, 'offer_id' => 1, 'amount' => 1000]);
        Funding::create(['invoice_id' => 2, 'offer_id' => 2, 'amount' => 500]);

        ReceivedRepayment::create(['buyer_id' => 1, 'amount' => 200, 'received_date' => now()]);
        ReceivedRepayment::create(['buyer_id' => 1, 'amount' => 300, 'received_date' => now()]);

        Invoice::create(['supplier_id' => 1, 'buyer_id' => 1, 'invoice_number' => 'INV-1', 'amount' => 100, 'currency' => 'USD', 'due_date' => now(), 'status' => 'overdue']);

        $res = $this->actingAs($user)->getJson('/api/v1/dashboard/metrics')->assertOk()->json();

        $this->assertEquals(1500.0, (float) $res['kpis']['totalFunded']);
        $this->assertEquals(500.0, (float) $res['kpis']['totalRepaid']);
        $this->assertEquals(1000.0, (float) $res['kpis']['outstanding']);
        $this->assertEquals(100.0, (float) $res['kpis']['overdue']);
    }
}


