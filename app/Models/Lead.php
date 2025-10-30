<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'company_email',
        'company_phone',
        'company_name',
        'contact_name',
        'status',
        'verify_token',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];
}
