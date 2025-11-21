<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->hasVerifiedEmail()) {
            // If already verified, check next step
            if ($user->hasRole('Supplier')) {
                $supplier = \App\Models\Supplier::where('contact_email', $user->email)->first();
                if (!$supplier || !in_array($supplier->kyb_status, ['approved'])) {
                    return redirect()->route('onboarding.kyc')->with('verified', true);
                }
            }
            $dashboardRoute = $user->hasRole('Admin') ? 'admin.dashboard' : 'supplier.dashboard';
            return redirect()->intended(route($dashboardRoute, absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // After email verification, redirect suppliers to KYC/KYB
        if ($user->hasRole('Supplier')) {
            return redirect()->route('onboarding.kyc')->with('verified', true);
        }

        $dashboardRoute = $user->hasRole('Admin') ? 'admin.dashboard' : 'supplier.dashboard';
        return redirect()->intended(route($dashboardRoute, absolute: false).'?verified=1');
    }
}
