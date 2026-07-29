<?php

namespace App\Http\Resources;

use App\Models\Coach;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Coach */
class CoachResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->whenLoaded('user', fn () => $this->user->uuid),
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'email' => $this->whenLoaded('user', fn () => $this->user->email),
            'branch_id' => $this->branch_id,
            'certifications' => $this->certifications,
            'bio' => $this->bio,
            'employment_status' => $this->employment_status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
