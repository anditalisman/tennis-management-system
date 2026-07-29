<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\Package;
use App\Models\Participant;
use App\Models\ParticipantPackage;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    private function invoiceWithPendingPackage(): array
    {
        $package = Package::factory()->create(['price' => 500000, 'session_count' => 8, 'validity_days' => 60]);
        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        $participant = Participant::factory()->create(['user_id' => $participantUser->id]);

        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $invoiceResponse = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $participant->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['item_type' => 'package', 'package_id' => $package->id]],
        ]);
        $invoice = Invoice::query()->findOrFail($invoiceResponse->json('data.id'));

        return compact('package', 'participantUser', 'participant', 'invoice');
    }

    public function test_participant_can_submit_a_payment_for_their_own_invoice(): void
    {
        ['participantUser' => $participantUser, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();

        $response = $this->actingAs($participantUser, 'sanctum')
            ->withHeader('Idempotency-Key', 'test-key-1')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'method' => 'transfer',
                'amount' => 500000,
                'reference_no' => 'TRX123',
            ]);

        $response->assertCreated()->assertJsonPath('data.status', Payment::STATUS_PENDING);
    }

    public function test_duplicate_idempotency_key_returns_the_same_payment_instead_of_creating_a_new_one(): void
    {
        ['participantUser' => $participantUser, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();

        $payload = ['method' => 'transfer', 'amount' => 500000];
        $first = $this->actingAs($participantUser, 'sanctum')
            ->withHeader('Idempotency-Key', 'same-key')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", $payload);
        $second = $this->actingAs($participantUser, 'sanctum')
            ->withHeader('Idempotency-Key', 'same-key')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", $payload);

        $second->assertOk()->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertSame(1, Payment::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_payment_requires_idempotency_key_header(): void
    {
        ['participantUser' => $participantUser, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();

        $this->actingAs($participantUser, 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'transfer', 'amount' => 500000])
            ->assertUnprocessable();
    }

    public function test_participant_cannot_submit_a_payment_for_someone_elses_invoice(): void
    {
        ['invoice' => $invoice] = $this->invoiceWithPendingPackage();
        $intruder = $this->userWithRole(Role::PARTICIPANT);
        Participant::factory()->create(['user_id' => $intruder->id]);

        $this->actingAs($intruder, 'sanctum')
            ->withHeader('Idempotency-Key', 'intruder-key')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'transfer', 'amount' => 500000])
            ->assertForbidden();
    }

    public function test_finance_approving_a_payment_marks_invoice_paid_and_activates_the_package(): void
    {
        ['participantUser' => $participantUser, 'participant' => $participant, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();

        $paymentResponse = $this->actingAs($participantUser, 'sanctum')
            ->withHeader('Idempotency-Key', 'pay-1')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'transfer', 'amount' => 500000]);
        $paymentId = $paymentResponse->json('data.id');

        $finance = $this->userWithRole(Role::FINANCE);
        $verifyResponse = $this->actingAs($finance, 'sanctum')->postJson("/api/v1/payments/{$paymentId}/verify", [
            'action' => 'approve',
        ]);

        $verifyResponse->assertOk()->assertJsonPath('data.status', Payment::STATUS_VERIFIED);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);

        $package = ParticipantPackage::query()->where('participant_id', $participant->id)->firstOrFail();
        $this->assertSame(ParticipantPackage::STATUS_ACTIVE, $package->status);
        $this->assertNotNull($package->purchased_at);
    }

    public function test_finance_rejecting_a_payment_does_not_change_invoice_status(): void
    {
        ['participantUser' => $participantUser, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();

        $paymentResponse = $this->actingAs($participantUser, 'sanctum')
            ->withHeader('Idempotency-Key', 'pay-2')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'transfer', 'amount' => 500000]);
        $paymentId = $paymentResponse->json('data.id');

        $finance = $this->userWithRole(Role::FINANCE);
        $this->actingAs($finance, 'sanctum')->postJson("/api/v1/payments/{$paymentId}/verify", [
            'action' => 'reject',
            'note' => 'Bukti tidak jelas',
        ])->assertOk()->assertJsonPath('data.status', Payment::STATUS_REJECTED);

        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->fresh()->status);
    }

    public function test_administrator_cannot_verify_payments(): void
    {
        ['participantUser' => $participantUser, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();
        $paymentResponse = $this->actingAs($participantUser, 'sanctum')
            ->withHeader('Idempotency-Key', 'pay-3')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'transfer', 'amount' => 500000]);
        $paymentId = $paymentResponse->json('data.id');

        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/payments/{$paymentId}/verify", ['action' => 'approve'])
            ->assertForbidden();
    }

    public function test_receipt_is_unavailable_until_a_payment_is_verified(): void
    {
        ['participantUser' => $participantUser, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();

        $this->actingAs($participantUser, 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}/receipt")
            ->assertNotFound();
    }

    public function test_receipt_is_available_after_verification(): void
    {
        ['participantUser' => $participantUser, 'invoice' => $invoice] = $this->invoiceWithPendingPackage();
        $paymentResponse = $this->actingAs($participantUser, 'sanctum')
            ->withHeader('Idempotency-Key', 'pay-4')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", ['method' => 'transfer', 'amount' => 500000]);
        $finance = $this->userWithRole(Role::FINANCE);
        $this->actingAs($finance, 'sanctum')->postJson("/api/v1/payments/{$paymentResponse->json('data.id')}/verify", ['action' => 'approve']);

        $this->actingAs($participantUser, 'sanctum')
            ->getJson("/api/v1/invoices/{$invoice->id}/receipt")
            ->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_PAID);
    }
}
