<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Models\Invoice;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceReviewController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/InvoiceReviewQueue');
    }

    public function queue(Request $request)
    {
        $query = Invoice::query();

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['draft', 'under_review']);
        }

        // Filter by assigned reviewer
        if ($assigned = $request->query('assigned_to')) {
            if ($assigned === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $assigned);
            }
        } else {
            // Show unassigned first, then assigned to current user
            $query->orderByRaw('CASE WHEN assigned_to IS NULL THEN 0 WHEN assigned_to = ? THEN 1 ELSE 2 END', [auth()->id()]);
        }

        // Priority sorting
        if ($request->query('sort') === 'priority') {
            $dir = $request->query('dir', 'desc');
            $query->orderBy('priority', $dir);
        }

        // Age filter
        if ($age = $request->query('age')) {
            if (preg_match('/^(\d+)([hd])$/', $age, $m)) {
                $val = (int)$m[1];
                $col = $m[2] === 'h' ? now()->subHours($val) : now()->subDays($val);
                $query->where('created_at', '<=', $col);
            }
        }

        $query->orderBy('created_at');
        $invoices = $query->with(['supplier', 'buyer'])->paginate(20);

        return response()->json($invoices);
    }

    public function claim(Invoice $invoice)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);

        $old = ['assigned_to' => $invoice->assigned_to];
        $invoice->assigned_to = auth()->id();
        $invoice->save();

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'invoice_claimed',
            'diff_json' => ['old' => $old, 'new' => ['assigned_to' => $invoice->assigned_to]],
            'ip' => request()->ip(),
            'ua' => request()->userAgent(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function approve(Request $request, Invoice $invoice)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);

        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $oldStatus = $invoice->status;

        $invoice->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['notes'] ?? null,
            'priority' => $validated['priority'] ?? $invoice->priority,
        ]);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'invoice_approved',
            'diff_json' => [
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'approved', 'reviewed_by' => auth()->id()],
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Invoice approved']);
    }

    public function reject(Request $request, Invoice $invoice)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin', 'Analyst']), 403);

        $validated = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $oldStatus = $invoice->status;

        $invoice->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['notes'],
        ]);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'invoice_rejected',
            'diff_json' => [
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'rejected', 'reviewed_by' => auth()->id()],
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Invoice rejected']);
    }

    public function addDisputeNote(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $invoice->dispute_notes = ($invoice->dispute_notes ? $invoice->dispute_notes . "\n\n" : '') . 
            now()->toDateTimeString() . " - " . $validated['notes'];
        $invoice->save();

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'dispute_note_added',
            'diff_json' => ['notes' => $validated['notes']],
        ]);

        return response()->json(['ok' => true]);
    }

    public function writeOff(Request $request, Invoice $invoice)
    {
        abort_unless(auth()->user()?->hasAnyRole(['Admin']), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $oldStatus = $invoice->status;

        $invoice->update([
            'status' => 'written_off',
            'written_off_at' => now(),
            'written_off_by' => auth()->id(),
            'write_off_reason' => $validated['reason'],
        ]);

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'invoice_written_off',
            'diff_json' => [
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'written_off', 'reason' => $validated['reason']],
            ],
        ]);

        return response()->json(['ok' => true]);
    }
}







