<?php

namespace App\Modules\Invoices\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Modules\Invoices\Requests\SubmitInvoiceRequest;
use App\Modules\Invoices\Requests\BulkSubmitInvoiceRequest;
use App\Modules\Invoices\Resources\InvoiceResource;
use App\Modules\Invoices\Services\InvoiceSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoicesController extends Controller
{
    public function store(SubmitInvoiceRequest $request, InvoiceSubmissionService $service)
    {
        $this->authorize('create', \App\Modules\Invoices\Models\Invoice::class);

        // Get supplier for the logged-in user
        $user = $request->user();
        $supplier = Supplier::where('contact_email', $user->email)->first();

        // Check if supplier exists
        if (!$supplier) {
            throw ValidationException::withMessages(['profile' => 'Supplier profile not found. Please complete KYC/KYB onboarding first.']);
        }

        // Check if supplier is KYB approved
        if ($supplier->kyb_status !== 'approved') {
            throw ValidationException::withMessages(['kyb' => 'Your supplier profile must be approved before submitting invoices. Current status: ' . $supplier->kyb_status]);
        }

        // Check if supplier is active
        if (!$supplier->is_active) {
            throw ValidationException::withMessages(['active' => 'Your supplier account is not active. Please contact support.']);
        }

        // Agreement check removed as per request

        // Restrictive supplier_id check removed to fix 403 error

        $invoice = $service->submit(array_merge(
            $request->validated(),
            [
                'supplier_id' => $supplier->id, // Force current user's supplier ID
                'user_id' => $user->id // Link to specific user
            ]
        ));

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice submitted successfully.');
    }

    /**
     * Bulk submit multiple invoices at once
     */
    public function bulkStore(BulkSubmitInvoiceRequest $request, InvoiceSubmissionService $service)
    {
        $this->authorize('create', \App\Modules\Invoices\Models\Invoice::class);

        // Get supplier for the logged-in user
        $user = $request->user();
        $supplier = Supplier::where('contact_email', $user->email)->first();

        // Check if supplier exists
        if (!$supplier) {
            throw ValidationException::withMessages(['profile' => 'Supplier profile not found. Please complete KYC/KYB onboarding first.']);
        }

        // Check if supplier is KYB approved
        if ($supplier->kyb_status !== 'approved') {
            throw ValidationException::withMessages(['kyb' => 'Your supplier profile must be approved before submitting invoices. Current status: ' . $supplier->kyb_status]);
        }

        // Check if supplier is active
        if (!$supplier->is_active) {
            throw ValidationException::withMessages(['active' => 'Your supplier account is not active. Please contact support.']);
        }

        // Agreement check removed as per request

        $invoices = [];
        $errors = [];

        foreach ($request->input('invoices') as $index => $invoiceData) {
            try {
                // Restrictive supplier_id check removed to fix 403 error

                // Get file from request
                $fileKey = "invoices.{$index}.file";
                $file = $request->file($fileKey);

                $invoice = $service->submit(array_merge(
                    $invoiceData,
                    [
                        'file' => $file,
                        'supplier_id' => $supplier->id, // Force current user's supplier ID
                        'user_id' => $user->id // Link to specific user
                    ]
                ));
                $invoices[] = $invoice;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'invoice_number' => $invoiceData['invoice_number'] ?? 'N/A',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Send notification to admin about bulk submission
        if (count($invoices) > 0) {
            \App\Modules\Invoices\Events\BulkInvoicesSubmitted::dispatch($invoices, $supplier);
        }

        return response()->json([
            'success' => true,
            'submitted' => count($invoices),
            'failed' => count($errors),
            'invoices' => InvoiceResource::collection($invoices),
            'errors' => $errors,
        ], 201);
    }

    public function show($id)
    {
        $invoice = \App\Modules\Invoices\Models\Invoice::with(['buyer', 'attachments'])->findOrFail($id);

        $this->authorize('view', $invoice);

        return inertia('Invoices/Show', [
            'invoice' => new InvoiceResource($invoice)
        ]);
    }

    public function edit($id)
    {
        $invoice = \App\Modules\Invoices\Models\Invoice::with(['attachments'])->findOrFail($id);
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft' && $invoice->status !== 'under_review') {
            return redirect()->route('invoices.index')
                ->with('error', 'Only draft or under review invoices can be edited.');
        }

        return inertia('Invoices/Edit', [
            'invoice' => new InvoiceResource($invoice)
        ]);
    }

    public function update(Request $request, $id)
    {
        $invoice = \App\Modules\Invoices\Models\Invoice::findOrFail($id);
        $this->authorize('update', $invoice);

        if ($invoice->status !== 'draft' && $invoice->status !== 'under_review') {
            return redirect()->route('invoices.index')
                ->with('error', 'Only draft or under review invoices can be updated.');
        }

        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:3'],
            'due_date' => ['required', 'date'],
            'issue_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'bank_account_name' => ['nullable', 'string', 'max:191'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'bank_branch' => ['nullable', 'string', 'max:191'],
            'bank_iban' => ['nullable', 'string', 'max:191'],
            'bank_swift' => ['nullable', 'string', 'max:191'],
        ]);

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            if (is_array($files) && count($files) > 0) {
                // Update legacy path with the first file
                $path = \Illuminate\Support\Facades\Storage::disk('public')->putFile('invoices', $files[0]);
                $validated['file_path'] = $path;

                // Add to attachments
                foreach ($files as $file) {
                    $filePath = \Illuminate\Support\Facades\Storage::disk('public')->putFile('invoices', $file);
                    $invoice->attachments()->create([
                        'file_path' => $filePath,
                        'file_name' => $file->getClientOriginalName(),
                    ]);
                }
            }
        }

        $invoice->update($validated);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice updated successfully.');
    }
    
    public function deleteAttachment($id, $attachmentId)
    {
        $invoice = \App\Modules\Invoices\Models\Invoice::findOrFail($id);
        $this->authorize('update', $invoice);

        $attachment = $invoice->attachments()->findOrFail($attachmentId);

        // Delete file from storage
        \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();

        return back()->with('success', 'Attachment removed successfully.');
    }
}


