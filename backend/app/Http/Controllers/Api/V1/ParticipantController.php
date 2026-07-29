<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ScopesToBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreParticipantRequest;
use App\Http\Requests\Participant\UpdateParticipantRequest;
use App\Http\Requests\Participant\VerifyParticipantRequest;
use App\Http\Resources\ParticipantPackageResource;
use App\Http\Resources\ParticipantResource;
use App\Models\Guardian;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\Referral;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParticipantController extends Controller
{
    use ScopesToBranch;

    public function index(Request $request): JsonResponse
    {
        $participants = $this->visibleParticipants($request->user())
            ->with(['guardians.user', 'user'])
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('branch_id')->isNotEmpty(), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")->orWhere('registration_no', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => ParticipantResource::collection($participants->items()),
            'meta' => [
                'current_page' => $participants->currentPage(),
                'last_page' => $participants->lastPage(),
                'per_page' => $participants->perPage(),
                'total' => $participants->total(),
            ],
        ]);
    }

    public function store(StoreParticipantRequest $request): JsonResponse
    {
        $participant = DB::transaction(function () use ($request) {
            $authUser = auth('sanctum')->user();
            $guardian = null;
            $userId = null;
            $needsGuardian = in_array($request->validated('age_category'), Participant::GUARDIAN_REQUIRED_CATEGORIES, true);

            if ($authUser) {
                $userId = $authUser->id;
            } elseif ($needsGuardian) {
                $guardianData = $request->validated('guardian');
                $guardianUser = User::query()->where('email', $guardianData['email'])->first();

                if ($guardianUser) {
                    if (! $guardianUser->guardian) {
                        throw ValidationException::withMessages([
                            'guardian.email' => ['Email sudah terdaftar dengan peran lain.'],
                        ]);
                    }
                    $guardian = $guardianUser->guardian;
                } else {
                    $guardianUser = User::query()->create([
                        'name' => $guardianData['name'],
                        'email' => $guardianData['email'],
                        'phone' => $guardianData['phone'],
                        'password' => $guardianData['password'],
                        'status' => User::STATUS_ACTIVE,
                        'locale' => app()->getLocale(),
                        'email_verified_at' => now(),
                    ]);
                    $guardianRole = Role::query()->where('slug', Role::GUARDIAN)->firstOrFail();
                    $guardianUser->roles()->attach($guardianRole);

                    $guardian = Guardian::query()->create([
                        'user_id' => $guardianUser->id,
                        'relation' => $guardianData['relation'],
                    ]);
                }
            } else {
                // Adult, self-registering without a guardian — create their own login account.
                $email = $request->validated('email');
                $participantUser = User::query()->where('email', $email)->first();

                if ($participantUser) {
                    if (! $participantUser->hasRole(Role::PARTICIPANT) || $participantUser->participant) {
                        throw ValidationException::withMessages([
                            'email' => ['Email sudah terdaftar dengan peran lain.'],
                        ]);
                    }
                } else {
                    $participantUser = User::query()->create([
                        'name' => $request->validated('full_name'),
                        'email' => $email,
                        'phone' => $request->validated('phone'),
                        'password' => $request->validated('password'),
                        'status' => User::STATUS_ACTIVE,
                        'locale' => app()->getLocale(),
                        'email_verified_at' => now(),
                    ]);
                    $participantRole = Role::query()->where('slug', Role::PARTICIPANT)->firstOrFail();
                    $participantUser->roles()->attach($participantRole);
                }

                $userId = $participantUser->id;
            }

            $participant = Participant::query()->create([
                'user_id' => $userId,
                'branch_id' => $request->validated('branch_id'),
                'full_name' => $request->validated('full_name'),
                'email' => $request->validated('email'),
                'birth_date' => $request->validated('birth_date'),
                'age_category' => $request->validated('age_category'),
                'gender' => $request->validated('gender'),
                'skill_level' => $request->validated('skill_level') ?: 'beginner',
                'phone' => $request->validated('phone'),
                'address' => $request->validated('address'),
                'status' => Participant::STATUS_PENDING_VERIFICATION,
                'policy_accepted_at' => now(),
            ]);

            if ($guardian) {
                $participant->guardians()->attach($guardian->id, ['is_primary' => true]);
            }

            if ($referralCode = $request->validated('referral_code')) {
                Referral::query()
                    ->where('code', $referralCode)
                    ->whereNull('referred_participant_id')
                    ->first()
                    ?->update(['referred_participant_id' => $participant->id, 'reward_status' => Referral::STATUS_REDEEMED]);
            }

            return $participant;
        });

        return response()->json(['data' => new ParticipantResource($participant->load('guardians.user', 'user'))], 201);
    }

    public function show(Request $request, Participant $participant): JsonResponse
    {
        $this->authorizeView($request->user(), $participant);

        return response()->json(['data' => new ParticipantResource($participant->load('guardians.user', 'user'))]);
    }

    public function update(UpdateParticipantRequest $request, Participant $participant): JsonResponse
    {
        $this->authorizeView($request->user(), $participant);

        $participant->fill($request->validated())->save();

        return response()->json(['data' => new ParticipantResource($participant->load('guardians.user', 'user'))]);
    }

    public function verify(VerifyParticipantRequest $request, Participant $participant): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR),
            403,
            'Anda tidak memiliki izin untuk memverifikasi peserta.',
        );

        $target = $request->validated('action') === 'approve' ? Participant::STATUS_ACTIVE : Participant::STATUS_REJECTED;

        abort_unless($participant->canTransitionTo($target), 422, "Peserta berstatus {$participant->status} tidak bisa diubah ke {$target}.");

        $participant->update(['status' => $target]);

        if ($recipient = $participant->notifiableUser()) {
            Notification::queue(
                $recipient,
                Notification::CHANNEL_EMAIL,
                $target === Participant::STATUS_ACTIVE ? 'Pendaftaran disetujui' : 'Pendaftaran ditolak',
                $target === Participant::STATUS_ACTIVE
                    ? "Pendaftaran {$participant->full_name} ({$participant->registration_no}) telah disetujui."
                    : "Pendaftaran {$participant->full_name} ({$participant->registration_no}) ditolak.",
            );
        }

        return response()->json(['data' => new ParticipantResource($participant->fresh()->load('guardians.user', 'user'))]);
    }

    public function packages(Request $request, Participant $participant): JsonResponse
    {
        $this->authorizeView($request->user(), $participant);

        return response()->json(['data' => ParticipantPackageResource::collection($participant->packages()->with('package')->get())]);
    }

    private function visibleParticipants(User $user): Builder
    {
        if ($user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR)) {
            return $this->scopeToStaffBranch(Participant::query(), $user);
        }

        if ($user->hasRole(Role::PARTICIPANT)) {
            return Participant::query()->where('user_id', $user->id);
        }

        if ($user->hasRole(Role::GUARDIAN) && $user->guardian) {
            return Participant::query()->whereHas('guardians', fn ($q) => $q->where('guardians.id', $user->guardian->id));
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }

    public function authorizeView(User $user, Participant $participant): void
    {
        $allowed = $this->visibleParticipants($user)->whereKey($participant->id)->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }
}
