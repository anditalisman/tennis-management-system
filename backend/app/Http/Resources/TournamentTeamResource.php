<?php

namespace App\Http\Resources;

use App\Models\TournamentTeam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentTeam */
class TournamentTeamResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participant_one' => $this->participantOne ? new TournamentParticipantResource($this->participantOne) : null,
            'participant_two' => $this->participantTwo ? new TournamentParticipantResource($this->participantTwo) : null,
        ];
    }
}
