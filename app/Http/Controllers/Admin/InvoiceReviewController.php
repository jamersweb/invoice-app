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
        $invoices = $query->with(['supplier:id,company_name,legal_name,contact_email', 'buyer:id,name', 'attachments'])->paginate(20);

        return response()->json($invoices);
    }

    public function destroy(Invoice $invoice)
    {
        abort_unless(auth()->user()?->hasRole('Admin'), 403);
        
        $invoice->delete();
        
        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'invoice_deleted',
            'diff_json' => ['invoice_number' => $invoice->invoice_number],
        ]);

        return response()->json(['success' => true]);
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
            'repayment_parts' => ['nullable', 'integer', 'min:1', 'max:12'], // 1-12 parts
            'repayment_interval_days' => ['nullable', 'integer', 'min:1'], // Days between repayments (e.g., 30, 60, 90)
            'extra_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'], // Extra interest percentage
        ]);

        $oldStatus = $invoice->status;

        $invoice->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['notes'] ?? null,
            'priority' => $validated['priority'] ?? $invoice->priority,
            'repayment_parts' => $validated['repayment_parts'] ?? null,
            'repayment_interval_days' => $validated['repayment_interval_days'] ?? null,
            'extra_percentage' => $validated['extra_percentage'] ?? 0,
        ]);

        // Create repayment schedule if repayment_parts and repayment_interval_days are set
        $repaymentParts = null;
        if (isset($validated['repayment_parts']) && $validated['repayment_parts'] > 0 
            && isset($validated['repayment_interval_days']) && $validated['repayment_interval_days'] > 0) {
            $repaymentParts = $validated['repayment_parts'];
            $this->createRepaymentSchedule(
                $invoice, 
                $validated['repayment_parts'], 
                $validated['repayment_interval_days'],
                $validated['extra_percentage'] ?? 0
            );
        }

        // Send notification to supplier
        $notificationService = new \App\Services\InvoiceStatusNotificationService();
        $notificationService->notifyStatusChange($invoice, $oldStatus, 'approved');
        
        // If repayment schedule was created, send additional notification
        if ($repaymentParts) {
            $notificationService->notifyRepaymentScheduleCreated($invoice, $repaymentParts);
        }

        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'invoice_approved',
            'diff_json' => [
                'old_values' => ['status' => $oldStatus],
                'new_values' => [
                    'status' => 'approved',
                    'reviewed_by' => auth()->id(),
                    'repayment_parts' => $validated['repayment_parts'] ?? null,
                    'extra_percentage' => $validated['extra_percentage'] ?? 0,
                ],
            ],
        ]);

        return response()->json(['success' => true, 'message' => 'Invoice approved']);
    }

    private function createRepaymentSchedule(Invoice $invoice, int $parts, int $intervalDays, float $extraPercentage): void
    {
        // Calculate total amount with extra percentage
        $baseAmount = (float) $invoice->amount;
        $totalAmount = $baseAmount + ($baseAmount * $extraPercentage / 100);
        $partAmount = $totalAmount / $parts;

        // Delete existing expected repayments for this invoice
        \App\Modules\Repayments\Models\ExpectedRepayment::where('invoice_id', $invoice->id)->delete();

        // Create repayment schedule - repayments occur AFTER invoice due date
        // Start from invoice due date + interval days
        $startDate = $invoice->due_date->copy();
        
        for ($i = 1; $i <= $parts; $i++) {
            // Each repayment is due after the invoice due date, spaced by interval days
            $dueDate = $startDate->copy()->addDays($intervalDays * $i);
            
            \App\Modules\Repayments\Models\ExpectedRepayment::create([
                'invoice_id' => $invoice->id,
                'buyer_id' => $invoice->buyer_id,
                'amount' => $partAmount,
                'due_date' => $dueDate,
                'status' => 'open',
            ]);
        }
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

        // Send notification to supplier
        $notificationService = new \App\Services\InvoiceStatusNotificationService();
        $notificationService->notifyStatusChange($invoice, $oldStatus, 'rejected');

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










