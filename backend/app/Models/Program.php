<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'age_group', 'skill_level', 'target_competency', 'description', 'status'])]
#[UseFactory(ProgramFactory::class)]
class Program extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * @return HasMany<TrainingClass, $this>
     */
    public function classes(): HasMany
    {
        return $this->hasMany(TrainingClass::class, 'program_id');
    }
}
