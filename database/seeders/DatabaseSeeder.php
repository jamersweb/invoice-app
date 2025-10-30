<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed base roles and permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // Seed document types
        $this->call(DocumentTypeSeeder::class);

        // Seed agreement template
        $this->call(AgreementTemplateSeeder::class);

        // Seed a demo supplier linked to admin
        $this->call(SupplierSeeder::class);

        // Create or update an admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign Admin role if permission package is present
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('Admin');
        }
    }
}
