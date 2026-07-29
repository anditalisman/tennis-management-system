<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attendance */
class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'participant_id' => $this->whenLoaded('participant', fn () => $this->participant->uuid),
            'participant_name' => $this->whenLoaded('participant', fn () => $this->participant->full_name),
            'status' => $this->status,
            'method' => $this->method,
            'verified_by' => $this->whenLoaded('verifier', fn () => $this->verifier?->name),
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_out_at' => $this->check_out_at?->toIso8601String(),
        ];
    }
}
