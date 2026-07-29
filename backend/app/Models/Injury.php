<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['participant_id', 'description', 'severity', 'reported_at', 'reported_by'])]
class Injury extends Model
{
    public const SEVERITY_MINOR = 'minor';

    public const SEVERITY_MODERATE = 'moderate';

    public const SEVERITY_SEVERE = 'severe';

    protected function casts(): array
    {
        return [
            'reported_at' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
