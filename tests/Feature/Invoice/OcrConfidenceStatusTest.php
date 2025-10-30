<?php

namespace Tests\Feature\Invoice;

use App\Models\User;
use App\Modules\Invoices\Contracts\OcrServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OcrConfidenceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_under_review_when_confidence_below_threshold(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->app->bind(OcrServiceInterface::class, function () {
            return new class implements OcrServiceInterface {
                public function extract($filePath): array
                {
                    return [
                        'confidence_score' => 60,
                    ];
                }
            };
        });

        $response = $this->post('/invoices', [
            'supplier_id' => 1,
            'buyer_id' => 2,
            'invoice_number' => 'INV-3000',
            'amount' => 1000,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30)->toDateString(),
            'file' => UploadedFile::fake()->create('invoice.pdf', 50, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $invoiceId = $response->json('data.id');

        // Run the job inline to avoid queue
        \App\Modules\Invoices\Jobs\ProcessInvoiceOcr::dispatchSync(\App\Modules\Invoices\Models\Invoice::find($invoiceId));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'under_review',
            'ocr_confidence' => 60,
        ]);
    }
}


