<?php

namespace Tests\Feature\Agreements;

use App\Models\AgreementTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgreementsHappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_generate_and_send(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $tpl = AgreementTemplate::create([
            'name' => 'Master Agreement',
            'version' => 'v1',
            'html' => '<h1>Agreement</h1><p>Terms...</p>'
        ]);

        // Create agreement from template (draft + generate PDF)
        $this->actingAs($user)
            ->post(route('agreements.store'), ['template_id' => $tpl->id])
            ->assertRedirect();

        $this->assertDatabaseHas('agreements', [
            'agreement_template_id' => $tpl->id,
            'version' => 'v1',
            'status' => 'draft',
        ]);

        $agreement = \App\Models\Agreement::first();
        $this->assertNotNull($agreement->signed_pdf);

        // Send for e-sign
        $this->actingAs($user)
            ->post(route('agreements.send'), [
                'agreement_id' => $agreement->id,
                'recipient' => ['email' => 'supplier@example.com', 'name' => 'Supplier'],
            ])->assertRedirect();

        $this->assertDatabaseHas('agreements', [
            'id' => $agreement->id,
            'status' => 'sent',
        ]);
    }
}


