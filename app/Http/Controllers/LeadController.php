<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Mail\LeadVerifyMail;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Apply/Step1');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_email' => ['required', 'email'],
            'company_phone' => ['nullable', 'string', 'max:30'],
        ]);

        $token = Str::random(40);

        $lead = Lead::updateOrCreate(
            ['company_email' => strtolower($validated['company_email'])],
            [
                'company_phone' => $validated['company_phone'] ?? null,
                'status' => 'new',
                'verify_token' => $token,
            ]
        );

        $verifyUrl = route('apply.verify', ['token' => $lead->verify_token]);
        Mail::to($lead->company_email)->send(new LeadVerifyMail($verifyUrl));

        return redirect()->route('apply.step2', ['token' => $lead->verify_token]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Lead $lead)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lead $lead)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function step2(Request $request): Response
    {
        return Inertia::render('Apply/Step2', [
            'token' => $request->query('token'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $lead = Lead::where('verify_token', $request->query('token'))->firstOrFail();
        $lead->forceFill([
            'status' => 'verified',
            'verified_at' => now(),
        ])->save();
        return redirect()->route('apply.step2', ['token' => $lead->verify_token]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:190'],
            'contact_name' => ['nullable', 'string', 'max:190'],
        ]);

        $lead->fill($validated)->save();

        // Sync Supplier profile basics (upsert by contact_email)
        Supplier::updateOrCreate(
            ['contact_email' => $lead->company_email],
            [
                'company_name' => $lead->company_name,
                'contact_phone' => $lead->company_phone,
            ]
        );
        return redirect()->route('apply.step3', ['token' => $lead->verify_token]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lead $lead)
    {
        //
    }
}
