<?php

namespace App\Http\Resources;

use App\Models\TournamentParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TournamentParticipant */
class TournamentParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'nickname' => $this->nickname,
            'category' => $this->category,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
