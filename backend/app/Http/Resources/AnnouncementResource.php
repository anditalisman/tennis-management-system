<?php

namespace App\Http\Resources;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Announcement */
class AnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'status' => $this->status,
            'publish_at' => $this->publish_at?->toIso8601String(),
            'expire_at' => $this->expire_at?->toIso8601String(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
