<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\Investment;
use App\Models\Expense;
use App\Models\ProfitAllocation;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Services\ProfitAllocationService;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class WorkflowImplementationTest extends TestCase
{
       use RefreshDatabase;

       protected function setUp(): void
       {
              parent::setUp();
              $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
       }

       /** @test */
       public function chat_can_be_initiated_and_messages_sent()
       {
              $admin = User::factory()->create();
              $admin->assignRole('Admin');

              $customer = User::factory()->create();
              $customer->assignRole('Supplier');

              $conversation = ChatConversation::create([
                     'customer_id' => $customer->id,
                     'admin_id' => $admin->id,
                     'last_message_at' => now(),
              ]);

              Storage::fake('public');

              $response = $this->actingAs($customer)->post("/chat/{$conversation->id}/messages", [
                     'message' => 'Hello Admin, I have a question.',
                     'attachments' => [UploadedFile::fake()->create('docs.pdf', 100)],
              ]);

              $response->assertStatus(200);
              $this->assertDatabaseHas('chat_messages', [
                     'conversation_id' => $conversation->id,
                     'message' => 'Hello Admin, I have a question.',
                     'sender_id' => $customer->id,
              ]);

              $this->assertDatabaseHas('notifications', [
                     'recipient_id' => $admin->id,
                     'type' => 'new_chat_message',
              ]);
       }

       /** @test */
    public function profit_allocation_formula_is_correct()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $customer = \App\Models\Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'company_name' => 'Test Corp',
            'status' => 'active',
        ]);

        $transaction = Transaction::create([
            'transaction_number' => 'TRX-101',
            'customer' => $customer->name,
            'customer_id' => $customer->id,
            'date_of_transaction' => now(),
            'net_amount' => 10000.00,
            'profit_margin' => 10.00,
            'disbursement_charges' => 500.00,
            'sales_cycle' => 30,
            'status' => 'Ongoing',
        ]);

        // Add an approved expense linked to this transaction
        Expense::create([
            'category' => 'Fee',
            'description' => 'Processing for TRX-101', // Linked via description
            'amount' => 200.00,
            'currency' => 'AED',
            'expense_date' => now(),
            'status' => 'Approved',
        ]);

        // Add 2 investments
        $investor1 = User::factory()->create();
        Investment::create([
            'name' => 'Investor A',
            'investor_id' => $investor1->id,
            'amount' => 6000.00,
            'currency' => 'AED',
            'date' => now(),
            'status' => 'Confirmed',
        ]);

        $investor2 = User::factory()->create();
        Investment::create([
            'name' => 'Investor B',
            'investor_id' => $investor2->id,
            'amount' => 4000.00,
            'currency' => 'AED',
            'date' => now(),
            'status' => 'Confirmed',
        ]);

        $service = new ProfitAllocationService();
        $service->calculateAndAllocate($transaction->id);

        // Expected profit = 10000 - 500 - 200 = 9300
        // Investor A (60%) = 5580
        // Investor B (40%) = 3720

        $this->assertDatabaseHas('profit_allocations', [
            'transaction_id' => $transaction->id,
            'investor_name' => 'Investor A',
            'individual_profit' => 5580.00,
        ]);

        $this->assertDatabaseHas('profit_allocations', [
            'transaction_id' => $transaction->id,
            'investor_name' => 'Investor B',
            'individual_profit' => 3720.00,
        ]);
    }

    /** @test */
    public function contract_service_handles_draft_and_variable_replacement()
    {
        $customer = User::factory()->create();

        $template = AgreementTemplate::create([
            'name' => 'Service Agreement',
            'version' => '1.0',
            'html' => 'Hello {{customer_name}}, the amount is {{amount}}.',
        ]);

        $service = new ContractService();
        $variables = [
            'customer_name' => 'Test User',
            'amount' => '1000 AED',
            'invoice_id' => 1,
        ];

        $agreement = $service->createDraft($template->id, $customer->id, $variables);

        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'status' => 'Draft',
        ]);

        $processedHtml = $service->processTemplate($template->html, $variables);
        $this->assertEquals('Hello Test User, the amount is 1000 AED.', $processedHtml);

        // Test signing
        $service->sign($agreement);
        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'status' => 'Signed',
        ]);
    }

    /** @test */
    public function investment_status_update_triggers_notification()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $investor = User::factory()->create();
        $investor->assignRole('Investor');

        $customer = \App\Models\Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'company_name' => 'Test Corp',
            'status' => 'active',
        ]);

        $transaction = Transaction::create([
            'transaction_number' => 'TRX-102',
            'customer' => $customer->name,
            'customer_id' => $customer->id,
            'date_of_transaction' => now(),
            'net_amount' => 5000.00,
            'profit_margin' => 10.00,
            'sales_cycle' => 30,
            'status' => 'Ongoing',
        ]);

        $response = $this->actingAs($admin)->post('/forfaiting/investments', [
            'name' => $investor->name,
            'investor_id' => $investor->id,
            'transaction_id' => $transaction->id,
            'amount' => 2000.00,
            'currency' => 'AED',
            'date' => now()->toDateString(),
            'status' => 'Confirmed', // Should trigger notification
        ]);

        $this->assertDatabaseHas('notifications', [
            'recipient_id' => $investor->id,
            'type' => 'investment_confirmed',
        ]);
    }
}
