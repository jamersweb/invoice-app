<?php

namespace Database\Seeders;

use App\Models\AgreementTemplate;
use Illuminate\Database\Seeder;

class AgreementTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AgreementTemplate::firstOrCreate(
            ['name' => 'Master Agreement', 'version' => 'v1'],
            ['html' => '<h1>Master Agreement v1</h1><p>Terms...</p>']
        );
    }
}
