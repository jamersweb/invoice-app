<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'view_leads',
            'manage_suppliers',
            'review_documents',
            'issue_offers',
            'accept_offers',
            'record_fundings',
            'manage_pricing',
            'view_audit',
            'manage_users',
            'submit_invoices',
            'view_offers',
            'view_statements',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // Get all permissions for Admin role (dynamic - includes any new permissions)
        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();

        $roles = [
            'Admin' => $allPermissions, // full access to all permissions
            'Analyst' => [
                'view_leads', 'manage_suppliers', 'review_documents', 'manage_pricing', 'view_audit'
            ],
            'Collector' => [
                'record_fundings', 'view_statements', 'view_audit'
            ],
            'Supplier' => [
                'submit_invoices', 'view_offers', 'accept_offers', 'view_statements'
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
