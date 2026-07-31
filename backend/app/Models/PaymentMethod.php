<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['type', 'label', 'details', 'image_path', 'is_active', 'sort_order'])]
#[UseFactory(PaymentMethodFactory::class)]
class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_QRIS = 'qris';

    public const TYPE_BANK_TRANSFER = 'bank_transfer';

    public const TYPE_CASH = 'cash';

    public const TYPE_OTHER = 'other';

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
