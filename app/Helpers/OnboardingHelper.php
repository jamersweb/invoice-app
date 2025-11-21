<?php

namespace App\Helpers;

use App\Models\Supplier;
use App\Models\User;

class OnboardingHelper
{
    /**
     * Check if supplier needs email verification
     */
    public static function needsEmailVerification(User $user): bool
    {
        return !$user->hasVerifiedEmail();
    }

    /**
     * Check if supplier needs KYC/KYB completion
     */
    public static function needsKycKyb(User $user): bool
    {
        if (!$user->hasRole('Supplier')) {
            return false;
        }

        $supplier = Supplier::where('contact_email', $user->email)->first();
        
        // If no supplier profile exists, needs KYC
        if (!$supplier) {
            return true;
        }

        // If KYB is not approved, needs KYC
        if (!in_array($supplier->kyb_status, ['approved'])) {
            return true;
        }

        return false;
    }

    /**
     * Get the next step for supplier onboarding
     */
    public static function getNextOnboardingStep(User $user): ?string
    {
        if (!$user->hasRole('Supplier')) {
            return null;
        }

        // Step 1: Email verification
        if (self::needsEmailVerification($user)) {
            return 'email_verification';
        }

        // Step 2: KYC/KYB
        if (self::needsKycKyb($user)) {
            return 'kyc_kyb';
        }

        // All done
        return null;
    }

    /**
     * Get redirect route for next onboarding step
     */
    public static function getOnboardingRedirect(User $user): string
    {
        $step = self::getNextOnboardingStep($user);

        return match($step) {
            'email_verification' => route('verification.notice'),
            'kyc_kyb' => route('onboarding.kyc'),
            default => route('supplier.dashboard'),
        };
    }
}

