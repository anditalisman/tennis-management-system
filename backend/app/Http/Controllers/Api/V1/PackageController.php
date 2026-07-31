<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $packages = Package::query()
            ->when($request->string('type')->isNotEmpty(), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => PackageResource::collection($packages->items()),
            'meta' => [
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'per_page' => $packages->perPage(),
                'total' => $packages->total(),
            ],
        ]);
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $package = Package::query()->create($request->validated());

        return response()->json(['data' => new PackageResource($package)], 201);
    }

    public function show(Package $package): JsonResponse
    {
        return response()->json(['data' => new PackageResource($package)]);
    }

    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $package->fill($request->validated())->save();

        return response()->json(['data' => new PackageResource($package)]);
    }

    public function destroy(Package $package): JsonResponse
    {
        $package->delete();

        return response()->json(['data' => ['message' => 'Paket berhasil dihapus.']]);
    }
}
