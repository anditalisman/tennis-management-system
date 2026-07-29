<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\StoreCoachRequest;
use App\Http\Requests\Coach\UpdateCoachRequest;
use App\Http\Resources\CoachResource;
use App\Models\Coach;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coaches = Coach::query()
            ->with('user')
            ->when($request->string('branch_id')->isNotEmpty(), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->string('employment_status')->isNotEmpty(), fn ($query) => $query->where('employment_status', $request->string('employment_status')))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => CoachResource::collection($coaches->items()),
            'meta' => [
                'current_page' => $coaches->currentPage(),
                'last_page' => $coaches->lastPage(),
                'per_page' => $coaches->perPage(),
                'total' => $coaches->total(),
            ],
        ]);
    }

    public function store(StoreCoachRequest $request): JsonResponse
    {
        $this->authorizeStaff($request);

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'branch_id' => $request->validated('branch_id'),
            'status' => User::STATUS_ACTIVE,
            'locale' => app()->getLocale(),
            'email_verified_at' => now(),
        ]);

        $coachRole = Role::query()->where('slug', Role::COACH)->firstOrFail();
        $user->roles()->attach($coachRole);

        $coach = Coach::query()->create([
            'user_id' => $user->id,
            'branch_id' => $request->validated('branch_id'),
            'certifications' => $request->validated('certifications'),
            'bio' => $request->validated('bio'),
            'employment_status' => $request->validated('employment_status') ?: Coach::STATUS_ACTIVE,
        ]);

        return response()->json(['data' => new CoachResource($coach->load('user'))], 201);
    }

    public function show(Coach $coach): JsonResponse
    {
        return response()->json(['data' => new CoachResource($coach->load('user'))]);
    }

    public function update(UpdateCoachRequest $request, Coach $coach): JsonResponse
    {
        $this->authorizeManage($request, $coach);

        $coach->fill($request->validated())->save();

        return response()->json(['data' => new CoachResource($coach->load('user'))]);
    }

    public function destroy(Request $request, Coach $coach): JsonResponse
    {
        $this->authorizeStaff($request);

        $coach->delete();

        return response()->json(['data' => ['message' => 'Data pelatih berhasil dihapus.']]);
    }

    private function authorizeManage(Request $request, Coach $coach): void
    {
        $user = $request->user();

        if ($user && $user->id === $coach->user_id) {
            return;
        }

        $this->authorizeStaff($request);
    }

    // coaches.manage is also held by the "coach" role itself (self-service only,
    // per the access matrix E-scope) — so full manage rights require staff roles.
    private function authorizeStaff(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR)),
            403,
            'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
        );
    }
}
