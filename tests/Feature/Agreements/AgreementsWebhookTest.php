<?php

namespace Tests\Feature\Agreements;

use App\Models\Agreement;
use App\Models\AgreementTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgreementsWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_updates_agreement_and_saves_pdf(): void
    {
        Storage::fake('public');
        Config::set('services.esign.webhook_token', 'secret');

        $tpl = AgreementTemplate::create(['name' => 'Master', 'version' => 'v1', 'html' => '<h1>T</h1>']);
        $agreement = Agreement::create([
            'agreement_template_id' => $tpl->id,
            'version' => 'v1',
            'status' => 'sent',
        ]);

        $pdf = base64_encode('%PDF-1.4 fake');
        $this->withHeader('X-Webhook-Token', 'secret')
            ->postJson(route('webhooks.esign'), [
                'agreement_id' => $agreement->id,
                'pdf_base64' => $pdf,
            ])->assertOk();

        $agreement->refresh();
        $this->assertEquals('signed', $agreement->status);
        $this->assertNotNull($agreement->signed_at);
        $this->assertNotNull($agreement->signed_pdf);
        Storage::disk('public')->assertExists($agreement->signed_pdf);
    }
}


