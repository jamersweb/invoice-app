<?php

namespace Tests\Feature\Invoice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmitInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_submit_invoice(): void
    {
        Storage::fake(config('filesystems.default', 'local'));
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/invoices', [
            'supplier_id' => 1,
            'buyer_id' => 1,
            'invoice_number' => 'INV-1001',
            'amount' => 1000.50,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30)->toDateString(),
            'file' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.invoice_number', 'INV-1001');
    }
}


