<?php

namespace Tests\Feature\Offer;

use App\Models\User;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\Offers\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_accept_offer_creates_funding_and_expected_repayment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $invoice = Invoice::query()->create([
            'supplier_id' => 1,
            'buyer_id' => 2,
            'invoice_number' => 'INV-5000',
            'amount' => 5000,
            'currency' => 'SAR',
            'due_date' => now()->addDays(30),
            'status' => 'offered',
        ]);

        $offer = Offer::query()->create([
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'tenor_days' => 30,
            'discount_rate' => 7.5,
            'discount_amount' => 31.25,
            'admin_fee' => 50,
            'net_amount' => 4918.75,
            'pricing_snapshot' => [],
            'status' => 'issued',
            'issued_at' => now(),
            'expires_at' => now()->addHours(48),
        ]);

        $resp = $this->post('/offers/accept', [
            'offer_id' => $offer->id,
        ]);

        $resp->assertStatus(201);
        $this->assertDatabaseHas('fundings', [
            'offer_id' => $offer->id,
            'invoice_id' => $invoice->id,
        ]);
        $this->assertDatabaseHas('expected_repayments', [
            'invoice_id' => $invoice->id,
            'buyer_id' => $invoice->buyer_id,
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'funded',
        ]);
    }
}


