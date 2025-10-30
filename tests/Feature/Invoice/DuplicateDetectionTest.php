<?php

namespace Tests\Feature\Invoice;

use App\Models\User;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_duplicate_when_amount_within_5_percent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Invoice::query()->create([
            'supplier_id' => 1,
            'buyer_id' => 2,
            'invoice_number' => 'INV-2000',
            'amount' => 1000.00,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'draft',
        ]);

        $response = $this->post('/invoices', [
            'supplier_id' => 1,
            'buyer_id' => 2,
            'invoice_number' => 'INV-2000',
            'amount' => 1049.00, // within +5%
            'currency' => 'SAR',
            'due_date' => now()->addDays(30)->toDateString(),
            'file' => UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertTrue((bool) $response->json('data.is_duplicate_flag'));
    }
}


