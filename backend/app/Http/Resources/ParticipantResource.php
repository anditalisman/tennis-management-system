<?php

namespace App\Http\Resources;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Participant */
class ParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'registration_no' => $this->registration_no,
            'branch_id' => $this->branch_id,
            'user_id' => $this->whenLoaded('user', fn () => $this->user?->uuid),
            'full_name' => $this->full_name,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->toDateString(),
            'age_category' => $this->age_category,
            'gender' => $this->gender,
            'skill_level' => $this->skill_level,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'policy_accepted_at' => $this->policy_accepted_at?->toIso8601String(),
            'guardians' => $this->whenLoaded('guardians', fn () => $this->guardians->map(fn ($guardian) => [
                'id' => $guardian->user->uuid,
                'name' => $guardian->user->name,
                'relation' => $guardian->relation,
                'is_primary' => (bool) $guardian->pivot->is_primary,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
