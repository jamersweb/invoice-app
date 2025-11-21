<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Check user role and redirect to appropriate dashboard
        $user = Auth::user();
        
        // Admin users go to admin dashboard
        if ($user->hasRole('Admin')) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }
        
        // Supplier users - check onboarding status
        if ($user->hasRole('Supplier')) {
            // Step 1: Check email verification
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }
            
            // Step 2: Check KYC/KYB completion
            $supplier = Supplier::where('contact_email', $user->email)->first();
            if (!$supplier || !in_array($supplier->kyb_status, ['approved'])) {
                return redirect()->route('onboarding.kyc');
            }
            
            // All checks passed - redirect to supplier dashboard
            return redirect()->intended(route('supplier.dashboard', absolute: false));
        }

        // Default fallback - redirect to supplier dashboard
        return redirect()->intended(route('supplier.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
