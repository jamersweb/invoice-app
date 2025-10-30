<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id','document_type_id','note','requested_by','requested_at','resolved_at'
    ];
}


