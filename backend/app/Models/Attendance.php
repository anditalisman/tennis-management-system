<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'participant_id', 'status', 'method', 'verified_by', 'check_in_at', 'check_out_at'])]
class Attendance extends Model
{
    public const TABLE = 'attendance';

    protected $table = self::TABLE;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_EXCUSED = 'excused';

    public const STATUS_SICK = 'sick';

    public const STATUS_LEFT_EARLY = 'left_early';

    public const METHOD_QR = 'qr';

    public const METHOD_MANUAL = 'manual';

    // Statuses that count toward package session deduction (Sprint 5) — a
    // final, non-cancelled attendance of Hadir/Terlambat per Tahap 1 §15 #05.
    public const DEDUCTING_STATUSES = [self::STATUS_PRESENT, self::STATUS_LATE];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'method' => self::METHOD_MANUAL,
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrainingSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'session_id');
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
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
