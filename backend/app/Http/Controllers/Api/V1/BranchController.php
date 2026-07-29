<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    /**
     * Unauthenticated listing for the public site (location picker on the
     * registration form, "Lapangan/Lokasi" page) — deliberately thin, no
     * pagination/filters, only active branches.
     */
    public function publicIndex(): JsonResponse
    {
        $branches = Branch::query()->where('status', Branch::STATUS_ACTIVE)->orderBy('name')->get();

        return response()->json(['data' => BranchResource::collection($branches)]);
    }

    public function index(Request $request): JsonResponse
    {
        $branches = Branch::query()
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->string('search')->isNotEmpty(), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => BranchResource::collection($branches->items()),
            'meta' => [
                'current_page' => $branches->currentPage(),
                'last_page' => $branches->lastPage(),
                'per_page' => $branches->perPage(),
                'total' => $branches->total(),
            ],
        ]);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = Branch::query()->create([
            'name' => $request->validated('name'),
            'slug' => $request->validated('slug') ?: Str::slug($request->validated('name')),
            'address' => $request->validated('address'),
            'phone' => $request->validated('phone'),
            'geo_lat' => $request->validated('geo_lat'),
            'geo_lng' => $request->validated('geo_lng'),
            'status' => $request->validated('status') ?: Branch::STATUS_ACTIVE,
        ]);

        return response()->json(['data' => new BranchResource($branch)], 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        return response()->json(['data' => new BranchResource($branch)]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $branch->fill($request->validated())->save();

        return response()->json(['data' => new BranchResource($branch)]);
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $branch->delete();

        return response()->json(['data' => ['message' => 'Cabang berhasil dihapus.']]);
    }
}
