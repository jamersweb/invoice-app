<?php

namespace Tests\Feature\Supplier;

use App\Mail\LeadVerifyMail;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_onboarding_flow(): void
    {
        Mail::fake();
        Storage::fake('public');

        // 1) Lead Capture
        $resp = $this->post('/apply', [
            'company_email' => 'supplier@example.com',
            'company_phone' => '500000000',
        ]);
        $resp->assertRedirect();
        Mail::assertSent(LeadVerifyMail::class);

        $lead = Lead::firstOrFail();

        // 2) Email Verification
        $resp = $this->get('/apply/verify?token='.$lead->verify_token);
        $resp->assertRedirect();
        $lead->refresh();
        $this->assertEquals('verified', $lead->status);

        // 3) Profile Creation (minimal update)
        $user = User::factory()->create();
        $this->actingAs($user);
        $lead->company_name = 'Supplier LLC';
        $lead->save();

        // Seed document types for upload validation
        $this->seed(\Database\Seeders\DocumentTypeSeeder::class);

        // 4) Document Upload (one doc)
        $this->post('/documents', [
            'document_type_id' => 1,
            'file' => UploadedFile::fake()->image('cr.png'),
        ])->assertRedirect();

        // Assert document created in pending
        $this->assertDatabaseHas('documents', ['status' => 'pending']);
    }
}


