<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ScopesToBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Package;
use App\Models\Participant;
use App\Models\ParticipantPackage;
use App\Models\Role;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    use ScopesToBranch;

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->visibleInvoices($request->user())
            ->with(['participant', 'items'])
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('participant_id')->isNotEmpty(), function ($query) use ($request) {
                $query->whereHas('participant', fn ($q) => $q->where('uuid', $request->string('participant_id')));
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => InvoiceResource::collection($invoices->items()),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = DB::transaction(function () use ($request) {
            $participant = Participant::query()->where('uuid', $request->validated('participant_id'))->firstOrFail();

            $invoice = Invoice::query()->create([
                'invoice_no' => Invoice::generateInvoiceNo($participant->branch_id),
                'participant_id' => $participant->id,
                'branch_id' => $participant->branch_id,
                'due_date' => $request->validated('due_date'),
                'status' => Invoice::STATUS_UNPAID,
                'issued_by' => $request->user()->id,
            ]);

            $subtotal = 0.0;

            foreach ($request->validated('items') as $itemData) {
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

            $discount = (float) ($request->validated('discount_amount') ?? 0);

            if ($voucherCode = $request->validated('voucher_code')) {
                $voucher = Voucher::query()->where('code', $voucherCode)->lockForUpdate()->first();
                abort_if(! $voucher || ! $voucher->isUsable(), 422, 'Kode voucher tidak valid atau sudah tidak berlaku.');

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

        return response()->json(['data' => new InvoiceResource($invoice->load('participant', 'items'))], 201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeView($request->user(), $invoice);

        return response()->json(['data' => new InvoiceResource($invoice->load('participant', 'items'))]);
    }

    private function visibleInvoices(User $user): Builder
    {
        if ($user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR) || $user->hasRole(Role::FINANCE)) {
            return $this->scopeToStaffBranch(Invoice::query(), $user);
        }

        if ($user->hasRole(Role::PARTICIPANT) && $user->participant) {
            return Invoice::query()->where('participant_id', $user->participant->id);
        }

        if ($user->hasRole(Role::GUARDIAN) && $user->guardian) {
            $participantIds = $user->guardian->participants()->pluck('participants.id');

            return Invoice::query()->whereIn('participant_id', $participantIds);
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }

    public function authorizeView(User $user, Invoice $invoice): void
    {
        $allowed = $this->visibleInvoices($user)->whereKey($invoice->id)->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }
}
