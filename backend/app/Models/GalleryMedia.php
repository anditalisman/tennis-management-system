<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gallery_id', 'file_path', 'type', 'thumbnail_path', 'tags'])]
class GalleryMedia extends Model
{
    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Gallery, $this>
     */
    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }
}
