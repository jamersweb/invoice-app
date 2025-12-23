<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'file_path',
        'file_name',
    ];

    public function invoice()
    {
        return $this->belongsTo(\App\Modules\Invoices\Models\Invoice::class);
    }
}
