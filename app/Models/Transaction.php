<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'customer',
        'customer_id',
        'date_of_transaction',
        'net_amount',
        'profit_margin',
        'disbursement_charges',
        'sales_cycle',
        'status',
        'notes',
    ];

    protected $casts = [
        'date_of_transaction' => 'date',
        'net_amount' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'disbursement_charges' => 'decimal:2',
        'sales_cycle' => 'integer',
    ];

    public function customerRelation(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function profitAllocations(): HasMany
    {
        return $this->hasMany(ProfitAllocation::class);
    }

    public function getExpectedProfitAttribute(): float
    {
        return ($this->net_amount * $this->profit_margin / 100) - $this->disbursement_charges;
    }
}

