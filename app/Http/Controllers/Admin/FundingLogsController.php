<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundingLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FundingLogsController extends Controller
{
    /**
     * Display funding logs with filters.
     */
    public function index(Request $request): Response
    {
        $query = FundingLog::with(['supplier', 'recordedBy'])
            ->latest('transfer_date');

        // Date filters
        if ($request->filled('date_from')) {
            $query->where('transfer_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transfer_date', '<=', $request->date_to);
        }

        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $logs = $query->paginate(50)->through(function ($log) {
            return [
                'id' => $log->id,
                'supplier_name' => $log->supplier->company_name ?? $log->supplier->legal_name ?? 'N/A',
                'transfer_date' => $log->transfer_date->format('Y-m-d'),
                'amount' => number_format($log->amount, 2),
                'currency' => $log->currency,
                'bank_reference' => $log->bank_reference,
                'internal_reference' => $log->internal_reference,
                'notes' => $log->notes,
                'recorded_by' => $log->recordedBy->name ?? 'N/A',
                'created_at' => $log->created_at->format('Y-m-d H:i'),
            ];
        });

        $suppliers = Supplier::select('id', 'company_name', 'legal_name')
            ->orderBy('company_name')
            ->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->company_name ?? $s->legal_name ?? 'N/A']);

        return Inertia::render('Admin/FundingLogs', [
            'logs' => $logs,
            'suppliers' => $suppliers,
            'filters' => $request->only(['date_from', 'date_to', 'supplier_id']),
        ]);
    }

    /**
     * Store a new funding log (append-only).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required','exists:suppliers,id'],
            'funding_id' => ['nullable','exists:fundings,id'],
            'transfer_date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'currency' => ['required','string','size:3'],
            'bank_reference' => ['nullable','string','max:190'],
            'internal_reference' => ['nullable','string','max:190'],
            'notes' => ['nullable','string','max:1000'],
        ]);

        $log = FundingLog::create([
            ...$validated,
            'recorded_by' => auth()->id(),
        ]);

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => FundingLog::class,
            'entity_id' => $log->id,
            'action' => 'created',
            'diff_json' => [
                'new_values' => [
                    'supplier_id' => $log->supplier_id,
                    'transfer_date' => $log->transfer_date->format('Y-m-d'),
                    'amount' => $log->amount,
                    'currency' => $log->currency,
                    'bank_reference' => $log->bank_reference,
                ],
            ],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return back()->with('success', 'Funding log created successfully.');
    }

    /**
     * Export funding logs as CSV.
     */
    public function export(Request $request)
    {
        $query = FundingLog::with(['supplier', 'recordedBy']);

        if ($request->filled('date_from')) {
            $query->where('transfer_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('transfer_date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $logs = $query->orderBy('transfer_date')->get();

        $filename = 'funding_logs_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Supplier', 'Transfer Date', 'Amount', 'Currency', 'Bank Reference', 'Internal Reference', 'Notes', 'Recorded By', 'Created At']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->supplier->company_name ?? $log->supplier->legal_name ?? 'N/A',
                    $log->transfer_date->format('Y-m-d'),
                    $log->amount,
                    $log->currency,
                    $log->bank_reference ?? '',
                    $log->internal_reference ?? '',
                    $log->notes ?? '',
                    $log->recordedBy->name ?? 'N/A',
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}












