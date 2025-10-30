<?php

namespace App\Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Spatie\ModelStates\HasStates;
// use App\Modules\Invoices\States\InvoiceStatus;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    // use HasStates;

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Buyer::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(\App\Modules\Offers\Models\Offer::class);
    }

    public function fundings(): HasMany
    {
        return $this->hasMany(\App\Modules\Funding\Models\Funding::class);
    }

    protected $fillable = [
        'supplier_id',
        'buyer_id',
        'invoice_number',
        'amount',
        'currency',
        'due_date',
        'status',
        'ocr_data',
        'ocr_confidence',
        'is_duplicate_flag',
        'file_path',
        'version',
        'assigned_to',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'dispute_notes',
        'written_off_at',
        'written_off_by',
        'write_off_reason',
        'priority',
    ];

    protected $casts = [
        'ocr_data' => 'array',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'is_duplicate_flag' => 'boolean',
        'status' => 'string',
        'reviewed_at' => 'datetime',
        'written_off_at' => 'datetime',
        'priority' => 'integer',
    ];
}


