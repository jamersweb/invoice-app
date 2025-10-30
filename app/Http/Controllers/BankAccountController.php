<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class BankAccountController extends Controller
{
    /**
     * Display supplier banking details with masking.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $supplier = Supplier::where('contact_email', $user->email)->first();

        if (!$supplier) {
            return Inertia::render('Bank/Index', ['account' => null]);
        }

        $account = BankAccount::where('supplier_id', $supplier->id)->first();

        return Inertia::render('Bank/Index', [
            'account' => $account ? [
                'id' => $account->id,
                'account_name' => $account->masked_account_name, // Masked for non-admin
                'iban' => $account->masked_iban,
                'swift' => $account->masked_swift,
                'bank_name' => $account->bank_name ? '****' : null,
                'branch' => $account->branch ? '****' : null,
                'bank_letter_path' => $account->bank_letter_path,
                'has_bank_letter' => !empty($account->bank_letter_path),
            ] : null
        ]);
    }

    /**
     * Store banking details.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $supplier = Supplier::where('contact_email', $user->email)->first();

        if (!$supplier) {
            return back()->withErrors(['error' => 'Supplier profile not found. Please complete KYC first.']);
        }

        $validated = $request->validate([
            'account_name' => ['required','string','max:190'],
            'iban' => ['required','string','max:64'],
            'swift' => ['nullable','string','max:16'],
            'bank_name' => ['nullable','string','max:190'],
            'branch' => ['nullable','string','max:190'],
            'bank_letter' => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('bank_letter')) {
            Storage::disk('public')->makeDirectory('bank_letters');
            $path = $request->file('bank_letter')->store('bank_letters', 'public');
        }

        $oldAccount = BankAccount::where('supplier_id', $supplier->id)->first();
        $oldValues = $oldAccount ? [
            'account_name' => $oldAccount->account_name,
            'iban' => $oldAccount->iban,
            'swift' => $oldAccount->swift,
            'bank_name' => $oldAccount->bank_name,
            'branch' => $oldAccount->branch,
        ] : [];

        $account = BankAccount::updateOrCreate(
            ['supplier_id' => $supplier->id],
            [
                'account_name' => $validated['account_name'],
                'iban' => $validated['iban'],
                'swift' => $validated['swift'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'branch' => $validated['branch'] ?? null,
                'bank_letter_path' => $path ?? $oldAccount?->bank_letter_path,
            ]
        );

        // Log audit event
        \App\Models\AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => $user->id,
            'entity_type' => BankAccount::class,
            'entity_id' => $account->id,
            'action' => $oldAccount ? 'updated' : 'created',
            'diff_json' => [
                'old_values' => $oldValues,
                'new_values' => [
                    'account_name' => '***',
                    'iban' => '***',
                    'swift' => $validated['swift'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'branch' => $validated['branch'] ?? null,
                ],
            ],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return back()->with('success','Bank details saved successfully');
    }
}
