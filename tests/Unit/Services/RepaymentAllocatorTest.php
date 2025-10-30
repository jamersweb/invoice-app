<?php

namespace Tests\Unit\Services;

use App\Modules\Repayments\Models\ExpectedRepayment;
use App\Modules\Repayments\Models\ReceivedRepayment;
use App\Modules\Repayments\Services\RepaymentAllocatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepaymentAllocatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifo_allocation_and_status_updates(): void
    {
        $e1 = ExpectedRepayment::create(['invoice_id' => 1, 'buyer_id' => 10, 'amount' => 300, 'due_date' => now()->addDays(5), 'status' => 'open']);
        $e2 = ExpectedRepayment::create(['invoice_id' => 2, 'buyer_id' => 10, 'amount' => 300, 'due_date' => now()->addDays(10), 'status' => 'open']);

        $r = ReceivedRepayment::create(['buyer_id' => 10, 'amount' => 400, 'received_date' => now(), 'allocated_amount' => 0, 'unallocated_amount' => 0]);

        (new RepaymentAllocatorService())->allocate($r);

        $this->assertDatabaseHas('expected_repayments', ['id' => $e1->id, 'status' => 'settled']);
        $this->assertDatabaseHas('expected_repayments', ['id' => $e2->id, 'status' => 'partial']);
        $this->assertDatabaseHas('received_repayments', ['id' => $r->id, 'allocated_amount' => 400]);
    }
}


