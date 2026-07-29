<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['notification_id', 'status', 'retry_count', 'provider_response'])]
class NotificationLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'provider_response' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Notification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
