<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('roles')
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('branch_id')->isNotEmpty(), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->string('role')->isNotEmpty(), function ($query) use ($request) {
                $query->whereHas('roles', fn ($roles) => $roles->where('slug', $request->string('role')));
            })
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => UserResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'branch_id' => $request->validated('branch_id'),
            'status' => $request->validated('status') ?: User::STATUS_ACTIVE,
            'locale' => app()->getLocale(),
            'email_verified_at' => now(),
        ]);

        $roleIds = Role::query()->whereIn('slug', $request->validated('roles'))->pluck('id');
        $user->roles()->attach($roleIds);

        return response()->json(['data' => new UserResource($user->load('roles'))], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user->load('roles'))]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->fill($request->safe()->except('roles', 'password'))->save();

        if ($request->filled('password')) {
            $user->forceFill(['password' => $request->validated('password')])->save();
        }

        if ($request->has('roles')) {
            $roleIds = Role::query()->whereIn('slug', $request->validated('roles'))->pluck('id');
            $user->roles()->sync($roleIds);
        }

        return response()->json(['data' => new UserResource($user->load('roles'))]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['data' => ['message' => 'Pengguna berhasil dinonaktifkan.']]);
    }
}
