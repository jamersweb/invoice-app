<?php

namespace App\Modules\Funding\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'total_amount', 'status', 'approved_by', 'executed_by', 'executed_at', 'created_by'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'executed_at' => 'datetime',
    ];
}


