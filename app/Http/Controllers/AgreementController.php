<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AgreementTemplate;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Jobs\Agreements\GenerateAgreementPdf;
use App\Jobs\Agreements\SendForESignature;
use Illuminate\Support\Facades\Storage;

class AgreementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $templates = AgreementTemplate::all(['id','name','version']);
        $agreements = Agreement::latest()->get(['id','agreement_template_id','version','status','signed_at']);
        return Inertia::render('Agreements/Index', compact('templates','agreements'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'template_id' => ['required','exists:agreement_templates,id']
        ]);

        $template = AgreementTemplate::findOrFail($request->integer('template_id'));
        $agreement = Agreement::create([
            'agreement_template_id' => $template->id,
            'version' => $template->version,
            'status' => 'draft',
            'terms_snapshot_json' => ['name' => $template->name, 'version' => $template->version],
        ]);

        GenerateAgreementPdf::dispatchSync($agreement);
        return back()->with('success','Agreement created');
    }

    /**
     * Display the specified resource.
     */
    public function show(Agreement $agreement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Agreement $agreement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function sign(Request $request): RedirectResponse
    {
        $request->validate([
            'template_id' => ['required','exists:agreement_templates,id']
        ]);

        // Check if supplier is KYB approved before allowing agreement signing
        $user = auth()->user();
        $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();

        // Skip check for Admin/Analyst roles
        if (!$user->hasAnyRole(['Admin', 'Analyst'])) {
            if (!$supplier) {
                return back()->withErrors(['error' => 'Supplier profile not found. Please complete KYC/KYB onboarding first.']);
            }

            if ($supplier->kyb_status !== 'approved') {
                return back()->withErrors(['error' => 'Your supplier profile must be approved before signing agreements. Current status: ' . $supplier->kyb_status]);
            }
        }

        $template = AgreementTemplate::findOrFail($request->input('template_id'));
        $agreement = Agreement::create([
            'agreement_template_id' => $template->id,
            'version' => $template->version,
            'status' => 'signed',
            'signer_id' => auth()->id() ?? 0,
            'signed_at' => now(),
            'terms_snapshot_json' => ['name' => $template->name, 'version' => $template->version],
        ]);

        $pdf = PDF::loadHTML($template->html);
        $path = 'agreements/'.$agreement->id.'-signed.pdf';
        // Ensure target directory exists
        Storage::disk('public')->makeDirectory('agreements');
        $pdf->save(storage_path('app/public/'.$path));
        $agreement->update(['signed_pdf' => $path]);

        // Audit signer identity
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => Agreement::class,
            'entity_id' => $agreement->id,
            'action' => 'agreement_signed',
            'diff_json' => [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
                'signed_at' => now()->toISOString(),
                'template' => ['id' => $template->id, 'version' => $template->version],
            ],
        ]);

        return back()->with('success','Agreement signed');
    }

    public function send(Request $request): RedirectResponse
    {
        $request->validate([
            'agreement_id' => ['required','exists:agreements,id'],
            'recipient.email' => ['required','email'],
            'recipient.name' => ['required','string']
        ]);
        $agreement = Agreement::findOrFail($request->integer('agreement_id'));
        SendForESignature::dispatchSync($agreement, $request->input('recipient'));
        return back()->with('success','Agreement sent');
    }

    public function webhook(Request $request)
    {
        // Simple token validation (configure: services.esign.webhook_token)
        $token = config('services.esign.webhook_token');
        abort_if(!$token || $request->header('X-Webhook-Token') !== $token, 401);

        $agreementId = (int) $request->input('agreement_id');
        $agreement = Agreement::findOrFail($agreementId);

        // Provider sends a base64 pdf content in 'pdf_base64' (stubbed)
        if ($pdf = $request->input('pdf_base64')) {
            $path = 'agreements/'.$agreement->id.'-signed.pdf';
            Storage::disk('public')->put($path, base64_decode($pdf));
            $agreement->signed_pdf = $path;
        }
        $agreement->status = 'signed';
        $agreement->signed_at = now();
        $agreement->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Agreement $agreement)
    {
        //
    }
}
