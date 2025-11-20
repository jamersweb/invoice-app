<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\ProfitAllocation;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $investments = Investment::all();
        $transactions = Transaction::all();
        $allocations = ProfitAllocation::all();
        $expenses = Expense::all();

        // Portfolio Health Score Calculation
        $totalPrincipal = $investments->sum('amount');
        $realizedProfit = $allocations->where('deal_status', 'Ended')->sum('individual_profit');
        $pendingProfit = $allocations->where('deal_status', 'Ongoing')->sum('individual_profit');
        $totalFund = $totalPrincipal + $realizedProfit;
        
        $lockedAmount = $transactions->where('status', 'Ongoing')->sum('net_amount');
        $availableBalance = $totalFund - $lockedAmount;
        $utilizationRate = $totalFund > 0 ? ($lockedAmount / $totalFund) * 100 : 0;
        
        $totalNetProfit = $transactions->sum(function($t) {
            return ($t->net_amount * $t->profit_margin / 100) - ($t->disbursement_charges ?? 0);
        });
        $totalDisbursed = $transactions->sum('net_amount');
        $profitabilityRate = $totalDisbursed > 0 ? ($totalNetProfit / $totalDisbursed) * 100 : 0;

        // Health score (0-100)
        $healthScore = min(100, max(0, 
            ($utilizationRate * 0.3) + 
            ($profitabilityRate * 0.4) + 
            (($realizedProfit / max($totalPrincipal, 1)) * 100 * 0.3)
        ));

        // Alerts
        $alerts = [];
        
        // Low utilization alert
        if ($utilizationRate < 50) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Fund Utilization',
                'message' => "Only {$utilizationRate}% of funds are currently utilized.",
            ];
        }

        // High pending expenses
        $pendingExpenses = $expenses->where('status', 'Pending')->sum('amount');
        if ($pendingExpenses > $totalFund * 0.1) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'High Pending Expenses',
                'message' => "Pending expenses ({$pendingExpenses} AED) exceed 10% of total fund.",
            ];
        }

        // Overdue deals
        $overdueDeals = $transactions->where('status', 'Ongoing')->filter(function($t) {
            $endDate = \Carbon\Carbon::parse($t->date_of_transaction)->addDays($t->sales_cycle);
            return $endDate->isPast();
        });
        
        if ($overdueDeals->count() > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Overdue Deals',
                'message' => "{$overdueDeals->count()} deal(s) have passed their expected end date.",
            ];
        }

        return Inertia::render('Forfaiting/Analytics/Index', [
            'healthScore' => round($healthScore, 2),
            'alerts' => $alerts,
            'metrics' => [
                'utilizationRate' => round($utilizationRate, 2),
                'profitabilityRate' => round($profitabilityRate, 2),
                'totalFund' => $totalFund,
                'availableBalance' => $availableBalance,
                'lockedAmount' => $lockedAmount,
            ],
        ]);
    }
}

