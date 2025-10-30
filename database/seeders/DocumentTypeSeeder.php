<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Trade License', 'required' => true, 'expires_in_days' => 365],
            ['name' => 'Memorandum of Association', 'required' => true, 'expires_in_days' => null],
            ['name' => 'Owner ID (Passport/EID)', 'required' => true, 'expires_in_days' => 365],
            ['name' => 'Bank Letter', 'required' => true, 'expires_in_days' => null],
        ];

        foreach ($types as $t) {
            DocumentType::firstOrCreate(['name' => $t['name']], $t);
        }
    }
}
