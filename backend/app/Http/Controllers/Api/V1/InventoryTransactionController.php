<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryTransactionRequest;
use App\Http\Resources\InventoryTransactionResource;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InventoryTransactionController extends Controller
{
    public function index(InventoryItem $inventoryItem): JsonResponse
    {
        $transactions = $inventoryItem->transactions()->with('participant', 'creator')->orderByDesc('created_at')->get();

        return response()->json(['data' => InventoryTransactionResource::collection($transactions)]);
    }

    public function store(StoreInventoryTransactionRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $transaction = DB::transaction(function () use ($request, $inventoryItem) {
            // Lock the item row so concurrent in/out transactions serialize instead
            // of racing on stock_qty (same pattern as class enrollment/schedules).
            $item = InventoryItem::query()->whereKey($inventoryItem->id)->lockForUpdate()->firstOrFail();

            $qty = $request->validated('qty');
            $isDecreasing = in_array($request->validated('type'), InventoryTransaction::DECREASING_TYPES, true);
            $delta = $isDecreasing ? -$qty : $qty;

            abort_if($isDecreasing && ($item->stock_qty + $delta) < 0, 422, 'Stok tidak mencukupi untuk transaksi ini.');

            $item->increment('stock_qty', $delta);

            return InventoryTransaction::query()->create([
                'item_id' => $item->id,
                'type' => $request->validated('type'),
                'qty' => $qty,
                'participant_id' => $request->validated('participant_id'),
                'created_by' => $request->user()->id,
                'note' => $request->validated('note'),
            ]);
        });

        return response()->json(['data' => new InventoryTransactionResource($transaction->load('participant', 'creator'))], 201);
    }
}
