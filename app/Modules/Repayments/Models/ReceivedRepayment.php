<?php

namespace App\Modules\Repayments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedRepayment extends Model
{
    use HasFactory;

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Buyer::class);
    }

    protected $fillable = [
        'buyer_id', 'amount', 'received_date', 'bank_reference', 'allocated_amount', 'unallocated_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'received_date' => 'date',
    ];
}


