<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['uuid', 'branch_id', 'name', 'email', 'phone', 'password', 'status', 'locale'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_INACTIVE = 'inactive';

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->roles->pluck('slug')->intersect($slugs)->isNotEmpty();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles->loadMissing('permissions')
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->contains($slug);
    }

    /**
     * @return HasOne<Participant, $this>
     */
    public function participant(): HasOne
    {
        return $this->hasOne(Participant::class);
    }

    /**
     * @return HasOne<Guardian, $this>
     */
    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    /**
     * @return HasOne<Coach, $this>
     */
    public function coach(): HasOne
    {
        return $this->hasOne(Coach::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['email_verified_at' => now()])->save();
    }

    /**
     * HMAC-signed, time-limited token for the emailed verification link —
     * not routed through Laravel's own signed-route helpers since the link
     * points at the separate Next.js frontend (verifikasi-email page),
     * not a Laravel route the backend serves directly.
     */
    public static function emailVerificationSignature(int $id, int $expires): string
    {
        return hash_hmac('sha256', "{$id}|{$expires}", config('app.key'));
    }
}
