<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KybChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_type','document_type_id','is_required','expires_in_days','is_active'
    ];

    public function documentType()
    {
        return $this->belongsTo(\App\Models\DocumentType::class, 'document_type_id');
    }
}


