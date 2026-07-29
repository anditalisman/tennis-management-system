<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentConfirmation;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request, Invoice $invoice): JsonResponse
    {
        app(InvoiceController::class)->authorizeView($request->user(), $invoice);

        return response()->json(['data' => PaymentResource::collection($invoice->payments()->with('confirmation')->orderByDesc('created_at')->get())]);
    }

    public function store(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        app(InvoiceController::class)->authorizeView($request->user(), $invoice);

        abort_if(
            in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED], true),
            422,
            'Invoice ini sudah lunas atau dibatalkan.',
        );

        $idempotencyKey = $request->header('Idempotency-Key');
        abort_unless($idempotencyKey, 422, 'Header Idempotency-Key wajib disertakan.');

        $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return response()->json(['data' => new PaymentResource($existing->load('confirmation'))], 200);
        }

        $payment = DB::transaction(function () use ($request, $invoice, $idempotencyKey) {
            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'method' => $request->validated('method'),
                'amount' => $request->validated('amount'),
                'reference_no' => $request->validated('reference_no'),
                'idempotency_key' => $idempotencyKey,
                'submitted_by' => $request->user()->id,
            ]);

            if ($request->hasFile('proof')) {
                $path = $request->file('proof')->store('payments/'.$invoice->id, 's3');
                PaymentConfirmation::query()->create([
                    'payment_id' => $payment->id,
                    'proof_path' => $path,
                ]);
            }

            return $payment;
        });

        return response()->json(['data' => new PaymentResource($payment->load('confirmation'))], 201);
    }

    public function verify(VerifyPaymentRequest $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::FINANCE),
            403,
            'Hanya petugas keuangan yang dapat memverifikasi pembayaran.',
        );
        abort_unless($payment->status === Payment::STATUS_PENDING, 422, 'Pembayaran ini sudah diproses sebelumnya.');

        DB::transaction(function () use ($request, $payment, $user) {
            $approved = $request->validated('action') === 'approve';

            $payment->update(['status' => $approved ? Payment::STATUS_VERIFIED : Payment::STATUS_REJECTED]);

            PaymentConfirmation::query()->updateOrCreate(
                ['payment_id' => $payment->id],
                ['verified_by' => $user->id, 'verified_at' => now(), 'note' => $request->validated('note')],
            );

            if (! $approved) {
                return;
            }

            $invoice = $payment->invoice;
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

        $payment->refresh();
        if ($recipient = $payment->invoice->participant->notifiableUser()) {
            Notification::queue(
                $recipient,
                Notification::CHANNEL_EMAIL,
                $payment->status === Payment::STATUS_VERIFIED ? 'Pembayaran terverifikasi' : 'Pembayaran ditolak',
                $payment->status === Payment::STATUS_VERIFIED
                    ? "Pembayaran untuk invoice {$payment->invoice->invoice_no} sebesar {$payment->amount} telah diverifikasi."
                    : "Pembayaran untuk invoice {$payment->invoice->invoice_no} ditolak. Silakan unggah ulang bukti bayar.",
            );
        }

        return response()->json(['data' => new PaymentResource($payment->load('confirmation'))]);
    }

    public function receipt(Request $request, Invoice $invoice): JsonResponse
    {
        app(InvoiceController::class)->authorizeView($request->user(), $invoice);

        $verifiedPayments = $invoice->payments()->where('status', Payment::STATUS_VERIFIED)->with('confirmation')->get();
        abort_if($verifiedPayments->isEmpty(), 404, 'Belum ada pembayaran terverifikasi untuk invoice ini.');

        return response()->json(['data' => [
            'invoice_no' => $invoice->invoice_no,
            'participant' => $invoice->participant->full_name,
            'total_amount' => (float) $invoice->total_amount,
            'amount_paid' => $invoice->amountPaid(),
            'status' => $invoice->status,
            'payments' => $verifiedPayments->map(fn (Payment $payment) => [
                'method' => $payment->method,
                'amount' => (float) $payment->amount,
                'reference_no' => $payment->reference_no,
                'verified_at' => $payment->confirmation?->verified_at?->toIso8601String(),
            ]),
        ]]);
    }
}
