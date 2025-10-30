<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccount extends Model
{
    protected $fillable = ['supplier_id','account_name','iban','swift','bank_name','branch','bank_letter_path'];

    protected $casts = [
        'account_name' => 'encrypted',
        'iban' => 'encrypted',
        'swift' => 'encrypted',
        'bank_name' => 'encrypted',
        'branch' => 'encrypted',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getMaskedIbanAttribute(): string
    {
        $plain = $this->attributes['iban'] ?? '';
        $len = strlen($plain);
        return $len > 4 ? str_repeat('*', max(0, $len - 4)).substr($plain, -4) : $plain;
    }

    public function getMaskedSwiftAttribute(): string
    {
        $plain = $this->attributes['swift'] ?? '';
        if (empty($plain)) return '';
        return strlen($plain) > 4 ? substr($plain, 0, 2).'****'.substr($plain, -2) : str_repeat('*', strlen($plain));
    }

    public function getMaskedAccountNameAttribute(): string
    {
        $plain = $this->attributes['account_name'] ?? '';
        $words = explode(' ', $plain);
        if (count($words) <= 1) {
            $len = strlen($plain);
            return $len > 2 ? substr($plain, 0, 2).str_repeat('*', max(0, $len - 2)) : str_repeat('*', $len);
        }
        return $words[0].' '.str_repeat('*', strlen(implode(' ', array_slice($words, 1))));
    }
}
