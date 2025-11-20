<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('customerRelation');

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('transaction_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer')) {
            $query->where('customer', $request->customer);
        }

        $transactions = $query->orderBy('date_of_transaction', 'desc')->paginate(20);

        return Inertia::render('Forfaiting/Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_number' => 'required|string|unique:transactions,transaction_number',
            'customer' => 'required|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'date_of_transaction' => 'required|date',
            'net_amount' => 'required|numeric|min:0.01',
            'profit_margin' => 'required|numeric|min:0|max:100',
            'disbursement_charges' => 'nullable|numeric|min:0',
            'sales_cycle' => 'required|integer|min:1',
            'status' => 'required|in:Ongoing,Ended',
            'notes' => 'nullable|string',
        ]);

        $transaction = Transaction::create($validated);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Transaction::class,
            'entity_id' => $transaction->id,
            'action' => 'created',
            'diff_json' => ['new_data' => $validated],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Transaction created successfully');
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:Ongoing,Ended',
            'profit_margin' => 'sometimes|numeric|min:0|max:100',
            'sales_cycle' => 'sometimes|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $oldData = $transaction->toArray();
        $transaction->update($validated);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Transaction::class,
            'entity_id' => $transaction->id,
            'action' => 'updated',
            'diff_json' => ['old_data' => $oldData, 'new_data' => $validated],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Transaction updated successfully');
    }

    public function destroy(Transaction $transaction)
    {
        $oldData = $transaction->toArray();
        
        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Transaction::class,
            'entity_id' => $transaction->id,
            'action' => 'deleted',
            'diff_json' => ['old_data' => $oldData],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);

        $transaction->delete();

        return redirect()->back()->with('success', 'Transaction deleted successfully');
    }
}

