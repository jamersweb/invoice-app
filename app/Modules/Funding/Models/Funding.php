<?php

namespace App\Modules\Funding\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funding extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'offer_id', 'batch_id', 'status', 'amount', 'funded_at', 'bank_reference', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'funded_at' => 'datetime',
    ];
}


