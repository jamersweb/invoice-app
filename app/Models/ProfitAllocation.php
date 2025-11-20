<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfitAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'investor_name',
        'individual_profit',
        'profit_percentage',
        'deal_status',
        'allocation_date',
        'notes',
    ];

    protected $casts = [
        'individual_profit' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'allocation_date' => 'date',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}

