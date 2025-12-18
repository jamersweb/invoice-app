<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\Auth\EmailVerificationOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OtpVerificationController extends Controller
{
    /**
     * Display the OTP verification view.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('supplier.dashboard', absolute: false))
            : Inertia::render('Auth/VerifyEmailOtp', [
                'status' => session('status'),
                'otp' => app()->environment('local') ? $request->user()->otp : null,
            ]);
    }

    /**
     * Verify the email using OTP.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->otp === $request->otp && now()->lt($user->otp_expires_at)) {
            $user->markEmailAsVerified();
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            // Ensure user has a role (fix for users created during bug window)
            if ($user->roles->isEmpty()) {
                $user->assignRole('Supplier');
                // Ensure supplier profile exists too
                \App\Models\Supplier::updateOrCreate(
                    ['contact_email' => $user->email],
                    [
                        'company_name' => $user->name,
                        'kyb_status' => 'pending',
                    ]
                );
            }

            // Handle role-based redirection
            if ($user->hasRole('Admin')) {
                return redirect()->intended(route('admin.dashboard', absolute: false));
            }

            if ($user->hasRole('Supplier')) {
                $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
                if (!$supplier || !in_array($supplier->kyb_status, ['approved'])) {
                    return redirect()->route('onboarding.kyc');
                }
                return redirect()->intended(route('supplier.dashboard', absolute: false));
            }

            return redirect()->intended(route('supplier.dashboard', absolute: false))
                ->with('status', 'Email verified successfully!');
        }

        return back()->withErrors(['otp' => 'The provided OTP is invalid or has expired.']);
    }

    /**
     * Resend the verification OTP.
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $otp = (string) rand(100000, 999999);
        $request->user()->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $request->user()->notify(new EmailVerificationOtpNotification($otp));

        return back()->with('status', 'verification-link-sent');
    }
}
