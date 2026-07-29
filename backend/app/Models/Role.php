<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description'])]
#[UseFactory(RoleFactory::class)]
class Role extends Model
{
    use HasFactory;

    public const SUPER_ADMIN = 'super-admin';

    public const MANAGEMENT = 'management';

    public const ADMINISTRATOR = 'administrator';

    public const COACH = 'coach';

    public const PARTICIPANT = 'participant';

    public const GUARDIAN = 'guardian';

    public const FINANCE = 'finance';

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }
}
