<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display audit logs with filters.
     */
    public function index(Request $request): Response
    {
        $query = AuditEvent::query();

        // Filters
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        if ($request->filled('correlation_id')) {
            $query->where('correlation_id', $request->correlation_id);
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->latest('created_at')
            ->paginate(50)
            ->through(function ($log) {
                $actor = null;
                if ($log->actor_type === User::class && $log->actor_id) {
                    $actor = User::find($log->actor_id);
                }

                return [
                    'id' => $log->id,
                    'actor' => $actor ? $actor->name . ' (' . $actor->email . ')' : 'System',
                    'entity_type' => class_basename($log->entity_type),
                    'entity_id' => $log->entity_id,
                    'action' => $log->action,
                    'correlation_id' => $log->correlation_id,
                    'ip' => $log->ip,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'diff_summary' => $this->summarizeDiff($log->diff_json),
                ];
            });

        $actors = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name . ' (' . $u->email . ')']);

        $entityTypes = AuditEvent::distinct('entity_type')
            ->pluck('entity_type')
            ->map(fn($t) => class_basename($t))
            ->sort()
            ->values();

        return Inertia::render('Admin/AuditLog', [
            'logs' => $logs,
            'actors' => $actors,
            'entity_types' => $entityTypes,
            'filters' => $request->only(['actor_id', 'entity_type', 'action', 'correlation_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Export audit logs as CSV.
     */
    public function export(Request $request)
    {
        $query = AuditEvent::query();

        // Apply same filters as index
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        if ($request->filled('correlation_id')) {
            $query->where('correlation_id', $request->correlation_id);
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->latest('created_at')->get();

        $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Actor', 'Entity Type', 'Entity ID', 'Action', 'Correlation ID', 'IP', 'Created At', 'Diff Summary']);

            foreach ($logs as $log) {
                $actor = null;
                if ($log->actor_type === User::class && $log->actor_id) {
                    $actor = User::find($log->actor_id);
                }

                fputcsv($file, [
                    $log->id,
                    $actor ? $actor->name . ' (' . $actor->email . ')' : 'System',
                    class_basename($log->entity_type),
                    $log->entity_id,
                    $log->action,
                    $log->correlation_id ?? '',
                    $log->ip ?? '',
                    $log->created_at->format('Y-m-d H:i:s'),
                    $this->summarizeDiff($log->diff_json),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show detailed audit log entry.
     */
    public function show(int $id)
    {
        $log = AuditEvent::findOrFail($id);

        $actor = null;
        if ($log->actor_type === User::class && $log->actor_id) {
            $actor = User::find($log->actor_id);
        }

        return response()->json([
            'id' => $log->id,
            'actor' => $actor ? [
                'id' => $actor->id,
                'name' => $actor->name,
                'email' => $actor->email,
            ] : null,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'action' => $log->action,
            'correlation_id' => $log->correlation_id,
            'ip' => $log->ip,
            'ua' => $log->ua,
            'diff_json' => $log->diff_json,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    private function summarizeDiff(?array $diff): string
    {
        if (!$diff) return '';

        $parts = [];
        if (isset($diff['old_values']) && is_array($diff['old_values'])) {
            $parts[] = 'Old: ' . count($diff['old_values']) . ' fields';
        }
        if (isset($diff['new_values']) && is_array($diff['new_values'])) {
            $parts[] = 'New: ' . count($diff['new_values']) . ' fields';
        }
        return implode(' | ', $parts) ?: 'No changes';
    }
}








