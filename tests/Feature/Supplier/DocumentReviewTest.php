<?php

namespace Tests\Feature\Supplier;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyst_can_approve_document(): void
    {
        Storage::fake('public');
        $this->seed(\Database\Seeders\DocumentTypeSeeder::class);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $supplier = User::factory()->create();
        $this->actingAs($supplier);
        $this->post('/documents', [
            'document_type_id' => 1,
            'file' => UploadedFile::fake()->image('vat.png'),
        ])->assertRedirect();

        $doc = Document::firstOrFail();

        $analyst = User::factory()->create();
        $analyst->syncRoles('Analyst');
        $this->actingAs($analyst);

        $this->post(route('documents.review', $doc), [
            'action' => 'approve',
            'review_notes' => 'Looks good',
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', ['id' => $doc->id, 'status' => 'approved']);
    }
}


