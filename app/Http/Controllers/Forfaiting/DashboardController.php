<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\ProfitAllocation;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $investments = Investment::all();
        $transactions = Transaction::all();
        $allocations = ProfitAllocation::all();
        $expenses = Expense::all();

        $USD_RATE = 3.67;

        // Calculate metrics
        $totalPrincipal = $investments->sum('amount');
        $realizedProfit = $allocations->where('deal_status', 'Ended')->sum('individual_profit');
        $pendingProfit = $allocations->where('deal_status', 'Ongoing')->sum('individual_profit');
        $totalFund = $totalPrincipal + $realizedProfit;
        
        $lockedAmount = $transactions->where('status', 'Ongoing')->sum('net_amount');
        $pendingExpenses = $expenses->where('status', 'Pending')->sum('amount');
        $availableBalance = $totalFund - $lockedAmount - $pendingExpenses;
        
        $completedDisbursements = $transactions->where('status', 'Ended')->sum('net_amount');
        $totalNetAmountDisbursed = $transactions->sum('net_amount');
        
        $totalNetProfit = $transactions->sum(function($t) {
            return ($t->net_amount * $t->profit_margin / 100) - ($t->disbursement_charges ?? 0);
        });
        
        $globalProfitabilityPercent = $totalNetAmountDisbursed > 0 
            ? ($totalNetProfit / $totalNetAmountDisbursed) * 100 
            : 0;
        
        $avgTenure = $transactions->count() > 0 
            ? $transactions->avg('sales_cycle') 
            : 0;
        
        $perDayProfit = $avgTenure > 0 ? ($globalProfitabilityPercent / $avgTenure) : 0;
        $perAnnumProfit = $perDayProfit * 360;
        $unutilizedPercent = $totalFund > 0 ? ($availableBalance / $totalFund) * 100 : 0;

        // Upcoming deals
        $upcomingDeals = $transactions->where('status', 'Ongoing')->map(function($t) {
            $endDate = Carbon::parse($t->date_of_transaction)->addDays($t->sales_cycle);
            $daysRemaining = Carbon::now()->diffInDays($endDate, false);
            $expectedProfit = ($t->net_amount * $t->profit_margin / 100) - ($t->disbursement_charges ?? 0);
            
            return [
                'transaction_number' => $t->transaction_number,
                'customer' => $t->customer,
                'disbursementDate' => $t->date_of_transaction->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
                'daysRemaining' => $daysRemaining,
                'amount' => $t->net_amount,
                'expectedProfit' => $expectedProfit,
                'profitMargin' => $t->profit_margin,
            ];
        })->sortBy('endDate')->values();

        // Investor Summary
        $investorSummary = [];
        foreach ($investments as $inv) {
            if (!isset($investorSummary[$inv->name])) {
                $investorSummary[$inv->name] = ['principal' => 0, 'realized' => 0, 'pending' => 0];
            }
            $investorSummary[$inv->name]['principal'] += $inv->amount;
        }
        
        foreach ($allocations as $alloc) {
            if (!isset($investorSummary[$alloc->investor_name])) {
                $investorSummary[$alloc->investor_name] = ['principal' => 0, 'realized' => 0, 'pending' => 0];
            }
            if ($alloc->deal_status === 'Ended') {
                $investorSummary[$alloc->investor_name]['realized'] += $alloc->individual_profit;
            } else {
                $investorSummary[$alloc->investor_name]['pending'] += $alloc->individual_profit;
            }
        }

        $investorSummary = collect($investorSummary)->map(function($data, $name) {
            return [
                'name' => $name,
                'principal' => $data['principal'],
                'profitActual' => $data['realized'],
                'profitWithPending' => $data['realized'] + $data['pending'],
                'totalActual' => $data['principal'] + $data['realized'],
                'totalWithPending' => $data['principal'] + $data['realized'] + $data['pending'],
                'profitPercentActual' => $data['principal'] > 0 ? ($data['realized'] / $data['principal']) * 100 : 0,
                'profitPercentWithPending' => $data['principal'] > 0 ? (($data['realized'] + $data['pending']) / $data['principal']) * 100 : 0,
            ];
        })->values();

        // Customer Analysis
        $customerAnalysis = [];
        foreach ($transactions as $t) {
            if (!isset($customerAnalysis[$t->customer])) {
                $customerAnalysis[$t->customer] = [
                    'total' => 0,
                    'ended' => 0,
                    'ongoing' => 0,
                    'totalDisbursed' => 0,
                    'totalGross' => 0,
                    'netProfit' => 0,
                    'totalSalesCycle' => 0,
                ];
            }
            
            $customerAnalysis[$t->customer]['total']++;
            if ($t->status === 'Ended') $customerAnalysis[$t->customer]['ended']++;
            if ($t->status === 'Ongoing') $customerAnalysis[$t->customer]['ongoing']++;
            
            $customerAnalysis[$t->customer]['totalDisbursed'] += $t->net_amount;
            $gross = $t->net_amount + ($t->net_amount * $t->profit_margin / 100);
            $customerAnalysis[$t->customer]['totalGross'] += $gross;
            
            $profit = ($t->net_amount * $t->profit_margin / 100) - ($t->disbursement_charges ?? 0);
            $customerAnalysis[$t->customer]['netProfit'] += $profit;
            $customerAnalysis[$t->customer]['totalSalesCycle'] += $t->sales_cycle;
        }

        $totalProfit = collect($customerAnalysis)->sum('netProfit');
        $totalDisbursed = collect($customerAnalysis)->sum('totalDisbursed');

        $customerAnalysis = collect($customerAnalysis)->map(function($data, $name) use ($totalProfit, $totalDisbursed) {
            $avgCustomerTenor = $data['total'] > 0 ? $data['totalSalesCycle'] / $data['total'] : 0;
            $profitPercent = $data['totalDisbursed'] > 0 ? ($data['netProfit'] / $data['totalDisbursed']) * 100 : 0;
            $perAnnumProfitPercent = $avgCustomerTenor > 0 ? $profitPercent * (360 / $avgCustomerTenor) : 0;
            $riskAdjustedYield = $avgCustomerTenor > 0 ? $profitPercent / $avgCustomerTenor : 0;
            
            return [
                'name' => $name,
                ...$data,
                'avgCustomerTenor' => $avgCustomerTenor,
                'profitPercent' => $profitPercent,
                'perAnnumProfitPercent' => $perAnnumProfitPercent,
                'riskAdjustedYield' => $riskAdjustedYield,
                'shareOfProfit' => $totalProfit > 0 ? ($data['netProfit'] / $totalProfit) * 100 : 0,
                'shareOfDisbursements' => $totalDisbursed > 0 ? ($data['totalDisbursed'] / $totalDisbursed) * 100 : 0,
                'avgTicketSize' => $data['total'] > 0 ? $data['totalDisbursed'] / $data['total'] : 0,
            ];
        })->values();

        return Inertia::render('Forfaiting/Dashboard', [
            'metrics' => [
                'totalPrincipal' => $totalPrincipal,
                'realizedProfit' => $realizedProfit,
                'pendingProfit' => $pendingProfit,
                'totalFund' => $totalFund,
                'lockedAmount' => $lockedAmount,
                'pendingExpenses' => $pendingExpenses,
                'availableBalance' => $availableBalance,
                'completedDisbursements' => $completedDisbursements,
                'avgTenure' => $avgTenure,
                'perDayProfit' => $perDayProfit,
                'perAnnumProfit' => $perAnnumProfit,
                'unutilizedPercent' => $unutilizedPercent,
                'usdRate' => $USD_RATE,
            ],
            'upcomingDeals' => $upcomingDeals,
            'investorSummary' => $investorSummary,
            'customerAnalysis' => $customerAnalysis,
        ]);
    }
}

