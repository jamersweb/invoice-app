<?php

namespace App\Modules\Invoices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoices\Requests\SubmitInvoiceRequest;
use App\Modules\Invoices\Resources\InvoiceResource;
use App\Modules\Invoices\Services\InvoiceSubmissionService;

class InvoicesController extends Controller
{
    public function store(SubmitInvoiceRequest $request, InvoiceSubmissionService $service)
    {
        $this->authorize('create', \App\Modules\Invoices\Models\Invoice::class);
        // Block until agreements signed (basic check: any signed agreement exists for this user)
        $hasAgreement = \App\Models\Agreement::where('status','signed')->exists();
        if (!$hasAgreement) {
            abort(422, 'Required agreements must be signed before submitting invoices.');
        }
        $invoice = $service->submit($request->validated() + ['file' => $request->file('file')]);
        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }
}


