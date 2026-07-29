<?php

namespace App\Http\Resources;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Gallery */
class GalleryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'session_id' => $this->session_id,
            'title' => $this->title,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'uploaded_by' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media) => [
                'id' => $media->id,
                'type' => $media->type,
                'url' => Storage::disk('s3')->url($media->file_path),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
