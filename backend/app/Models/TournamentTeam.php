<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['participant_one_id', 'participant_two_id'])]
class TournamentTeam extends Model
{
    /**
     * @return BelongsTo<TournamentParticipant, $this>
     */
    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant_one_id');
    }

    /**
     * @return BelongsTo<TournamentParticipant, $this>
     */
    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant_two_id');
    }
}
