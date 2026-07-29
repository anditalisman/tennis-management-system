<?php

namespace App\Models;

use Database\Factories\TrainingClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['program_id', 'branch_id', 'coach_id', 'court_id', 'name', 'capacity_min', 'capacity_max', 'session_duration', 'status'])]
#[UseFactory(TrainingClassFactory::class)]
class TrainingClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $attributes = [
        'capacity_min' => 1,
        'capacity_max' => 8,
        'session_duration' => 60,
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Coach, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /**
     * @return BelongsTo<Court, $this>
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * @return BelongsToMany<Participant, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class, 'class_members', 'class_id', 'participant_id')
            ->withPivot('status', 'joined_at')
            ->withTimestamps();
    }

    public function activeMemberCount(): int
    {
        return $this->members()->wherePivot('status', 'active')->count();
    }

    /**
     * @return HasMany<TrainingSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(TrainingSchedule::class, 'class_id');
    }

    /**
     * @return HasMany<WaitingList, $this>
     */
    public function waitingList(): HasMany
    {
        return $this->hasMany(WaitingList::class, 'class_id');
    }
}
