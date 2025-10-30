<?php

namespace Tests\Feature\Offer;

use App\Models\User;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_offer_creates_record_and_returns_201(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $invoice = Invoice::query()->create([
            'supplier_id' => 1,
            'buyer_id' => 2,
            'invoice_number' => 'INV-4000',
            'amount' => 10000,
            'currency' => 'SAR',
            'due_date' => now()->addDays(60),
            'status' => 'under_review',
        ]);

        $resp = $this->post('/offers/issue', [
            'invoice_id' => $invoice->id,
            'supplier_grade' => 'B',
            'buyer_grade' => 'B',
            'historical_default_rate' => 1.0,
        ]);

        $resp->assertStatus(201);
        $resp->assertJsonPath('data.invoice_id', $invoice->id);
        $this->assertDatabaseHas('offers', [
            'invoice_id' => $invoice->id,
            'status' => 'issued',
        ]);
    }
}


