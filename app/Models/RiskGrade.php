<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskGrade extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'default_rate_adjustment', 'max_funding_limit',
        'max_tenor_days', 'requires_approval', 'is_active', 'sort_order'
    ];

    protected $casts = [
        'default_rate_adjustment' => 'decimal:4',
        'max_funding_limit' => 'decimal:2',
        'max_tenor_days' => 'integer',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function buyers(): HasMany
    {
        return $this->hasMany(Buyer::class, 'risk_grade', 'code');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'grade', 'code');
    }
}






