<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Models\Supplier;
use App\Models\User;
use App\Modules\Invoices\Models\Invoice;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AgreementController extends Controller
{
    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    public function index()
    {
        return Inertia::render('Admin/Agreements', [
            'templates' => AgreementTemplate::all(['id', 'name', 'version']),
            'suppliers' => Supplier::all(['id', 'company_name', 'contact_email'])
        ]);
    }

    public function list(Request $request)
    {
        $query = Agreement::with(['template', 'invoice', 'supplier'])
            ->latest();

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function templates()
    {
        return response()->json(AgreementTemplate::all(['id', 'name', 'version']));
    }

    public function getSupplierInvoices(Supplier $supplier)
    {
        return response()->json($supplier->invoices()->latest()->get(['id', 'invoice_number', 'amount', 'currency', 'created_at']));
    }

    public function generateFromInvoice(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'template_id' => 'required|exists:agreement_templates,id',
            'variables' => 'required|array',
        ]);

        $supplier = Supplier::find($validated['supplier_id']);
        $user = User::where('email', $supplier->contact_email)->first();

        $agreement = $this->contractService->createDraft(
            $validated['template_id'],
            $user ? $user->id : auth()->id(),
            $validated['variables'],
            $validated['invoice_id']
        );

        $agreement->update(['supplier_id' => $supplier->id]);

        return response()->json([
            'message' => 'Contract draft generated successfully',
            'agreement' => $agreement
        ]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string',
            'category' => 'required|string'
        ]);

        $path = $request->file('file')->store('agreements', 'public');

        $agreement = Agreement::create([
            'supplier_id' => $validated['supplier_id'],
            'invoice_id' => $validated['invoice_id'],
            'document_type' => $validated['document_type'],
            'file_name' => $request->file('file')->getClientOriginalName(),
            'version' => 'N/A',
            'signed_pdf' => $path,
            'status' => 'Signed',
            'signed_at' => now(),
            'category' => $validated['category'],
            'notes' => $validated['notes'],
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'agreement' => $agreement
        ]);
    }

    public function send(Agreement $agreement)
    {
        $this->contractService->sendToCustomer($agreement);
        return response()->json(['message' => 'Contract sent to customer']);
    }

    public function status(Invoice $invoice)
    {
        $agreement = Agreement::where('invoice_id', $invoice->id)->latest()->first();
        return response()->json(['agreement' => $agreement]);
    }

    public function destroy(Agreement $agreement)
    {
        if ($agreement->signed_pdf) {
            Storage::disk('public')->delete($agreement->signed_pdf);
        }
        $agreement->delete();
        return response()->json(['ok' => true]);
    }
}
