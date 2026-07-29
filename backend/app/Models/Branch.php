<?php

namespace App\Models;

use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'address', 'phone', 'geo_lat', 'geo_lng', 'status'])]
#[UseFactory(BranchFactory::class)]
class Branch extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected function casts(): array
    {
        return [
            'geo_lat' => 'decimal:7',
            'geo_lng' => 'decimal:7',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
