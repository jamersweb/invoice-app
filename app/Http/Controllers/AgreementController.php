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
    public function index(): Response
    {
        $user = auth()->user();
        $templates = AgreementTemplate::all(['id','name','version']);
        
        $agreementsQuery = Agreement::with('template:id,name')->latest();
        
        if (!$user->hasAnyRole(['Admin', 'Analyst'])) {
            $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
            
            $agreementsQuery->where(function($q) use ($user, $supplier) {
                $q->where('signer_id', $user->id);
                if ($supplier) {
                    $q->orWhere('supplier_id', $supplier->id);
                }
            });
            
            // Also include agreements where status is not draft
            $agreementsQuery->where('status', '!=', 'draft');
        }

        $agreements = $agreementsQuery->get(['id','agreement_template_id','version','status','signed_at', 'signed_pdf', 'document_type']);
        
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
            'template_id' => ['nullable', 'exists:agreement_templates,id'],
            'agreement_id' => ['nullable', 'exists:agreements,id']
        ]);

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

        $service = app(\App\Services\ContractService::class);

        if ($request->filled('agreement_id')) {
            $agreement = Agreement::findOrFail($request->input('agreement_id'));
            if ($agreement->signer_id !== $user->id && !$user->hasRole('Admin')) {
                abort(403);
            }
            $service->sign($agreement);
        } else if ($request->filled('template_id')) {
            $template = AgreementTemplate::findOrFail($request->input('template_id'));
            $agreement = Agreement::create([
                'agreement_template_id' => $template->id,
                'version' => $template->version,
                'status' => 'Signed',
                'signer_id' => auth()->id() ?? 0,
                'signed_at' => now(),
                'terms_snapshot_json' => ['name' => $template->name, 'version' => $template->version],
            ]);

            $pdf = PDF::loadHTML($template->html);
            $path = 'agreements/'.$agreement->id.'-signed.pdf';
            Storage::disk('public')->makeDirectory('agreements');
            $pdf->save(storage_path('app/public/'.$path));
            $agreement->update(['signed_pdf' => $path]);
        } else {
            return back()->withErrors(['error' => 'Either template_id or agreement_id is required.']);
        }

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
            ],
        ]);

        return back()->with('success', 'Agreement signed');
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
