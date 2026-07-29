<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['referrer_participant_id', 'referred_participant_id', 'code', 'reward_status'])]
class Referral extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_REWARDED = 'rewarded';

    protected $attributes = [
        'reward_status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (Referral $referral) {
            $referral->code ??= Str::upper(Str::random(8));
        });
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'referrer_participant_id');
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'referred_participant_id');
    }
}
