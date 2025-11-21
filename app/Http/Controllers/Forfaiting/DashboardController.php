<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\ProfitAllocation;
use App\Models\Expense;
use App\Models\User;
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
        $users = User::whereNotNull('investor_id')->get();

        // Basic Metrics
        $totalPrincipal = $investments->sum('amount');
        $realizedProfit = $allocations->where('deal_status', 'Ended')->sum('individual_profit');
        $pendingProfit = $allocations->where('deal_status', 'Ongoing')->sum('individual_profit');
        $totalFund = $totalPrincipal + $realizedProfit;
        
        $lockedAmount = $transactions->where('status', 'Ongoing')->sum('net_amount');
        $pendingExpenses = $expenses->where('status', 'Pending')->sum('amount');
        $availableBalance = $totalFund - $lockedAmount - $pendingExpenses;
        
        // Weighted Portfolio APY
        $ongoingTransactions = $transactions->where('status', 'Ongoing');
        $weightedPortfolioAPY = 0;
        if ($ongoingTransactions->count() > 0) {
            $totalOngoingExposure = $ongoingTransactions->sum('net_amount');
            $weightedPortfolioAPY = $ongoingTransactions->sum(function($t) use ($totalOngoingExposure) {
                $weight = $t->net_amount / $totalOngoingExposure;
                $profitPercent = $t->profit_margin - (($t->disbursement_charges ?? 0) / $t->net_amount * 100);
                $annualizedProfit = $t->sales_cycle > 0 ? $profitPercent * (360 / $t->sales_cycle) : 0;
                return $annualizedProfit * $weight;
            });
        }

        $idlePercent = $totalFund > 0 ? ($availableBalance / $totalFund) * 100 : 0;

        // Customer Analysis
        $customerAnalysis = [];
        foreach ($transactions as $t) {
            if (!isset($customerAnalysis[$t->customer])) {
                $customerAnalysis[$t->customer] = [
                    'total' => 0,
                    'ended' => 0,
                    'ongoing' => 0,
                    'totalDisbursed' => 0,
                    'netProfit' => 0,
                    'totalSalesCycle' => 0,
                ];
            }
            
            $customerAnalysis[$t->customer]['total']++;
            if ($t->status === 'Ended') $customerAnalysis[$t->customer]['ended']++;
            if ($t->status === 'Ongoing') $customerAnalysis[$t->customer]['ongoing']++;
            
            $customerAnalysis[$t->customer]['totalDisbursed'] += $t->net_amount;
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
            
            return [
                'name' => $name,
                ...$data,
                'avgCustomerTenor' => $avgCustomerTenor,
                'profitPercent' => $profitPercent,
                'perAnnumProfitPercent' => $perAnnumProfitPercent,
                'shareOfProfit' => $totalProfit > 0 ? ($data['netProfit'] / $totalProfit) * 100 : 0,
                'shareOfDisbursements' => $totalDisbursed > 0 ? ($data['totalDisbursed'] / $totalDisbursed) * 100 : 0,
                'avgTicketSize' => $data['total'] > 0 ? $data['totalDisbursed'] / $data['total'] : 0,
            ];
        })->sortByDesc('perAnnumProfitPercent')->values();

        // Customer Exposure
        $customerExposure = [];
        foreach ($ongoingTransactions as $t) {
            if (!isset($customerExposure[$t->customer])) {
                $customerExposure[$t->customer] = ['totalExposure' => 0, 'dealCount' => 0];
            }
            $customerExposure[$t->customer]['totalExposure'] += $t->net_amount;
            $customerExposure[$t->customer]['dealCount']++;
        }

        $totalOngoingExposure = collect($customerExposure)->sum('totalExposure');
        $customerExposure = collect($customerExposure)->map(function($data, $customer) use ($totalOngoingExposure, $totalFund) {
            return [
                'customer' => $customer,
                'exposure' => $data['totalExposure'],
                'dealCount' => $data['dealCount'],
                'exposurePercent' => $totalOngoingExposure > 0 ? ($data['totalExposure'] / $totalOngoingExposure) * 100 : 0,
            ];
        })->sortByDesc('exposure')->values();

        // Counterparty Risk (HHI)
        $exposureShares = $customerExposure->pluck('exposurePercent')->map(function($share) {
            return ($share / 100) * ($share / 100);
        });
        $hhi = $exposureShares->sum() * 10000;
        $maxSingleExposure = $customerExposure->max('exposurePercent') ?? 0;

        // Investor Intelligence
        $investorIntelligence = [];
        foreach ($investments as $inv) {
            if (!isset($investorIntelligence[$inv->name])) {
                $investorIntelligence[$inv->name] = [
                    'totalPrincipal' => 0,
                    'realizedProfit' => 0,
                    'pendingProfit' => 0,
                    'recentProfits' => 0,
                    'lastInvestmentDate' => $inv->date,
                ];
            }
            $investorIntelligence[$inv->name]['totalPrincipal'] += $inv->amount;
            if ($inv->date > $investorIntelligence[$inv->name]['lastInvestmentDate']) {
                $investorIntelligence[$inv->name]['lastInvestmentDate'] = $inv->date;
            }
        }

        $ninetyDaysAgo = Carbon::now()->subDays(90);
        foreach ($allocations as $alloc) {
            if (!isset($investorIntelligence[$alloc->investor_name])) {
                $investorIntelligence[$alloc->investor_name] = [
                    'totalPrincipal' => 0,
                    'realizedProfit' => 0,
                    'pendingProfit' => 0,
                    'recentProfits' => 0,
                    'lastInvestmentDate' => null,
                ];
            }
            
            if ($alloc->deal_status === 'Ended') {
                $investorIntelligence[$alloc->investor_name]['realizedProfit'] += $alloc->individual_profit;
                if ($alloc->allocation_date && $alloc->allocation_date >= $ninetyDaysAgo) {
                    $investorIntelligence[$alloc->investor_name]['recentProfits'] += $alloc->individual_profit;
                }
            } else {
                $investorIntelligence[$alloc->investor_name]['pendingProfit'] += $alloc->individual_profit;
            }
        }

        $investorIntelligence = collect($investorIntelligence)->map(function($data, $name) {
            $totalCapital = $data['totalPrincipal'] + $data['realizedProfit'];
            $weightedYield = $data['totalPrincipal'] > 0 
                ? ($data['realizedProfit'] / $data['totalPrincipal']) * 100 
                : 0;
            $profitToPrincipalPercent = $data['totalPrincipal'] > 0 
                ? ($data['realizedProfit'] / $data['totalPrincipal']) * 100 
                : 0;
            $hasRecentProfits = $data['recentProfits'] > 0;
            $redemptionRisk = !$hasRecentProfits && $profitToPrincipalPercent < 3;
            
            $daysSinceLastInvestment = $data['lastInvestmentDate'] 
                ? Carbon::now()->diffInDays($data['lastInvestmentDate']) 
                : 999;

            $ongoingDealsForInvestor = ProfitAllocation::where('investor_name', $name)
                ->where('deal_status', 'Ongoing')
                ->get();
            $deployedCapital = $ongoingDealsForInvestor->sum(function($a) {
                return $a->transaction->net_amount ?? 0;
            });
            $deploymentRate = $totalCapital > 0 ? ($deployedCapital / $totalCapital) * 100 : 0;

            return [
                'name' => $name,
                'totalPrincipal' => $data['totalPrincipal'],
                'realizedProfit' => $data['realizedProfit'],
                'pendingProfit' => $data['pendingProfit'],
                'totalCapital' => $totalCapital,
                'weightedYield' => $weightedYield,
                'recentProfits' => $data['recentProfits'],
                'redemptionRisk' => $redemptionRisk,
                'daysSinceLastInvestment' => $daysSinceLastInvestment,
                'deployedCapital' => $deployedCapital,
                'deploymentRate' => $deploymentRate,
            ];
        })->sortByDesc('totalCapital')->values();

        $investorStabilityPercent = $investorIntelligence->count() > 0 
            ? (($investorIntelligence->count() - $investorIntelligence->where('redemptionRisk', true)->count()) / $investorIntelligence->count()) * 100 
            : 100;

        // Deal Recycling Speed
        $endedTransactions = $transactions->where('status', 'Ended');
        $cyclesPerMonth = 0;
        if ($endedTransactions->count() > 0) {
            $oldestDate = $endedTransactions->min('date_of_transaction');
            $totalDays = Carbon::now()->diffInDays($oldestDate);
            $totalMonths = $totalDays / 30;
            if ($totalMonths > 0) {
                $totalVolume = $endedTransactions->sum('net_amount');
                $monthlyVolume = $totalVolume / $totalMonths;
                $avgDeployedCapital = $lockedAmount;
                $cyclesPerMonth = $avgDeployedCapital > 0 ? $monthlyVolume / $avgDeployedCapital : 0;
            }
        }

        // Portfolio Health Score
        $liquidityScore = ($idlePercent >= 5 && $idlePercent <= 10) ? 100 
            : ($idlePercent < 5 ? max(0, 100 - (5 - $idlePercent) * 10) 
            : max(0, 100 - ($idlePercent - 10) * 5));
        
        $yieldScore = min(100, ($weightedPortfolioAPY / 10) * 100);
        $counterpartyScore = $hhi < 1500 ? 100 : ($hhi < 2500 ? 70 : ($hhi < 5000 ? 40 : 20));
        $investorScore = max(0, $investorStabilityPercent * 2 - 100);
        $velocityScore = min(100, ($cyclesPerMonth / 1.5) * 100);

        $portfolioHealthScore = (
            $liquidityScore * 0.25 +
            $yieldScore * 0.30 +
            $counterpartyScore * 0.20 +
            $investorScore * 0.15 +
            $velocityScore * 0.10
        );

        // System Alerts
        $alerts = [];
        if ($idlePercent > 8) {
            $alerts[] = [
                'severity' => 'warning',
                'title' => 'High Idle Funds',
                'message' => "Idle capital at " . number_format($idlePercent, 2) . "%. Target: 5-10%. Action: Deploy to top-quality customers or onboard new counterparties.",
            ];
        }
        if ($idlePercent < 5) {
            $alerts[] = [
                'severity' => 'warning',
                'title' => 'Low Liquidity Cushion',
                'message' => "Idle capital at " . number_format($idlePercent, 2) . "%, below 5% target. Consider maintaining higher reserves.",
            ];
        }

        $highExposureCustomers = $customerExposure->where('exposurePercent', '>', 35);
        if ($highExposureCustomers->count() > 0) {
            $alerts[] = [
                'severity' => 'critical',
                'title' => 'High Customer Concentration',
                'message' => $highExposureCustomers->count() . " customer(s) exceed 35% exposure. Action: Diversify portfolio.",
            ];
        }

        if ($hhi > 2500) {
            $alerts[] = [
                'severity' => 'critical',
                'title' => 'High Portfolio Concentration (HHI)',
                'message' => "HHI " . number_format($hhi) . " indicates concentrated risk. Action: Diversify exposure across more counterparties.",
            ];
        }

        $investorsAtRisk = $investorIntelligence->where('redemptionRisk', true);
        if ($investorsAtRisk->count() > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'title' => 'Investors at Redemption Risk',
                'message' => $investorsAtRisk->count() . " investor(s) with no recent profits or low returns.",
            ];
        }

        // Predictive Analytics - Next 30 Days
        $next30DaysDeals = $ongoingTransactions->map(function($t) {
            $endDate = Carbon::parse($t->date_of_transaction)->addDays($t->sales_cycle);
            $daysUntilEnd = Carbon::now()->diffInDays($endDate, false);
            $profit = ($t->net_amount * $t->profit_margin / 100) - ($t->disbursement_charges ?? 0);
            
            return [
                'endDate' => $endDate->format('Y-m-d'),
                'daysUntilEnd' => $daysUntilEnd,
                'profit' => $profit,
                'amount' => $t->net_amount,
                'customer' => $t->customer,
                'transactionNumber' => $t->transaction_number,
            ];
        })->filter(function($d) {
            return $d['daysUntilEnd'] >= 0 && $d['daysUntilEnd'] <= 30;
        })->sortBy('daysUntilEnd')->values();

        $totalExpectedProfit30d = $next30DaysDeals->sum('profit');
        $totalReturningCapital = $next30DaysDeals->sum(function($d) {
            return $d['amount'] + $d['profit'];
        });
        $maxDeploymentCapacity = $availableBalance + $totalReturningCapital;

        // Customer Quality Scores
        $customerQualityScores = $customerAnalysis->filter(function($c) {
            return $c['total'] >= 3;
        })->map(function($customer) use ($customerExposure) {
            $exposureData = $customerExposure->firstWhere('customer', $customer['name']);
            $exposurePercent = $exposureData ? $exposureData['exposurePercent'] : 0;
            
            $profitScore = min($customer['perAnnumProfitPercent'] / 20 * 100, 100);
            
            $tenorScore = $customer['avgCustomerTenor'] < 60 ? 100 
                : ($customer['avgCustomerTenor'] <= 90 ? 80 
                : ($customer['avgCustomerTenor'] <= 120 ? 60 
                : max(0, 60 - ($customer['avgCustomerTenor'] - 120) / 2)));
            
            $concentrationPenalty = $exposurePercent > 25 
                ? max(0, 100 - ($exposurePercent - 25) * 3)
                : 100;
            
            $qualityScore = ($profitScore * 0.4 + $tenorScore * 0.3 + $concentrationPenalty * 0.3);

            return [
                'customer' => $customer['name'],
                'qualityScore' => $qualityScore,
                'profitScore' => $profitScore,
                'tenorScore' => $tenorScore,
                'concentrationPenalty' => $concentrationPenalty,
                'perAnnumProfit' => $customer['perAnnumProfitPercent'],
                'avgTenor' => $customer['avgCustomerTenor'],
                'exposurePercent' => $exposurePercent,
                'dealCount' => $customer['total'],
            ];
        })->sortByDesc('qualityScore')->values();

        return Inertia::render('Forfaiting/Dashboard', [
            'metrics' => [
                'totalPrincipal' => $totalPrincipal,
                'realizedProfit' => $realizedProfit,
                'pendingProfit' => $pendingProfit,
                'totalFund' => $totalFund,
                'lockedAmount' => $lockedAmount,
                'pendingExpenses' => $pendingExpenses,
                'availableBalance' => $availableBalance,
                'weightedPortfolioAPY' => $weightedPortfolioAPY,
                'idlePercent' => $idlePercent,
            ],
            'portfolioHealthScore' => [
                'overall' => $portfolioHealthScore,
                'liquidity' => $liquidityScore,
                'yield' => $yieldScore,
                'counterpartyRisk' => $counterpartyScore,
                'investorStability' => $investorScore,
                'dealVelocity' => $velocityScore,
            ],
            'counterpartyRisk' => [
                'hhi' => $hhi,
                'maxSingleExposure' => $maxSingleExposure,
                'top3ProfitPercent' => $customerAnalysis->take(3)->sum('shareOfProfit'),
            ],
            'customerAnalysis' => $customerAnalysis,
            'customerExposure' => $customerExposure,
            'customerQualityScores' => $customerQualityScores,
            'investorIntelligence' => $investorIntelligence,
            'investorStabilityPercent' => $investorStabilityPercent,
            'dealRecyclingSpeed' => [
                'cyclesPerMonth' => $cyclesPerMonth,
            ],
            'predictiveAnalytics' => [
                'totalExpectedProfit30d' => $totalExpectedProfit30d,
                'totalReturningCapital' => $totalReturningCapital,
                'maxDeploymentCapacity' => $maxDeploymentCapacity,
                'next10DealsEnding' => $next30DaysDeals->take(10)->map(function($d) {
                    return [
                        'transactionNumber' => $d['transactionNumber'],
                        'customer' => $d['customer'],
                        'amount' => $d['amount'] + $d['profit'],
                        'principal' => $d['amount'],
                        'profit' => $d['profit'],
                        'endDate' => $d['endDate'],
                        'daysUntilEnd' => $d['daysUntilEnd'],
                    ];
                })->values(),
            ],
            'alerts' => array_slice($alerts, 0, 5),
        ]);
    }
}
