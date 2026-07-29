<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['class_id', 'session_id', 'title', 'visibility', 'status', 'uploaded_by'])]
class Gallery extends Model
{
    use SoftDeletes;

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_PUBLIC = 'public';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    protected $attributes = [
        'visibility' => self::VISIBILITY_PRIVATE,
        'status' => self::STATUS_PENDING,
    ];

    /**
     * @return BelongsTo<TrainingClass, $this>
     */
    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    /**
     * @return BelongsTo<TrainingSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return HasMany<GalleryMedia, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(GalleryMedia::class);
    }
}
