<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\AuditEvent;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankController extends Controller
{
    /**
     * Display all supplier banking details (full fields, no masking).
     */
    public function index(): Response
    {
        $accounts = BankAccount::with('supplier')
            ->whereHas('supplier')
            ->latest()
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'supplier_id' => $account->supplier_id,
                    'supplier_name' => $account->supplier->company_name ?? $account->supplier->legal_name ?? 'N/A',
                    'account_name' => $account->account_name, // Full (decrypted automatically)
                    'iban' => $account->iban, // Full
                    'swift' => $account->swift,
                    'bank_name' => $account->bank_name,
                    'branch' => $account->branch,
                    'bank_letter_path' => $account->bank_letter_path,
                    'created_at' => $account->created_at,
                    'updated_at' => $account->updated_at,
                ];
            });

        return Inertia::render('Admin/Bank', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Update banking details (append-only audit).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $account = BankAccount::findOrFail($id);

        $validated = $request->validate([
            'account_name' => ['required','string','max:190'],
            'iban' => ['required','string','max:64'],
            'swift' => ['nullable','string','max:16'],
            'bank_name' => ['nullable','string','max:190'],
            'branch' => ['nullable','string','max:190'],
            'correction_note' => ['required','string','max:500'], // Required for edits
        ]);

        $oldValues = [
            'account_name' => $account->account_name,
            'iban' => $account->iban,
            'swift' => $account->swift,
            'bank_name' => $account->bank_name,
            'branch' => $account->branch,
        ];

        $account->update([
            'account_name' => $validated['account_name'],
            'iban' => $validated['iban'],
            'swift' => $validated['swift'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'branch' => $validated['branch'] ?? null,
        ]);

        // Log audit event with correction note
        AuditEvent::create([
            'actor_type' => \App\Models\User::class,
            'actor_id' => auth()->id(),
            'entity_type' => BankAccount::class,
            'entity_id' => $account->id,
            'action' => 'updated',
            'diff_json' => [
                'old_values' => $oldValues,
                'new_values' => [
                    'account_name' => $validated['account_name'],
                    'iban' => $validated['iban'],
                    'swift' => $validated['swift'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'branch' => $validated['branch'] ?? null,
                ],
                'correction_note' => $validated['correction_note'],
            ],
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return back()->with('success', 'Banking details updated. Audit event logged with correction note.');
    }
}





