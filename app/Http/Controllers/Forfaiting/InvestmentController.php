<?php

namespace App\Http\Controllers\Forfaiting;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InvestmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Investment::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('investor')) {
            $query->where('name', $request->investor);
        }

        $investments = $query->orderBy('date', 'desc')->paginate(20);

        return Inertia::render('Forfaiting/Investments/Index', [
            'investments' => $investments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|max:3',
            'date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $investment = Investment::create($validated);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Investment::class,
            'entity_id' => $investment->id,
            'action' => 'created',
            'diff_json' => ['new_data' => $validated],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Investment created successfully');
    }

    public function destroy(Investment $investment)
    {
        $oldData = $investment->toArray();
        
        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Investment::class,
            'entity_id' => $investment->id,
            'action' => 'deleted',
            'diff_json' => ['old_data' => $oldData],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);

        $investment->delete();

        return redirect()->back()->with('success', 'Investment deleted successfully');
    }

    public function export(Request $request)
    {
        $investments = Investment::all();
        
        $filename = 'investments_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($investments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Amount', 'Currency', 'Date', 'Notes', 'Created At']);
            
            foreach ($investments as $investment) {
                fputcsv($file, [
                    $investment->id,
                    $investment->name,
                    $investment->amount,
                    $investment->currency,
                    $investment->date->format('Y-m-d'),
                    $investment->notes,
                    $investment->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

