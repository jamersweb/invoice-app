<?php

namespace App\Services;

use App\Models\ProfitAllocation;
use App\Models\Transaction;
use App\Models\Investment;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ProfitAllocationService
{
       /**
        * Calculate and save profit allocation for a specific transaction.
        * Formula: net_profit = incoming - outgoing - expenses
        *
        * @param int $transactionId
        * @return bool
        */
       public function calculateAndAllocate(int $transactionId): bool
       {
              return DB::transaction(function () use ($transactionId) {
                     $transaction = Transaction::findOrFail($transactionId);

                     // 1. Calculate Incoming (Net amount from transaction)
                     $incoming = $transaction->net_amount;

                     // 2. Calculate Outgoing (Disbursements/Charges)
                     $outgoing = $transaction->disbursement_charges ?? 0;

                     // 3. Calculate Expenses related to this transaction/deal
                     $expenses = Expense::where('status', 'Approved')
                            ->where(function ($query) use ($transaction) {
                                   $query->where('notes', 'like', '%' . $transaction->transaction_number . '%')
                                          ->orWhere('description', 'like', '%' . $transaction->transaction_number . '%');
                            })
                            ->sum('amount');

                     $netProfit = $incoming - $outgoing - $expenses;

                     // 4. Get all active investments to distribute profit
                     $investments = Investment::all();
                     $totalInvestmentAmount = $investments->sum('amount');

                     if ($totalInvestmentAmount <= 0) {
                            return false;
                     }

                     // Clean existing allocations for this transaction before recalculating
                     ProfitAllocation::where('transaction_id', $transactionId)->delete();

                     // 5. Create new allocations
                     foreach ($investments as $investment) {
                            $proportion = $investment->amount / $totalInvestmentAmount;
                            $individualProfit = $netProfit * $proportion;
                            $percentage = $proportion * 100;

                            ProfitAllocation::create([
                                   'transaction_id' => $transactionId,
                                   'investor_name' => $investment->name,
                                   'individual_profit' => $individualProfit,
                                   'profit_percentage' => $percentage,
                                   'deal_status' => $transaction->status === 'Ended' ? 'Ended' : 'Ongoing',
                                   'allocation_date' => now(),
                                   'notes' => "Auto-calculated: Incoming({$incoming}) - Outgoing({$outgoing}) - Expenses({$expenses})",
                            ]);
                     }

                     // 6. Notify investors
                     (new \App\Services\AppNotificationService())->notifyProfitAllocationFinalized($transaction);

                     return true;
              });
       }
}
