<?php

namespace App\Modules\Invoices\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Modules\Invoices\Requests\SubmitInvoiceRequest;
use App\Modules\Invoices\Resources\InvoiceResource;
use App\Modules\Invoices\Services\InvoiceSubmissionService;

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
            abort(422, 'Supplier profile not found. Please complete KYC/KYB onboarding first.');
        }

        // Check if supplier is KYB approved
        if ($supplier->kyb_status !== 'approved') {
            abort(422, 'Your supplier profile must be approved before submitting invoices. Current status: ' . $supplier->kyb_status);
        }

        // Check if supplier is active
        if (!$supplier->is_active) {
            abort(422, 'Your supplier account is not active. Please contact support.');
        }

        // Check if agreements are signed
        $hasAgreement = \App\Models\Agreement::where('status', 'signed')
            ->where('signer_id', $user->id)
            ->exists();

        if (!$hasAgreement) {
            abort(422, 'Required agreements must be signed before submitting invoices.');
        }

        // Ensure supplier_id in request matches the logged-in supplier
        if ($request->input('supplier_id') != $supplier->id) {
            abort(403, 'You can only submit invoices for your own supplier account.');
        }

        $invoice = $service->submit($request->validated() + ['file' => $request->file('file')]);
        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }
}


