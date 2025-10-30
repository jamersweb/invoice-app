<?php

namespace App\Modules\Offers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'tenor_days',
        'discount_rate',
        'discount_amount',
        'admin_fee',
        'net_amount',
        'pricing_snapshot',
        'status',
        'issued_at',
        'expires_at',
        'responded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_rate' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'pricing_snapshot' => 'array',
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(\App\Modules\Invoices\Models\Invoice::class);
    }
}


