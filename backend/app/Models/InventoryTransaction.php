<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['item_id', 'type', 'qty', 'participant_id', 'created_by', 'note'])]
class InventoryTransaction extends Model
{
    public const TYPE_IN = 'in';

    public const TYPE_OUT = 'out';

    public const TYPE_BORROW = 'borrow';

    public const TYPE_RETURN = 'return';

    public const TYPE_DAMAGE = 'damage';

    public const TYPE_LOSS = 'loss';

    // Transaction types that decrease stock_qty; all others increase it.
    public const DECREASING_TYPES = [self::TYPE_OUT, self::TYPE_BORROW, self::TYPE_DAMAGE, self::TYPE_LOSS];

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
