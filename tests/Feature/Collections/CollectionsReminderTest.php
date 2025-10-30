<?php

namespace Tests\Feature\Collections;

use App\Models\AuditEvent;
use App\Modules\Invoices\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CollectionsReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Mail::fake();
    }

    public function test_collector_can_send_reminder_email()
    {
        $collector = User::factory()->create();
        $collector->assignRole('Collector');

        $invoice = Invoice::factory()->create([
            'status' => 'overdue',
            'amount' => 5000.00,
            'due_date' => now()->subDays(10),
        ]);

        $response = $this->actingAs($collector)
            ->postJson("/api/v1/admin/collections/{$invoice->id}/remind");

        $response->assertStatus(200)
            ->assertJson(['ok' => true, 'message' => 'Reminder sent successfully']);

        // Assert email was sent
        Mail::assertSent(\App\Mail\CollectionsReminderMail::class, function ($mail) use ($invoice) {
            return $mail->invoice->id === $invoice->id;
        });

        // Assert audit event was created
        $this->assertDatabaseHas('audit_events', [
            'actor_type' => \App\Models\User::class,
            'actor_id' => $collector->id,
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'collections_remind',
        ]);
    }

    public function test_admin_can_send_reminder_email()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $invoice = Invoice::factory()->create([
            'status' => 'overdue',
            'amount' => 3000.00,
            'due_date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/collections/{$invoice->id}/remind");

        $response->assertStatus(200);

        Mail::assertSent(\App\Mail\CollectionsReminderMail::class);
    }

    public function test_supplier_cannot_send_reminder_email()
    {
        $supplier = User::factory()->create();
        $supplier->assignRole('Supplier');

        $invoice = Invoice::factory()->create([
            'status' => 'overdue',
        ]);

        $response = $this->actingAs($supplier)
            ->postJson("/api/v1/admin/collections/{$invoice->id}/remind");

        $response->assertStatus(403);

        Mail::assertNothingSent();
    }

    public function test_claim_action_logs_audit_event()
    {
        $collector = User::factory()->create();
        $collector->assignRole('Collector');

        $invoice = Invoice::factory()->create([
            'status' => 'overdue',
            'assigned_to' => null,
        ]);

        $response = $this->actingAs($collector)
            ->postJson("/api/v1/admin/collections/{$invoice->id}/claim");

        $response->assertStatus(200);

        // Assert invoice was assigned
        $invoice->refresh();
        $this->assertEquals($collector->id, $invoice->assigned_to);

        // Assert audit event was created
        $this->assertDatabaseHas('audit_events', [
            'actor_type' => \App\Models\User::class,
            'actor_id' => $collector->id,
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'collections_claim',
        ]);
    }

    public function test_reassign_action_logs_audit_event()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $collector = User::factory()->create();
        $collector->assignRole('Collector');

        $invoice = Invoice::factory()->create([
            'status' => 'overdue',
            'assigned_to' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/collections/{$invoice->id}/reassign", [
                'assigned_to' => $collector->id,
            ]);

        $response->assertStatus(200);

        // Assert invoice was reassigned
        $invoice->refresh();
        $this->assertEquals($collector->id, $invoice->assigned_to);

        // Assert audit event was created
        $this->assertDatabaseHas('audit_events', [
            'actor_type' => \App\Models\User::class,
            'actor_id' => $admin->id,
            'entity_type' => Invoice::class,
            'entity_id' => $invoice->id,
            'action' => 'collections_reassign',
        ]);
    }

    public function test_reassign_requires_valid_assigned_to()
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $invoice = Invoice::factory()->create([
            'status' => 'overdue',
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/admin/collections/{$invoice->id}/reassign", [
                'assigned_to' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    }
}
