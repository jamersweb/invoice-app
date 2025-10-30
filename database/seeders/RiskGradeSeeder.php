<?php

namespace Database\Seeders;

use App\Models\RiskGrade;
use Illuminate\Database\Seeder;

class RiskGradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            [
                'code' => 'A+',
                'name' => 'Excellent',
                'description' => 'Highest credit quality, minimal risk',
                'default_rate_adjustment' => -0.01,
                'max_funding_limit' => 1000000,
                'max_tenor_days' => 120,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'A',
                'name' => 'Very Good',
                'description' => 'High credit quality, low risk',
                'default_rate_adjustment' => -0.005,
                'max_funding_limit' => 750000,
                'max_tenor_days' => 90,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'B+',
                'name' => 'Good',
                'description' => 'Good credit quality, moderate risk',
                'default_rate_adjustment' => 0,
                'max_funding_limit' => 500000,
                'max_tenor_days' => 60,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'code' => 'B',
                'name' => 'Average',
                'description' => 'Average credit quality, moderate risk',
                'default_rate_adjustment' => 0.005,
                'max_funding_limit' => 250000,
                'max_tenor_days' => 45,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'code' => 'C',
                'name' => 'Below Average',
                'description' => 'Below average credit quality, higher risk',
                'default_rate_adjustment' => 0.01,
                'max_funding_limit' => 100000,
                'max_tenor_days' => 30,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'code' => 'D',
                'name' => 'High Risk',
                'description' => 'High risk, requires special approval',
                'default_rate_adjustment' => 0.02,
                'max_funding_limit' => 50000,
                'max_tenor_days' => 15,
                'requires_approval' => true,
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($grades as $grade) {
            RiskGrade::firstOrCreate(['code' => $grade['code']], $grade);
        }
    }
}



