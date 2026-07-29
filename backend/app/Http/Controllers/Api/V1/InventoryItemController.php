<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ScopesToBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Requests\Inventory\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    use ScopesToBranch;

    public function index(Request $request): JsonResponse
    {
        $items = $this->scopeToStaffBranch(InventoryItem::query(), $request->user())
            ->when($request->string('branch_id')->isNotEmpty(), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->string('category')->isNotEmpty(), fn ($query) => $query->where('category', $request->string('category')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => InventoryItemResource::collection($items->items()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = InventoryItem::query()->create($request->validated());

        return response()->json(['data' => new InventoryItemResource($item)], 201);
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json(['data' => new InventoryItemResource($inventoryItem)]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->fill($request->validated())->save();

        return response()->json(['data' => new InventoryItemResource($inventoryItem)]);
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->delete();

        return response()->json(['data' => ['message' => 'Barang inventaris berhasil dihapus.']]);
    }
}
