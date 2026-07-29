<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'signature_valid', 'payload', 'processed'])]
class PaymentGatewayLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
