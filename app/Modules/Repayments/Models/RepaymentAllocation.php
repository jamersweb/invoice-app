<?php

namespace App\Modules\Repayments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'received_repayment_id', 'expected_repayment_id', 'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}


