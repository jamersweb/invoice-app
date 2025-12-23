<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'investor_id',
        'transaction_id',
        'name',
        'amount',
        'currency',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function investor()
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

