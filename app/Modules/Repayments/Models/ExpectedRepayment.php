<?php

namespace App\Modules\Repayments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpectedRepayment extends Model
{
    use HasFactory;

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Invoices\Models\Invoice::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Buyer::class);
    }

    // Get supplier through invoice
    public function getSupplierAttribute()
    {
        return $this->invoice->supplier ?? null;
    }

    protected $fillable = [
        'invoice_id', 'buyer_id', 'amount', 'due_date', 'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
    ];
}


