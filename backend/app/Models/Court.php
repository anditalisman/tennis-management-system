<?php

namespace App\Models;

use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['branch_id', 'name', 'surface_type', 'operating_hours', 'rental_cost', 'status'])]
#[UseFactory(CourtFactory::class)]
class Court extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_MAINTENANCE = 'maintenance';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
            'rental_cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
