<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign Supplier role
        $user->assignRole('Supplier');

        // Create supplier profile linked to user
        Supplier::updateOrCreate(
            ['contact_email' => $user->email],
            [
                'company_name' => $request->name,
                'kyb_status' => 'pending',
            ]
        );

        event(new Registered($user));

        // Generate OTP
        $otp = (string) rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new \App\Notifications\Auth\EmailVerificationOtpNotification($otp));

        Auth::login($user);

        return redirect()->route('verification.otp');
    }
}
