<?php

namespace App\Http\Resources;

use App\Models\TrialClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TrialClass */
class TrialClassResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'participant_id' => $this->whenLoaded('participant', fn () => $this->participant->uuid),
            'participant_name' => $this->whenLoaded('participant', fn () => $this->participant->full_name),
            'trial_date' => $this->trial_date?->toDateString(),
            'converted_to_member' => $this->converted_to_member,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
