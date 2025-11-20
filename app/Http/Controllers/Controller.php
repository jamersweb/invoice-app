<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Get the appropriate dashboard route based on user role
     */
    protected function getDashboardRoute(): string
    {
        $user = Auth::user();
        
        if ($user->hasRole('Admin')) {
            return 'admin.dashboard';
        }
        
        if ($user->hasRole('Supplier')) {
            return 'supplier.dashboard';
        }
        
        // Default fallback
        return 'supplier.dashboard';
    }

    /**
     * Redirect to appropriate dashboard based on user role
     */
    protected function redirectToDashboard()
    {
        $user = Auth::user();
        
        if ($user->hasRole('Admin')) {
            return redirect()->route('admin.dashboard');
        }
        
        if ($user->hasRole('Supplier')) {
            $supplier = Supplier::where('contact_email', $user->email)->first();
            
            // If no supplier profile or KYB not approved, redirect to onboarding
            if (!$supplier || !in_array($supplier->kyb_status, ['approved'])) {
                return redirect()->route('onboarding.kyc');
            }
            
            return redirect()->route('supplier.dashboard');
        }
        
        // Default fallback
        return redirect()->route('supplier.dashboard');
    }
}
