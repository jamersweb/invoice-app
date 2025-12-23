<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\ProfitAllocation;
use App\Models\Transaction;
use App\Models\Investment;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProfitAllocationController extends Controller
{
    public function index(Request $request)
    {
        $query = ProfitAllocation::with('transaction');

        if ($request->has('investor')) {
            $query->where('investor_name', $request->investor);
        }

        if ($request->has('status')) {
            $query->where('deal_status', $request->status);
        }

        if ($request->has('transaction_id')) {
            $query->where('transaction_id', $request->transaction_id);
        }

        $allocations = $query->orderBy('allocation_date', 'desc')->paginate(20);
        $transactions = Transaction::select('id', 'transaction_number', 'customer')->get();

        return Inertia::render('Forfaiting/ProfitAllocations/Index', [
            'allocations' => $allocations,
            'transactions' => $transactions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'investor_name' => 'required|string|max:255',
            'individual_profit' => 'required|numeric|min:0',
            'profit_percentage' => 'required|numeric|min:0|max:100',
            'deal_status' => 'required|in:Ongoing,Ended',
            'allocation_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $allocation = ProfitAllocation::create($validated);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => ProfitAllocation::class,
            'entity_id' => $allocation->id,
            'action' => 'created',
            'diff_json' => ['new_data' => $validated],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Profit allocation created successfully');
    }

    public function recalculate(Request $request, \App\Services\ProfitAllocationService $service)
    {
        $transactionId = $request->input('transaction_id');

        if ($transactionId) {
            $success = $service->calculateAndAllocate($transactionId);

            if (!$success) {
                return redirect()->back()->with('error', 'Failed to calculate profit allocation. Check investments.');
            }
        }

        return redirect()->back()->with('success', 'Profit allocations recalculated successfully based on: Incoming - Outgoing - Expenses');
    }
}

