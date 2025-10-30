<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        // Demo supplier for the seeded admin user
        Supplier::firstOrCreate(
            ['contact_email' => 'admin@example.com'],
            [
                'company_name' => 'Acme Trading',
                'legal_name' => 'Acme Trading LLC',
                'kyb_status' => 'pending',
                'country' => 'SA',
                'city' => 'Riyadh',
                'is_active' => false,
            ]
        );
    }
}


