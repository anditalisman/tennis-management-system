<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Package;
use App\Models\Participant;
use App\Models\ParticipantPackage;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared by the staff "Buat Tagihan" flow (InvoiceController::store, any
 * mix of package/other items, arbitrary discount) and participant
 * self-checkout (PackageCheckoutController, always exactly one package
 * item, no arbitrary discount) so both create invoices, line items, and
 * pending ParticipantPackage rows identically.
 *
 * @param  array<int, array<string, mixed>>  $items
 */
class InvoiceIssuer
{
    public function issue(
        Participant $participant,
        array $items,
        ?string $voucherCode,
        string $dueDate,
        int $issuedByUserId,
        float $discount = 0.0,
    ): Invoice {
        return DB::transaction(function () use ($participant, $items, $voucherCode, $dueDate, $issuedByUserId, $discount) {
            $invoice = Invoice::query()->create([
                'invoice_no' => Invoice::generateInvoiceNo($participant->branch_id),
                'participant_id' => $participant->id,
                'branch_id' => $participant->branch_id,
                'due_date' => $dueDate,
                'status' => Invoice::STATUS_UNPAID,
                'issued_by' => $issuedByUserId,
            ]);

            $subtotal = 0.0;

            foreach ($items as $itemData) {
                $qty = $itemData['qty'] ?? 1;

                if ($itemData['item_type'] === InvoiceItem::TYPE_PACKAGE) {
                    $package = Package::query()->findOrFail($itemData['package_id']);
                    $unitPrice = (float) $package->price;
                    $description = $package->name;
                    $itemId = $package->id;
                } else {
                    $unitPrice = (float) $itemData['unit_price'];
                    $description = $itemData['description'];
                    $itemId = null;
                }

                $lineSubtotal = $unitPrice * $qty;
                $subtotal += $lineSubtotal;

                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $itemData['item_type'],
                    'item_id' => $itemId,
                    'description' => $description,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                if ($itemData['item_type'] === InvoiceItem::TYPE_PACKAGE) {
                    ParticipantPackage::query()->create([
                        'participant_id' => $participant->id,
                        'package_id' => $package->id,
                        'sessions_remaining' => $package->session_count * $qty,
                        'valid_until' => now()->addDays($package->validity_days),
                        'status' => ParticipantPackage::STATUS_PENDING,
                    ]);
                }
            }

            if ($voucherCode) {
                $voucher = Voucher::query()->where('code', $voucherCode)->lockForUpdate()->first();
                if (! $voucher || ! $voucher->isUsable()) {
                    throw ValidationException::withMessages([
                        'voucher_code' => ['Kode voucher tidak valid atau sudah tidak berlaku.'],
                    ]);
                }

                $voucherDiscount = $voucher->calculateDiscount($subtotal - $discount);
                $discount += $voucherDiscount;

                $voucher->invoices()->attach($invoice->id, ['discount_applied' => $voucherDiscount]);
                $voucher->increment('used_count');
            }

            $total = max(0, $subtotal - $discount);

            $invoice->update([
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $total,
            ]);

            return $invoice;
        });
    }
}
