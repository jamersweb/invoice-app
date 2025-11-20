<?php

namespace App\Http\Controllers\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Modules\Invoices\Requests\SubmitInvoiceRequest;
use App\Modules\Invoices\Services\InvoiceSubmissionService;
use App\Modules\Invoices\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkInvoiceController extends Controller
{
    public function store(Request $request, InvoiceSubmissionService $service)
    {
        $this->authorize('create', \App\Modules\Invoices\Models\Invoice::class);

        $user = $request->user();
        $supplier = Supplier::where('contact_email', $user->email)->first();

        if (!$supplier) {
            abort(422, 'Supplier profile not found. Please complete KYC/KYB onboarding first.');
        }

        if ($supplier->kyb_status !== 'approved') {
            abort(422, 'Your supplier profile must be approved before submitting invoices.');
        }

        $validated = $request->validate([
            'invoices' => ['required', 'array', 'min:1', 'max:50'],
            'invoices.*.buyer_id' => ['required', 'integer', 'exists:buyers,id'],
            'invoices.*.invoice_number' => ['required', 'string', 'max:255'],
            'invoices.*.amount' => ['required', 'numeric', 'min:0.01'],
            'invoices.*.currency' => ['required', 'string', 'max:3'],
            'invoices.*.due_date' => ['required', 'date'],
            'invoices.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $invoices = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($validated['invoices'] as $index => $invoiceData) {
                try {
                    $invoiceData['supplier_id'] = $supplier->id;
                    $invoiceData['file'] = $invoiceData['file'] ?? null;
                    
                    $invoice = $service->submit($invoiceData);
                    $invoices[] = $invoice;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'invoice_number' => $invoiceData['invoice_number'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (empty($invoices) && !empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'All invoices failed to submit',
                    'errors' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($invoices) . ' invoice(s) submitted successfully',
                'invoices' => InvoiceResource::collection(collect($invoices)),
                'errors' => $errors,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit invoices',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

