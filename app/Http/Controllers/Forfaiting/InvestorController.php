<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\ProfitAllocation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class InvestorController extends Controller
{
    public function index(Request $request)
    {
        $investments = Investment::all();
        $allocations = ProfitAllocation::all();

        // Calculate investor summaries
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

        // Get investor IDs from users
        $userInvestors = \App\Models\User::whereNotNull('investor_id')->get()->keyBy('name');
        
        $investors = collect($investorSummary)->map(function($data, $name) use ($userInvestors) {
            $user = $userInvestors->get($name);
            return [
                'name' => $name,
                'investor_id' => $user ? $user->investor_id : null,
                'principal' => $data['principal'],
                'profitActual' => $data['realized'],
                'profitWithPending' => $data['realized'] + $data['pending'],
                'totalActual' => $data['principal'] + $data['realized'],
                'totalWithPending' => $data['principal'] + $data['realized'] + $data['pending'],
                'profitPercentActual' => $data['principal'] > 0 ? ($data['realized'] / $data['principal']) * 100 : 0,
                'profitPercentWithPending' => $data['principal'] > 0 ? (($data['realized'] + $data['pending']) / $data['principal']) * 100 : 0,
            ];
        })->values();

        return Inertia::render('Forfaiting/Investors/Index', [
            'investors' => $investors,
        ]);
    }

    public function dashboard(Request $request, $investorId = null)
    {
        $investorId = $investorId ?? $request->query('id');
        
        // If investor_id is provided, get user
        $user = null;
        if ($investorId) {
            $user = User::where('investor_id', $investorId)->first();
        }

        // Get investor name from user or request
        $investorName = $user ? $user->name : $request->query('name');

        if (!$investorName) {
            abort(404, 'Investor not found');
        }

        $investments = Investment::where('name', $investorName)->get();
        $allocations = ProfitAllocation::where('investor_name', $investorName)->get();

        $principal = $investments->sum('amount');
        $realizedProfit = $allocations->where('deal_status', 'Ended')->sum('individual_profit');
        $pendingProfit = $allocations->where('deal_status', 'Ongoing')->sum('individual_profit');

        $allocationsWithTransactions = ProfitAllocation::where('investor_name', $investorName)
            ->with('transaction:id,transaction_number')
            ->get();

        return Inertia::render('Forfaiting/Investors/Dashboard', [
            'investor' => [
                'name' => $investorName,
                'investor_id' => $investorId,
                'principal' => $principal,
                'profitActual' => $realizedProfit,
                'profitWithPending' => $realizedProfit + $pendingProfit,
                'totalActual' => $principal + $realizedProfit,
                'totalWithPending' => $principal + $realizedProfit + $pendingProfit,
                'profitPercentActual' => $principal > 0 ? ($realizedProfit / $principal) * 100 : 0,
                'profitPercentWithPending' => $principal > 0 ? (($realizedProfit + $pendingProfit) / $principal) * 100 : 0,
            ],
            'investments' => $investments,
            'allocations' => $allocationsWithTransactions,
        ]);
    }

    public function generateInvestorId(User $user)
    {
        if (!$user->investor_id) {
            $user->investor_id = Str::uuid()->toString();
            $user->save();
        }

        return response()->json(['investor_id' => $user->investor_id]);
    }
}

