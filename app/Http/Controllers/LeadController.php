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
        // Ensure user is not authenticated (guest middleware should handle this, but adding extra safeguard)
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
        
        return Inertia::render('Apply/Step1');
    }

    /**
     * Store a newly created resource in storage (User Registration).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        // Create user account
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
        ]);

        // Create or update lead
        $lead = Lead::updateOrCreate(
            ['company_email' => strtolower($validated['email'])],
            [
                'status' => 'new',
                'verify_token' => Str::random(40),
            ]
        );

        // Create supplier profile linked to user
        \App\Models\Supplier::updateOrCreate(
            ['contact_email' => $user->email],
            [
                'company_name' => $validated['name'],
                'kyb_status' => 'pending',
            ]
        );

        // Assign Supplier role
        $user->assignRole('Supplier');

        // Send email verification
        event(new \Illuminate\Auth\Events\Registered($user));

        // Login user
        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('apply.step2', [
            'token' => $lead->verify_token,
            'email' => $user->email,
        ]);
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
        $token = $request->query('token');
        $email = $request->query('email');
        $verified = false;

        if ($token) {
            $lead = Lead::where('verify_token', $token)->first();
            if ($lead && $lead->verified_at) {
                $verified = true;
            }
        }

        return Inertia::render('Apply/Step2', [
            'token' => $token,
            'email' => $email ?? auth()->user()?->email,
            'verified' => $verified,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $lead = Lead::where('verify_token', $request->query('token'))->firstOrFail();
        
        // Mark lead as verified
        $lead->forceFill([
            'status' => 'verified',
            'verified_at' => now(),
        ])->save();

        // Mark user email as verified if user exists
        $user = \App\Models\User::where('email', $lead->company_email)->first();
        if ($user && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Login user if not already logged in
        if (!$request->user() && $user) {
            \Illuminate\Support\Facades\Auth::login($user);
        }

        return redirect()->route('apply.step2', [
            'token' => $lead->verify_token,
            'email' => $lead->company_email,
            'verified' => true,
        ])->with('verified', true);
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
