<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $fillable = [
        'agreement_template_id', 'invoice_id', 'supplier_id', 'version', 'signer_id', 'signed_at', 'signed_pdf', 
        'terms_snapshot_json', 'status', 'document_type', 'file_name', 'category', 'notes'
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'terms_snapshot_json' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(\App\Modules\Invoices\Models\Invoice::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function template()
    {
        return $this->belongsTo(AgreementTemplate::class, 'agreement_template_id');
    }
}
