<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['invoice_id', 'method', 'amount', 'status', 'reference_no', 'idempotency_key', 'submitted_by'])]
#[UseFactory(PaymentFactory::class)]
class Payment extends Model
{
    use HasFactory;

    public const METHOD_TRANSFER = 'transfer';

    public const METHOD_CASH = 'cash';

    public const METHOD_QRIS = 'qris';

    public const METHOD_GATEWAY = 'gateway';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return HasOne<PaymentConfirmation, $this>
     */
    public function confirmation(): HasOne
    {
        return $this->hasOne(PaymentConfirmation::class);
    }
}
