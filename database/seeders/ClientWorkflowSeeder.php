<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Buyer;
use App\Models\DocumentType;
use App\Models\Lead;
use App\Models\Supplier;
use App\Models\User;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClientWorkflowSeeder extends Seeder
{
    /**
     * Seed test data for complete client workflow
     */
    public function run(): void
    {
        // Seed default app settings
        AppSetting::set(
            'reminder_email_days_before_due',
            7,
            'integer',
            'Number of days before repayment due date to send reminder email'
        );

        AppSetting::set(
            'reminder_email_check_frequency',
            'daily',
            'string',
            'How often to check for repayments due (daily or weekly)'
        );

        // Create test admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('Admin');
        }

        // Create test supplier user (for workflow testing)
        $supplierUser = User::firstOrCreate(
            ['email' => 'supplier@test.com'],
            [
                'name' => 'Test Supplier',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($supplierUser, 'assignRole')) {
            $supplierUser->assignRole('Supplier');
        }

        // Create lead for supplier
        $lead = Lead::firstOrCreate(
            ['company_email' => $supplierUser->email],
            [
                'status' => 'verified',
                'verify_token' => \Illuminate\Support\Str::random(40),
                'verified_at' => now(),
            ]
        );

        // Create supplier profile
        $supplier = Supplier::firstOrCreate(
            ['contact_email' => $supplierUser->email],
            [
                'company_name' => 'Test Supplier Company',
                'legal_name' => 'Test Supplier Company LLC',
                'tax_registration_number' => 'TAX123456',
                'kyb_status' => 'approved', // Approved for testing
                'country' => 'US',
                'city' => 'New York',
                'contact_phone' => '+1-555-123-4567',
            ]
        );

        // Create test buyer
        $buyer = Buyer::firstOrCreate(
            ['name' => 'Test Buyer Company'],
            [
                'contact_email' => 'buyer@test.com',
                'contact_phone' => '+1-555-987-6543',
                'address' => '123 Buyer Street',
                'city' => 'Los Angeles',
                'country' => 'US',
            ]
        );

        // Create test invoice (approved, ready for funding)
        $invoice = Invoice::firstOrCreate(
            ['invoice_number' => 'INV-2025-001'],
            [
                'supplier_id' => $supplier->id,
                'buyer_id' => $buyer->id,
                'amount' => 10000.00,
                'currency' => 'USD',
                'due_date' => now()->addDays(60),
                'status' => 'approved',
                'repayment_parts' => 3,
                'repayment_interval_days' => 30, // 30 days between repayments
                'extra_percentage' => 5.00,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        // Create expected repayments (after invoice due date)
        if ($invoice->wasRecentlyCreated) {
            $baseAmount = (float) $invoice->amount;
            $totalAmount = $baseAmount + ($baseAmount * $invoice->extra_percentage / 100);
            $partAmount = $totalAmount / $invoice->repayment_parts;

            $startDate = $invoice->due_date->copy();
            for ($i = 1; $i <= $invoice->repayment_parts; $i++) {
                $dueDate = $startDate->copy()->addDays($invoice->repayment_interval_days * $i);
                
                \App\Modules\Repayments\Models\ExpectedRepayment::create([
                    'invoice_id' => $invoice->id,
                    'buyer_id' => $buyer->id,
                    'amount' => $partAmount,
                    'due_date' => $dueDate,
                    'status' => 'open',
                ]);
            }
        }

        $this->command->info('Client workflow test data seeded successfully!');
        $this->command->info('Admin: admin@test.com / password');
        $this->command->info('Supplier: supplier@test.com / password');
    }
}
