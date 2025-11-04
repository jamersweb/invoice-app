<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Buyer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'legal_name', 'tax_registration_number', 'contact_email', 'contact_phone',
        'country', 'city', 'address', 'website', 'risk_grade', 'credit_limit',
        'payment_terms_days', 'metadata', 'is_active'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\Invoices\Models\Invoice::class, 'buyer_id');
    }

    public function riskGrade(): BelongsTo
    {
        return $this->belongsTo(RiskGrade::class, 'risk_grade', 'code');
    }

    public function expectedRepayments(): HasMany
    {
        return $this->hasMany(\App\Modules\Repayments\Models\ExpectedRepayment::class, 'buyer_id');
    }
}









