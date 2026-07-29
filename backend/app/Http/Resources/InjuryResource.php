<?php

namespace App\Http\Resources;

use App\Models\Injury;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Injury */
class InjuryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'severity' => $this->severity,
            'reported_at' => $this->reported_at?->toDateString(),
            'reported_by' => $this->whenLoaded('reporter', fn () => $this->reporter?->name),
        ];
    }
}
