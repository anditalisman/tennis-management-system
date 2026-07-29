<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        config(['services.payment_gateway.webhook_secret' => 'test-secret']);
    }

    private function sign(array $payload): array
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, 'test-secret');

        return [$body, $signature];
    }

    public function test_valid_signature_marks_invoice_paid(): void
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_UNPAID, 'total_amount' => 150000]);
        [$body, $signature] = $this->sign([
            'invoice_no' => $invoice->invoice_no,
            'reference' => 'gw-txn-001',
            'amount' => 150000,
            'status' => 'paid',
        ]);

        $response = $this->call('POST', '/api/v1/webhooks/payment-gateway', [], [], [], [
            'HTTP_X-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertOk();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertDatabaseHas('payment_gateway_logs', ['signature_valid' => true, 'processed' => true]);
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id, 'status' => Payment::STATUS_VERIFIED]);
    }

    public function test_invalid_signature_is_rejected_and_logged_without_changing_invoice(): void
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_UNPAID]);
        $body = json_encode([
            'invoice_no' => $invoice->invoice_no,
            'reference' => 'gw-txn-002',
            'amount' => 150000,
            'status' => 'paid',
        ]);

        $response = $this->call('POST', '/api/v1/webhooks/payment-gateway', [], [], [], [
            'HTTP_X-Signature' => 'not-the-real-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $response->assertUnauthorized();
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->fresh()->status);
        $this->assertDatabaseHas('payment_gateway_logs', ['signature_valid' => false]);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_replayed_webhook_does_not_create_a_duplicate_payment(): void
    {
        $invoice = Invoice::factory()->create(['status' => Invoice::STATUS_UNPAID, 'total_amount' => 150000]);
        $payload = [
            'invoice_no' => $invoice->invoice_no,
            'reference' => 'gw-txn-003',
            'amount' => 150000,
            'status' => 'paid',
        ];

        foreach ([1, 2] as $attempt) {
            [$body, $signature] = $this->sign($payload);
            $this->call('POST', '/api/v1/webhooks/payment-gateway', [], [], [], [
                'HTTP_X-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ], $body)->assertOk();
        }

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_unknown_invoice_returns_not_found_but_still_logs_payload(): void
    {
        [$body, $signature] = $this->sign([
            'invoice_no' => 'INV-DOES-NOT-EXIST',
            'reference' => 'gw-txn-004',
            'amount' => 1000,
            'status' => 'paid',
        ]);

        $this->call('POST', '/api/v1/webhooks/payment-gateway', [], [], [], [
            'HTTP_X-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body)->assertNotFound();

        $this->assertDatabaseHas('payment_gateway_logs', ['invoice_id' => null, 'signature_valid' => true]);
    }
}
