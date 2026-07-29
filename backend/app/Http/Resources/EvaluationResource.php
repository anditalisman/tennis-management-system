<?php

namespace App\Http\Resources;

use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Evaluation */
class EvaluationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participant_id' => $this->whenLoaded('participant', fn () => $this->participant->uuid),
            'coach_id' => $this->coach_id,
            'coach_name' => $this->whenLoaded('coach', fn () => $this->coach->user?->name),
            'evaluation_date' => $this->evaluation_date?->toDateString(),
            'next_target' => $this->next_target,
            'recommended_class_id' => $this->recommended_class_id,
            'details' => $this->whenLoaded('details', fn () => $this->details->map(fn ($detail) => [
                'aspect' => $detail->aspect,
                'score' => $detail->score,
                'note' => $detail->note,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
