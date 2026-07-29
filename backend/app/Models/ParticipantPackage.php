<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['participant_id', 'package_id', 'sessions_remaining', 'valid_until', 'status', 'purchased_at'])]
class ParticipantPackage extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_EXHAUSTED = 'exhausted';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'purchased_at' => 'datetime',
        ];
    }

    public function isUsable(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->sessions_remaining > 0
            && (! $this->valid_until || ! $this->valid_until->isPast());
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
