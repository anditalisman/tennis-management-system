<?php

namespace App\Http\Resources;

use App\Models\TrainingSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TrainingSchedule */
class TrainingScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'court_id' => $this->court_id,
            'coach_id' => $this->coach_id,
            'session_date' => $this->session_date?->toDateString(),
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'type' => $this->type,
            'status' => $this->status,
            'cancellation_reason' => $this->cancellation_reason,
            'replaces_schedule_id' => $this->replaces_schedule_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
