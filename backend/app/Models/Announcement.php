<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'body', 'target_type', 'target_id', 'status', 'publish_at', 'expire_at', 'created_by'])]
#[UseFactory(AnnouncementFactory::class)]
class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    public const TARGET_ALL = 'all';

    public const TARGET_BRANCH = 'branch';

    public const TARGET_ROLE = 'role';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $attributes = [
        'target_type' => self::TARGET_ALL,
        'status' => self::STATUS_DRAFT,
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expire_at' => 'datetime',
        ];
    }

    public function isVisibleTo(User $user): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        if ($this->publish_at && $this->publish_at->isFuture()) {
            return false;
        }

        if ($this->expire_at && $this->expire_at->isPast()) {
            return false;
        }

        return match ($this->target_type) {
            self::TARGET_BRANCH => $this->target_id === $user->branch_id,
            self::TARGET_ROLE => $user->roles->pluck('id')->contains($this->target_id),
            default => true,
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
