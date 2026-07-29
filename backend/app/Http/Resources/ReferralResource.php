<?php

namespace App\Http\Resources;

use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Referral */
class ReferralResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'referred_participant_name' => $this->whenLoaded('referred', fn () => $this->referred?->full_name),
            'reward_status' => $this->reward_status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
