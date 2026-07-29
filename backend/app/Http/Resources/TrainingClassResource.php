<?php

namespace App\Http\Resources;

use App\Models\TrainingClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TrainingClass */
class TrainingClassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeMembers = $this->activeMemberCount();

        return [
            'id' => $this->id,
            'program_id' => $this->program_id,
            'branch_id' => $this->branch_id,
            'coach_id' => $this->coach_id,
            'court_id' => $this->court_id,
            'name' => $this->name,
            'capacity_min' => $this->capacity_min,
            'capacity_max' => $this->capacity_max,
            'active_member_count' => $activeMembers,
            'quota_remaining' => max(0, $this->capacity_max - $activeMembers),
            'session_duration' => $this->session_duration,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
