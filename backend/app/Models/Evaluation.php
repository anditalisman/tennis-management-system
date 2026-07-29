<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['participant_id', 'coach_id', 'evaluation_date', 'next_target', 'recommended_class_id'])]
class Evaluation extends Model
{
    // 11 aspects per Tahap 1 §03 (teknik & non-teknik).
    public const ASPECTS = [
        'forehand', 'backhand', 'servis', 'voli', 'footwork',
        'stamina', 'kelenturan', 'konsistensi', 'strategi', 'disiplin', 'sportivitas',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<Coach, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /**
     * @return BelongsTo<TrainingClass, $this>
     */
    public function recommendedClass(): BelongsTo
    {
        return $this->belongsTo(TrainingClass::class, 'recommended_class_id');
    }

    /**
     * @return HasMany<EvaluationDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(EvaluationDetail::class);
    }
}
