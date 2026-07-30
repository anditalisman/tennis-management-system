<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ScopesToBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Court\StoreCourtRequest;
use App\Http\Requests\Court\UpdateCourtRequest;
use App\Http\Resources\CourtResource;
use App\Models\Court;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    use ScopesToBranch;

    public function index(Request $request): JsonResponse
    {
        $courts = $this->scopeToStaffBranch(Court::query(), $request->user())
            ->when($request->string('branch_id')->isNotEmpty(), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => CourtResource::collection($courts->items()),
            'meta' => [
                'current_page' => $courts->currentPage(),
                'last_page' => $courts->lastPage(),
                'per_page' => $courts->perPage(),
                'total' => $courts->total(),
            ],
        ]);
    }

    public function store(StoreCourtRequest $request): JsonResponse
    {
        $court = Court::query()->create($request->validated());

        return response()->json(['data' => new CourtResource($court)], 201);
    }

    public function show(Request $request, Court $court): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN)
                || $user->hasPermission('courts-inventory.view')
                || $user->hasPermission('courts-inventory.manage')
                || $user->hasAnyRole([Role::PARTICIPANT, Role::GUARDIAN]),
            403,
            'Anda tidak memiliki izin untuk melihat lapangan ini.',
        );

        return response()->json(['data' => new CourtResource($court)]);
    }

    public function update(UpdateCourtRequest $request, Court $court): JsonResponse
    {
        $court->fill($request->validated())->save();

        return response()->json(['data' => new CourtResource($court)]);
    }

    public function destroy(Court $court): JsonResponse
    {
        $court->delete();

        return response()->json(['data' => ['message' => 'Lapangan berhasil dihapus.']]);
    }
}
