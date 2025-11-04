<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    protected $fillable = [
        'company_name', 'legal_name', 'tax_registration_number', 'contact_email', 'contact_phone',
        'business_type', 'industry', 'incorporation_date', 'country', 'state_province', 'city',
        'address', 'postal_code', 'website', 'kyb_status', 'grade', 'kyb_notes',
        'kyb_approved_at', 'kyb_approved_by', 'kyc_data', 'is_active'
    ];

    protected $casts = [
        'incorporation_date' => 'date',
        'kyb_approved_at' => 'datetime',
        'kyc_data' => 'array',
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'supplier_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\Invoices\Models\Invoice::class, 'supplier_id');
    }

    public function kybApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kyb_approved_by');
    }

    public function getKybStatusBadgeAttribute(): string
    {
        return match($this->kyb_status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'under_review' => 'bg-blue-100 text-blue-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function getCompletionPercentageAttribute(): int
    {
        $fields = [
            'company_name', 'legal_name', 'tax_registration_number', 'contact_email', 'contact_phone',
            'business_type', 'industry', 'incorporation_date', 'country', 'state_province', 'city',
            'address', 'postal_code'
        ];

        $completed = collect($fields)->filter(fn($field) => !empty($this->$field))->count();
        return round(($completed / count($fields)) * 100);
    }

    /**
     * Get onboarding progress status
     */
    public function getOnboardingProgress(): array
    {
        // Check if user exists for this supplier
        $user = \App\Models\User::where('email', $this->contact_email)->first();
        $signedAgreements = $user
            ? \App\Models\Agreement::where('status', 'signed')
                ->where('signer_id', $user->id)
                ->exists()
            : false;

        $progress = [
            'kyc_completed' => $this->completion_percentage >= 80,
            'documents_uploaded' => $this->documents()->whereIn('status', ['approved', 'pending_review', 'under_review'])->exists(),
            'kyb_approved' => $this->kyb_status === 'approved',
            'agreements_signed' => $signedAgreements,
            'bank_account_added' => \App\Models\BankAccount::where('supplier_id', $this->id)->exists(),
            'is_active' => $this->is_active,
        ];

        $completed = collect($progress)->filter(fn($val) => $val === true)->count();
        $total = count($progress);
        $percentage = round(($completed / $total) * 100);

        return [
            'percentage' => $percentage,
            'completed_steps' => $completed,
            'total_steps' => $total,
            'details' => $progress,
        ];
    }

    /**
     * Check if supplier can submit invoices
     */
    public function canSubmitInvoices(): bool
    {
        $user = \App\Models\User::where('email', $this->contact_email)->first();
        $hasSignedAgreement = $user
            ? \App\Models\Agreement::where('status', 'signed')
                ->where('signer_id', $user->id)
                ->exists()
            : false;

        return $this->kyb_status === 'approved'
            && $this->is_active
            && $hasSignedAgreement;
    }
}


