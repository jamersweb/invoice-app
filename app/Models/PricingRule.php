<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenor_min','tenor_max','amount_min','amount_max','base_rate','vip_adjust','is_active'
    ];
}


