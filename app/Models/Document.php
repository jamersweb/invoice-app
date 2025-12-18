<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'document_type_id',
        'owner_type',
        'owner_id',
        'supplier_id',
        'status',
        'expiry_at',
        'file_path',
        'review_notes',
        'assigned_to',
        'reviewed_by',
        'reviewed_at',
        'priority',
        'vip',
    ];

    protected $casts = [
        'expiry_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'vip' => 'boolean',
    ];

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}
