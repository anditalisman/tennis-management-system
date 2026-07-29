<?php

namespace App\Models;

use Database\Factories\TrainingScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['class_id', 'court_id', 'coach_id', 'session_date', 'start_time', 'end_time', 'type', 'status', 'cancellation_reason', 'replaces_schedule_id'])]
#[UseFactory(TrainingScheduleFactory::class)]
class TrainingSchedule extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_REGULAR = 'reguler';

    public const TYPE_SPECIAL = 'khusus';

    public const TYPE_REPLACEMENT = 'pengganti';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<TrainingClass, $this>
     */
    public function trainingClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'class_id');
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsTo<Coach, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /**
     * @return HasOne<TrainingSession, $this>
     */
    public function session(): HasOne
    {
        return $this->hasOne(TrainingSession::class, 'schedule_id');
    }
}
