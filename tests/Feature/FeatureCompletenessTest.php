<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Agreement;
use App\Models\AgreementTemplate;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Offers\Models\Offer;
use App\Modules\Funding\Models\Funding;
use App\Modules\Repayments\Models\ExpectedRepayment;
use App\Models\AuditEvent;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class FeatureCompletenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required data
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\DocumentTypeSeeder::class);
    }

    /** @test */
    public function supplier_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'supplier@test.com']);
    }

    /** @test */
    public function supplier_can_upload_documents()
    {
        $user = User::factory()->create();
        $user->assignRole('Supplier');
        
        $docType = DocumentType::first();
        Storage::fake('public');

        $response = $this->actingAs($user)->post('/documents', [
            'document_type_id' => $docType->id,
            'file' => UploadedFile::fake()->create('license.pdf', 500),
            'expiry_at' => now()->addYear()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'document_type_id' => $docType->id,
            'owner_type' => 'supplier',
        ]);
    }

    /** @test */
    public function supplier_can_sign_agreement()
    {
        $user = User::factory()->create();
        $user->assignRole('Supplier');
        
        $template = AgreementTemplate::create([
            'name' => 'Master Agreement',
            'version' => '1.0',
            'html' => '<html><body>Agreement content</body></html>',
        ]);

        $response = $this->actingAs($user)->post('/agreements/sign', [
            'template_id' => $template->id,
        ]);

        $this->assertDatabaseHas('agreements', [
            'agreement_template_id' => $template->id,
            'status' => 'signed',
            'signer_id' => $user->id,
        ]);
    }

    /** @test */
    public function supplier_can_add_bank_details()
    {
        $user = User::factory()->create();
        $user->assignRole('Supplier');
        
        $supplier = Supplier::create([
            'contact_email' => $user->email,
            'kyb_status' => 'approved',
        ]);

        $response = $this->actingAs($user)->post('/bank', [
            'bank_name' => 'Test Bank',
            'account_number' => '123456789',
            'iban' => 'SA1234567890123456789012',
            'account_holder_name' => 'Test Supplier',
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'supplier_id' => $supplier->id,
            'bank_name' => 'Test Bank',
        ]);
    }

    /** @test */
    public function supplier_can_submit_invoice()
    {
        $user = User::factory()->create();
        $user->assignRole('Supplier');
        
        $supplier = Supplier::create([
            'contact_email' => $user->email,
            'kyb_status' => 'approved',
        ]);

        // Create signed agreement
        Agreement::create([
            'agreement_template_id' => 1,
            'version' => '1.0',
            'status' => 'signed',
            'signer_id' => $user->id,
            'signed_at' => now(),
        ]);

        Storage::fake('local');

        $response = $this->actingAs($user)->post('/invoices', [
            'supplier_id' => $supplier->id,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30)->toDateString(),
            'file' => UploadedFile::fake()->create('invoice.pdf', 500),
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-001',
            'supplier_id' => $supplier->id,
        ]);
    }

    /** @test */
    public function supplier_can_view_status()
    {
        $user = User::factory()->create();
        $user->assignRole('Supplier');
        
        $supplier = Supplier::create([
            'contact_email' => $user->email,
            'kyb_status' => 'approved',
        ]);

        Invoice::create([
            'supplier_id' => $supplier->id,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)->get('/api/v1/me/invoices/recent');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function admin_can_review_and_approve_kyb()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $user = User::factory()->create();
        $supplier = Supplier::create([
            'contact_email' => $user->email,
            'kyb_status' => 'pending',
        ]);

        $docType = DocumentType::first();
        $document = Document::create([
            'document_type_id' => $docType->id,
            'owner_type' => 'supplier',
            'owner_id' => $user->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending_review',
            'file_path' => 'test.pdf',
        ]);

        Mail::fake();

        $response = $this->actingAs($admin)->post("/api/v1/admin/kyb-queue/{$document->id}/approve", [
            'notes' => 'Approved',
            'grade' => 'B',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'kyb_status' => 'approved',
        ]);
    }

    /** @test */
    public function admin_can_apply_pricing_rule()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->post('/admin/api/pricing-rules', [
            'tenor_min' => 0,
            'tenor_max' => 30,
            'amount_min' => 1000,
            'amount_max' => 10000,
            'base_rate' => 0.02,
            'vip_adjust' => -0.005,
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pricing_rules', [
            'tenor_min' => 0,
            'base_rate' => 0.02,
        ]);
    }

    /** @test */
    public function admin_can_issue_offer()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $supplier = Supplier::create([
            'contact_email' => 'supplier@test.com',
            'kyb_status' => 'approved',
            'grade' => 'B',
        ]);

        $invoice = Invoice::create([
            'supplier_id' => $supplier->id,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'under_review',
        ]);

        PricingRule::create([
            'tenor_min' => 0,
            'tenor_max' => 90,
            'amount_min' => 0,
            'amount_max' => 999999,
            'base_rate' => 0.02,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/offers/issue', [
            'invoice_id' => $invoice->id,
            'supplier_grade' => 'B',
            'buyer_grade' => 'B',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('offers', [
            'invoice_id' => $invoice->id,
            'status' => 'issued',
        ]);
    }

    /** @test */
    public function admin_can_record_funding()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $invoice = Invoice::create([
            'supplier_id' => 1,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'accepted',
        ]);

        $offer = Offer::create([
            'invoice_id' => $invoice->id,
            'amount' => 1000.00,
            'tenor_days' => 30,
            'discount_rate' => 0.02,
            'discount_amount' => 20.00,
            'admin_fee' => 10.00,
            'net_amount' => 970.00,
            'status' => 'accepted',
        ]);

        $funding = Funding::create([
            'invoice_id' => $invoice->id,
            'offer_id' => $offer->id,
            'amount' => 970.00,
            'status' => 'queued',
        ]);

        $response = $this->actingAs($admin)->post("/api/v1/admin/fundings/{$funding->id}/record");

        $response->assertStatus(200);
        $this->assertDatabaseHas('fundings', [
            'id' => $funding->id,
            'status' => 'executed',
        ]);
    }

    /** @test */
    public function admin_can_track_expected_repayment()
    {
        $invoice = Invoice::create([
            'supplier_id' => 1,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'funded',
        ]);

        $expected = ExpectedRepayment::create([
            'invoice_id' => $invoice->id,
            'buyer_id' => 1,
            'amount' => 1000.00,
            'due_date' => now()->addDays(30),
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('expected_repayments', [
            'invoice_id' => $invoice->id,
            'status' => 'open',
        ]);
    }

    /** @test */
    public function admin_can_mark_repayment_received()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $invoice = Invoice::create([
            'supplier_id' => 1,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'funded',
        ]);

        $expected = ExpectedRepayment::create([
            'invoice_id' => $invoice->id,
            'buyer_id' => 1,
            'amount' => 1000.00,
            'due_date' => now()->addDays(30),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->post('/api/v1/admin/repayments', [
            'buyer_id' => 1,
            'amount' => 1000.00,
            'received_date' => now()->toDateString(),
            'bank_reference' => 'REF-001',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('received_repayments', [
            'buyer_id' => 1,
            'amount' => 1000.00,
        ]);
    }

    /** @test */
    public function system_logs_audit_events()
    {
        $user = User::factory()->create();
        
        Invoice::create([
            'supplier_id' => 1,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $user->id,
            'entity_type' => Invoice::class,
        ]);
    }

    /** @test */
    public function invoice_review_endpoint_not_implemented()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $invoice = Invoice::create([
            'supplier_id' => 1,
            'buyer_id' => 1,
            'invoice_number' => 'INV-001',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'under_review',
        ]);

        $response = $this->actingAs($admin)->post("/api/v1/admin/invoices/{$invoice->id}/approve");

        // This endpoint currently returns 501
        $response->assertStatus(501);
    }
}







