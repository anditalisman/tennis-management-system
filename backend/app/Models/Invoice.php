<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['invoice_no', 'participant_id', 'branch_id', 'due_date', 'status', 'subtotal_amount', 'discount_amount', 'tax_amount', 'total_amount', 'issued_by'])]
#[UseFactory(InvoiceFactory::class)]
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    protected $attributes = [
        'status' => self::STATUS_UNPAID,
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public static function generateInvoiceNo(?int $branchId): string
    {
        $branch = Branch::query()->find($branchId);
        $prefix = $branch ? Str::upper(Str::slug($branch->slug, '')) : 'ZTC';
        $yearMonth = now()->format('Ym');

        $sequence = static::query()
            ->where('branch_id', $branchId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->withTrashed()
            ->count() + 1;

        return sprintf('INV-%s-%s-%04d', $prefix, $yearMonth, $sequence);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->where('status', Payment::STATUS_VERIFIED)->sum('amount');
    }

    /**
     * Flip any still-pending packages billed on this invoice to active, once
     * the invoice itself is fully paid. Shared by manual finance verification
     * and the payment gateway webhook so both paths behave identically.
     */
    public function activatePendingPackages(): void
    {
        $packageIds = $this->items()->where('item_type', InvoiceItem::TYPE_PACKAGE)->pluck('item_id');

        ParticipantPackage::query()
            ->where('participant_id', $this->participant_id)
            ->whereIn('package_id', $packageIds)
            ->where('status', ParticipantPackage::STATUS_PENDING)
            ->get()
            ->each(function (ParticipantPackage $participantPackage) {
                $participantPackage->update([
                    'status' => ParticipantPackage::STATUS_ACTIVE,
                    'purchased_at' => now(),
                    'valid_until' => now()->addDays($participantPackage->package->validity_days),
                ]);
            });
    }
}
