<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Document;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Mail;

class SupplierReviewController extends Controller
{
       /**
        * Display a listing of suppliers for review.
        */
       public function index(Request $request): Response
       {
              $query = Supplier::query();

              if ($request->filled('status')) {
                     $query->where('kyb_status', $request->status);
              }

              if ($request->filled('search')) {
                     $search = $request->search;
                     $query->where(function ($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%")
                                   ->orWhere('legal_name', 'like', "%{$search}%")
                                   ->orWhere('contact_email', 'like', "%{$search}%");
                     });
              }

              $suppliers = $query->orderBy('created_at', 'desc')
                     ->paginate(15)
                     ->withQueryString();

              return Inertia::render('Admin/Suppliers/Index', [
                     'suppliers' => $suppliers,
                     'filters' => $request->only(['status', 'search']),
              ]);
       }

       /**
        * Display the specified supplier for full review.
        */
       public function show(Supplier $supplier): Response
       {
              $supplier->load(['documents.documentType']);

              return Inertia::render('Admin/Suppliers/Show', [
                     'supplier' => $supplier,
              ]);
       }

       /**
        * Update the supplier's KYB status.
        */
       public function updateStatus(Request $request, Supplier $supplier)
       {
              $validated = $request->validate([
                     'status' => ['required', 'in:approved,rejected,under_review'],
                     'notes' => ['nullable', 'string', 'max:1000'],
                     'reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
              ]);

              $oldStatus = $supplier->kyb_status;

              $updateData = [
                     'kyb_status' => $validated['status'],
                     'kyb_notes' => $validated['notes'] ?? $supplier->kyb_notes,
              ];

              if ($validated['status'] === 'approved') {
                     $updateData['kyb_approved_at'] = now();
                     $updateData['kyb_approved_by'] = auth()->id();
                     $updateData['is_active'] = true;
              }

              $supplier->update($updateData);

              // Log audit event
              AuditEvent::create([
                     'actor_type' => \App\Models\User::class,
                     'actor_id' => auth()->id(),
                     'entity_type' => Supplier::class,
                     'entity_id' => $supplier->id,
                     'action' => 'supplier_status_updated',
                     'diff_json' => [
                            'old_values' => ['kyb_status' => $oldStatus],
                            'new_values' => [
                                   'kyb_status' => $validated['status'],
                                   'notes' => $validated['notes'] ?? null,
                                   'reason' => $validated['reason'] ?? null
                            ],
                     ],
              ]);

              // Send email notification (optional but recommended)
              // Mail::to($supplier->contact_email)->send(new SupplierStatusUpdated($supplier, $validated['status'], $validated['reason'] ?? null));

              return redirect()->route('admin.suppliers.index')->with('success', "Supplier status updated to {$validated['status']}.");
       }
}
