<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_supplier_can_create_invoice(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('submit_invoices');
        $this->assertTrue($user->can('create', Invoice::class));
    }

    public function test_analyst_can_update_invoice(): void
    {
        $user = User::factory()->create();
        $user->syncRoles('Analyst');
        $invoice = Invoice::create([
            'supplier_id' => 1,
            'buyer_id' => 2,
            'invoice_number' => 'INV-6000',
            'amount' => 100,
            'currency' => 'SAR',
            'due_date' => now()->addDays(10),
            'status' => 'under_review',
        ]);
        $this->assertTrue($user->can('update', $invoice));
    }
}


