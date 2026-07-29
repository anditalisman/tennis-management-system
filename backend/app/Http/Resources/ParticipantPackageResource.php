<?php

namespace App\Http\Resources;

use App\Models\ParticipantPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ParticipantPackage */
class ParticipantPackageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package_id' => $this->package_id,
            'package_name' => $this->whenLoaded('package', fn () => $this->package->name),
            'sessions_remaining' => $this->sessions_remaining,
            'valid_until' => $this->valid_until?->toDateString(),
            'status' => $this->status,
            'purchased_at' => $this->purchased_at?->toIso8601String(),
        ];
    }
}
