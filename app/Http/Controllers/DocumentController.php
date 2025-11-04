<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\AuditEvent;
use App\Models\DocumentType;
use App\Services\Kyb\AssignmentService;
use App\Services\Kyb\RiskGradingService;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupplierWelcomeMail;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(): Response
    {
        $docs = Document::query()
            ->when(!auth()->user()?->hasRole(['Admin','Analyst']), function ($q) {
                $q->where('owner_type', 'supplier')->where('owner_id', auth()->id() ?? 0);
            })
            ->latest()
            ->paginate(15, ['id','document_type_id','status','file_path','created_at','owner_id','owner_type']);

        return Inertia::render('Documents/Index', [
            'documents' => $docs,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Documents/Upload');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'expiry_at' => ['nullable', 'date'],
        ]);

        // Ensure supplier profile exists before allowing document upload
        $user = $request->user();
        $supplier = Supplier::where('contact_email', $user->email)->first();

        if (!$supplier) {
            return back()->withErrors(['error' => 'Supplier profile not found. Please complete KYC/KYB onboarding first.']);
        }

        $docType = DocumentType::findOrFail($validated['document_type_id']);
        if ($docType->required && empty($validated['expiry_at']) && in_array(strtolower($docType->name), ['commercial registration','vat certificate'])) {
            return back()->withErrors(['expiry_at' => 'Expiry date is required for this document type.']);
        }

        $path = $request->file('file')->store('documents', 'public');

        $status = 'pending_review';
        $assignedTo = app(AssignmentService::class)->nextAnalystId();

        Document::create([
            'document_type_id' => $validated['document_type_id'],
            'owner_type' => 'supplier',
            'owner_id' => auth()->id() ?? 0,
            'supplier_id' => $supplier->id,
            'status' => $status,
            'expiry_at' => $validated['expiry_at'] ?? null,
            'file_path' => $path,
            'review_notes' => $assignedTo ? 'Assigned to analyst #'.$assignedTo : null,
        ]);

        return back()->with('success', 'Document uploaded');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('review', $document);
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $document->status;
        if ($validated['action'] === 'approve') {
            $document->status = 'approved';
        } else {
            $document->status = 'rejected';
        }
        $document->review_notes = $validated['review_notes'] ?? null;
        $document->reviewed_by = auth()->id();
        $document->reviewed_at = now();
        $document->save();

        AuditEvent::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'action' => 'document_reviewed',
            'diff_json' => ['old' => ['status' => $oldStatus], 'new' => ['status' => $document->status]],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        // After each approval, check if supplier is fully approved and send welcome
        if ($document->status === 'approved') {
            $ownerId = $document->owner_id;
            $allApproved = Document::where('owner_type', 'supplier')->where('owner_id', $ownerId)->where('status', 'approved')->count() >= 3;
            if ($allApproved) {
                $grade = app(RiskGradingService::class)->gradeForSupplier($ownerId);
                $supplier = Supplier::firstOrCreate(['id' => $ownerId], []);
                $old = ['kyb_status' => $supplier->kyb_status, 'grade' => $supplier->grade];
                $supplier->kyb_status = 'approved';
                $supplier->grade = $grade;
                $supplier->save();
                AuditEvent::create([
                    'actor_type' => 'user',
                    'actor_id' => auth()->id(),
                    'entity_type' => Supplier::class,
                    'entity_id' => $supplier->id,
                    'action' => 'supplier_kyb_approved',
                    'diff_json' => ['old' => $old, 'new' => ['kyb_status' => $supplier->kyb_status, 'grade' => $supplier->grade]],
                    'ip' => $request->ip(),
                    'ua' => $request->userAgent(),
                ]);
                $recipient = $supplier->contact_email ?: 'admin@example.com';
                try {
                    Mail::to($recipient)->locale(app()->getLocale())->send(new SupplierWelcomeMail($supplier->company_name ?: ('Supplier #'.$supplier->id)));
                } catch (\Throwable $e) {}
            }
        }

        return back()->with('success', 'Document '.$document->status);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        //
    }
}
