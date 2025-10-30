<?php

namespace App\Modules\Repayments\Services;

use App\Modules\Repayments\Models\ExpectedRepayment;
use App\Modules\Repayments\Models\ReceivedRepayment;
use App\Modules\Repayments\Models\RepaymentAllocation;
use Illuminate\Support\Facades\DB;

class RepaymentAllocatorService
{
    public function allocate(ReceivedRepayment $received): void
    {
        DB::transaction(function () use ($received) {
            $remaining = (float) $received->amount - (float) $received->allocated_amount;
            if ($remaining <= 0) {
                return;
            }
            $expectedList = ExpectedRepayment::query()
                ->where('buyer_id', $received->buyer_id)
                ->whereIn('status', ['open', 'partial'])
                ->orderBy('due_date')
                ->lockForUpdate()
                ->get();

            foreach ($expectedList as $expected) {
                if ($remaining <= 0) {
                    break;
                }
                $openAmount = (float) $expected->amount;
                $allocated = min($openAmount, $remaining);
                RepaymentAllocation::create([
                    'received_repayment_id' => $received->id,
                    'expected_repayment_id' => $expected->id,
                    'amount' => $allocated,
                ]);
                $remaining -= $allocated;
                if ($allocated >= $openAmount) {
                    $expected->status = 'settled';
                } else {
                    $expected->status = 'partial';
                    $expected->amount = $openAmount - $allocated;
                }
                $expected->save();
            }

            $received->allocated_amount = (float) $received->amount - max(0, $remaining);
            $received->unallocated_amount = max(0, $remaining);
            $received->save();
        });
    }
}


