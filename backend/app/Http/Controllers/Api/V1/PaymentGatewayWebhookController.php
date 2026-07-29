<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentConfirmation;
use App\Models\PaymentGatewayLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentGatewayWebhookController extends Controller
{
    /**
     * Generic payment-gateway webhook contract (no real provider is wired up
     * yet — this secures and logs whatever a future Midtrans/Xendit-style
     * integration would send). Expected JSON body:
     *   { "invoice_no": "...", "reference": "gateway-txn-id",
     *     "amount": 100000, "status": "paid" }
     * Signature: header X-Signature = HMAC-SHA256(raw body, shared secret).
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $secret = (string) config('services.payment_gateway.webhook_secret');
        $signature = (string) $request->header('X-Signature', '');
        $isValid = $secret !== '' && $signature !== '' && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);

        $payload = json_decode($rawBody, true);
        $payload = is_array($payload) ? $payload : [];

        $invoice = isset($payload['invoice_no'])
            ? Invoice::query()->where('invoice_no', $payload['invoice_no'])->first()
            : null;

        // Every inbound payload is logged regardless of validity — signature
        // failures must be auditable, not silently dropped (Tahap 1 §14/§15 #06).
        $log = PaymentGatewayLog::query()->create([
            'invoice_id' => $invoice?->id,
            'signature_valid' => $isValid,
            'payload' => $payload,
            'processed' => false,
        ]);

        abort_unless($isValid, 401, 'Signature tidak valid.');
        abort_unless($invoice, 404, 'Invoice tidak ditemukan.');
        abort_unless(
            isset($payload['reference'], $payload['amount'], $payload['status']),
            422,
            'Payload tidak lengkap.',
        );

        if ($payload['status'] !== 'paid') {
            $log->update(['processed' => true]);

            return response()->json(['data' => ['message' => 'Diterima; status bukan paid, tidak ada tindakan.']]);
        }

        // The gateway's own transaction reference doubles as our idempotency
        // key, so a replayed webhook can never create a duplicate payment.
        if (Payment::query()->where('idempotency_key', $payload['reference'])->exists()) {
            $log->update(['processed' => true]);

            return response()->json(['data' => ['message' => 'Sudah diproses sebelumnya.']]);
        }

        DB::transaction(function () use ($invoice, $payload) {
            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'method' => Payment::METHOD_GATEWAY,
                'amount' => $payload['amount'],
                'status' => Payment::STATUS_VERIFIED,
                'reference_no' => $payload['reference'],
                'idempotency_key' => $payload['reference'],
                'submitted_by' => $invoice->issued_by,
            ]);

            PaymentConfirmation::query()->create([
                'payment_id' => $payment->id,
                'verified_at' => now(),
                'note' => 'Diverifikasi otomatis oleh payment gateway.',
            ]);

            $amountPaid = $invoice->amountPaid();
            $invoice->update([
                'status' => $amountPaid >= (float) $invoice->total_amount
                    ? Invoice::STATUS_PAID
                    : Invoice::STATUS_PARTIALLY_PAID,
            ]);

            if ($invoice->status === Invoice::STATUS_PAID) {
                $invoice->activatePendingPackages();
            }
        });

        $log->update(['processed' => true]);

        return response()->json(['data' => ['message' => 'Pembayaran berhasil diproses.']]);
    }
}
