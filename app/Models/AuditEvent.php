<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    protected $fillable = [
        'actor_type',
        'actor_id',
        'entity_type',
        'entity_id',
        'action',
        'diff_json',
        'ip',
        'ua',
        'correlation_id',
    ];

    protected $casts = [
        'diff_json' => 'array',
    ];
}
