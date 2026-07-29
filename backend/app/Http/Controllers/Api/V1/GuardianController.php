<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\LinkGuardianRequest;
use App\Http\Resources\ParticipantResource;
use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GuardianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR),
            403,
            'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
        );

        $guardians = Guardian::query()
            ->with(['user', 'participants'])
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search');
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $guardians->getCollection()->map(fn (Guardian $guardian) => [
                'id' => $guardian->id,
                'name' => $guardian->user->name,
                'email' => $guardian->user->email,
                'phone' => $guardian->user->phone,
                'relation' => $guardian->relation,
                'participant_count' => $guardian->participants->count(),
            ]),
            'meta' => [
                'current_page' => $guardians->currentPage(),
                'last_page' => $guardians->lastPage(),
                'per_page' => $guardians->perPage(),
                'total' => $guardians->total(),
            ],
        ]);
    }

    public function participants(Request $request, Guardian $guardian): JsonResponse
    {
        $user = $request->user();
        $isSelf = $user->guardian && $user->guardian->id === $guardian->id;

        abort_unless(
            $isSelf || $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR),
            403,
            'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
        );

        $participants = $guardian->participants()->with(['guardians.user', 'user'])->get();

        return response()->json(['data' => ParticipantResource::collection($participants)]);
    }

    public function link(LinkGuardianRequest $request, Participant $participant): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR),
            403,
            'Anda tidak memiliki izin untuk mengelola wali peserta.',
        );

        $guardianUser = User::query()->where('email', $request->validated('email'))->firstOrFail();

        if (! $guardianUser->guardian) {
            throw ValidationException::withMessages(['email' => ['Pengguna ini bukan akun wali.']]);
        }

        $participant->guardians()->syncWithoutDetaching([
            $guardianUser->guardian->id => ['is_primary' => (bool) $request->validated('is_primary')],
        ]);

        return response()->json(['data' => new ParticipantResource($participant->load('guardians.user', 'user'))]);
    }
}
