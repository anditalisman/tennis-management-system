<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'user_id', 'branch_id', 'registration_no', 'full_name', 'email', 'birth_date', 'age_category', 'gender', 'skill_level', 'phone', 'address', 'status', 'policy_accepted_at'])]
#[UseFactory(ParticipantFactory::class)]
class Participant extends Model
{
    use HasFactory, SoftDeletes;

    public const AGE_U10 = 'u10';

    public const AGE_U12 = 'u12';

    public const AGE_U14 = 'u14';

    public const AGE_U16 = 'u16';

    public const AGE_DEWASA = 'dewasa';

    public const AGE_PRESTASI = 'prestasi';

    /** @var array<int, string> */
    public const AGE_CATEGORIES = [
        self::AGE_U10,
        self::AGE_U12,
        self::AGE_U14,
        self::AGE_U16,
        self::AGE_DEWASA,
        self::AGE_PRESTASI,
    ];

    // Every category except "dewasa" (adult) needs a guardian account — minors
    // by age bracket, plus the competitive "prestasi" track regardless of age.
    /** @var array<int, string> */
    public const GUARDIAN_REQUIRED_CATEGORIES = [
        self::AGE_U10,
        self::AGE_U12,
        self::AGE_U14,
        self::AGE_U16,
        self::AGE_PRESTASI,
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_GRADUATED = 'graduated';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Allowed status => list of statuses it may transition to.
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_PENDING_VERIFICATION],
        self::STATUS_PENDING_VERIFICATION => [self::STATUS_REJECTED, self::STATUS_PENDING_PAYMENT, self::STATUS_ACTIVE],
        self::STATUS_PENDING_PAYMENT => [self::STATUS_ACTIVE, self::STATUS_REJECTED],
        self::STATUS_ACTIVE => [self::STATUS_INACTIVE, self::STATUS_GRADUATED, self::STATUS_SUSPENDED],
        self::STATUS_INACTIVE => [self::STATUS_ACTIVE],
        self::STATUS_SUSPENDED => [self::STATUS_ACTIVE, self::STATUS_INACTIVE],
        self::STATUS_REJECTED => [],
        self::STATUS_GRADUATED => [],
    ];

    protected static function booted(): void
    {
        static::creating(function (Participant $participant) {
            $participant->uuid ??= (string) Str::uuid();
            $participant->registration_no ??= static::generateRegistrationNo();
        });
    }

    /**
     * ZT-{tahun-bulan daftar}-{nomor urut, reset tiap bulan}, mis. ZT-202605-0001.
     * Sengaja tidak memakai prefix cabang — sistem tampil sebagai satu lokasi
     * ke pengguna meski data cabang masih ada di database untuk kebutuhan
     * internal, jadi nomor pendaftaran tidak boleh membocorkannya. Dihitung
     * global (bukan per cabang) karena `registration_no` unik di seluruh
     * tabel, bukan per cabang.
     */
    public static function generateRegistrationNo(): string
    {
        $period = now()->format('Ym');

        $sequence = static::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->withTrashed()
            ->count() + 1;

        return sprintf('ZT-%s-%04d', $period, $sequence);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    // Who to notify about this participant: their own account if they have one
    // (adult self-registration), otherwise the primary linked guardian.
    public function notifiableUser(): ?User
    {
        if ($this->user_id) {
            return $this->user;
        }

        $guardian = $this->guardians()->wherePivot('is_primary', true)->first()
            ?? $this->guardians()->first();

        return $guardian?->user;
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'policy_accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_participant')->withPivot('is_primary')->withTimestamps();
    }

    /**
     * @return HasMany<EmergencyContact, $this>
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    /**
     * @return HasMany<Injury, $this>
     */
    public function injuries(): HasMany
    {
        return $this->hasMany(Injury::class);
    }

    /**
     * @return HasMany<ParticipantPackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(ParticipantPackage::class);
    }
}
