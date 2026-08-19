<?php

namespace App\Http\Resources;

use App\Models\TournamentMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentMatch */
class TournamentMatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'round' => $this->round,
            'position' => $this->position,
            'team_one' => $this->teamOne ? new TournamentTeamResource($this->teamOne) : null,
            'team_two' => $this->teamTwo ? new TournamentTeamResource($this->teamTwo) : null,
        ];
    }
}
