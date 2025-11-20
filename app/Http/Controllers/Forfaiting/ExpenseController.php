<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('approver');

        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(20);

        $stats = [
            'total' => Expense::sum('amount'),
            'pending' => Expense::where('status', 'Pending')->sum('amount'),
            'approved' => Expense::where('status', 'Approved')->sum('amount'),
            'rejected' => Expense::where('status', 'Rejected')->sum('amount'),
        ];

        return Inertia::render('Forfaiting/Expenses/Index', [
            'expenses' => $expenses,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::create($validated);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Expense::class,
            'entity_id' => $expense->id,
            'action' => 'created',
            'diff_json' => ['new_data' => $validated],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Expense created successfully');
    }

    public function updateStatus(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        $oldData = $expense->toArray();
        
        $expense->update([
            'status' => $validated['status'],
            'approved_by' => $validated['status'] === 'Approved' ? auth()->id() : null,
            'approved_at' => $validated['status'] === 'Approved' ? now() : null,
        ]);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Expense::class,
            'entity_id' => $expense->id,
            'action' => 'status_updated',
            'diff_json' => ['old_data' => $oldData, 'new_data' => $expense->toArray()],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Expense status updated successfully');
    }

    public function destroy(Expense $expense)
    {
        $oldData = $expense->toArray();
        
        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Expense::class,
            'entity_id' => $expense->id,
            'action' => 'deleted',
            'diff_json' => ['old_data' => $oldData],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);

        $expense->delete();

        return redirect()->back()->with('success', 'Expense deleted successfully');
    }
}

