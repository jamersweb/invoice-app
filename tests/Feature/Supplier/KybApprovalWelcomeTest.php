<?php

namespace Tests\Feature\Supplier;

use App\Mail\SupplierWelcomeMail;
use App\Models\Document;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class KybApprovalWelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_persisted_and_welcome_sent_after_docs_approved(): void
    {
        Mail::fake();
        $this->seed(\Database\Seeders\DocumentTypeSeeder::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $analyst = User::factory()->create();
        $analyst->syncRoles('Analyst');
        $this->actingAs($analyst);

        $ownerId = 123;
        // Create 3 docs for the supplier
        for ($i=1; $i<=3; $i++) {
            Document::create([
                'document_type_id' => 1,
                'owner_type' => 'supplier',
                'owner_id' => $ownerId,
                'status' => 'pending_review',
                'file_path' => 'x',
            ]);
        }

        $doc = Document::first();
        $this->post(route('documents.review', $doc), [
            'action' => 'approve',
        ])->assertRedirect();

        // Approve remaining docs
        foreach (Document::skip(1)->take(2)->get() as $d) {
            $this->post(route('documents.review', $d), [
                'action' => 'approve',
            ])->assertRedirect();
        }

        $this->assertDatabaseHas('suppliers', ['id' => $ownerId, 'kyb_status' => 'approved']);
        Mail::assertSent(SupplierWelcomeMail::class);
    }
}


