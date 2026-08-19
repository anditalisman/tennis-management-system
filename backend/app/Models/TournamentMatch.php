<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['round', 'position', 'team_one_id', 'team_two_id'])]
class TournamentMatch extends Model
{
    /**
     * @return BelongsTo<TournamentTeam, $this>
     */
    public function teamOne(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'team_one_id');
    }

    /**
     * @return BelongsTo<TournamentTeam, $this>
     */
    public function teamTwo(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'team_two_id');
    }
}
