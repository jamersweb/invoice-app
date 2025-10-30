<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $fillable = [
        'agreement_template_id','version','signer_id','signed_at','signed_pdf','terms_snapshot_json','status'
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'terms_snapshot_json' => 'array',
    ];
}
