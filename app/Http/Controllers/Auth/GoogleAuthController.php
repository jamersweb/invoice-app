<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    /**
     * Check if Socialite is available
     */
    private function isSocialiteAvailable(): bool
    {
        return class_exists('Laravel\Socialite\Facades\Socialite');
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirect(): RedirectResponse
    {
        if (!$this->isSocialiteAvailable()) {
            return redirect()->route('apply.step1')
                ->with('error', 'Google OAuth is not configured. Please install Laravel Socialite: composer require laravel/socialite');
        }

        try {
            // Use dynamic class resolution to avoid fatal errors if Socialite isn't installed
            $socialiteClass = 'Laravel\Socialite\Facades\Socialite';
            $socialite = $socialiteClass::driver('google');
            return $socialite->redirect();
        } catch (\Exception $e) {
            Log::error('Google OAuth redirect error: ' . $e->getMessage());
            return redirect()->route('apply.step1')
                ->with('error', 'Unable to connect to Google. Please try again.');
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(): RedirectResponse
    {
        if (!$this->isSocialiteAvailable()) {
            return redirect()->route('apply.step1')
                ->with('error', 'Google OAuth is not configured.');
        }

        try {
            // Use dynamic class resolution to avoid fatal errors if Socialite isn't installed
            $socialiteClass = 'Laravel\Socialite\Facades\Socialite';
            $googleUser = $socialiteClass::driver('google')->user();
            
            // Check if user exists
            $user = User::where('email', $googleUser->getEmail())->first();
            
            if (!$user) {
                // Create new user from Google account
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'email_verified_at' => now(), // Google emails are already verified
                    'password' => null, // No password for OAuth users
                ]);

                // Create or update lead
                $lead = Lead::updateOrCreate(
                    ['company_email' => strtolower($googleUser->getEmail())],
                    [
                        'status' => 'new',
                        'verify_token' => Str::random(40),
                        'verified_at' => now(), // Mark as verified since Google email is verified
                    ]
                );

                // Create supplier profile linked to user
                Supplier::updateOrCreate(
                    ['contact_email' => $user->email],
                    [
                        'company_name' => $googleUser->getName(),
                        'kyb_status' => 'pending',
                    ]
                );

                // Assign Supplier role
                $user->assignRole('Supplier');
            } else {
                // Update email verification if not already verified
                if (!$user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }
            }

            // Login user
            Auth::login($user);

            // Check if supplier profile exists and redirect accordingly
            $supplier = Supplier::where('contact_email', $user->email)->first();
            
            if (!$supplier || !in_array($supplier->kyb_status, ['approved'])) {
                // Redirect to KYC/KYB onboarding
                return redirect()->route('onboarding.kyc');
            }

            return redirect()->route('dashboard', absolute: false);
        } catch (\Exception $e) {
            \Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect()->route('apply.step1')
                ->with('error', 'Unable to login with Google. Please try again.');
        }
    }
}
