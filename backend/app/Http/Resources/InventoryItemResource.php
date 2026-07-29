<?php

namespace App\Http\Resources;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryItem */
class InventoryItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'category' => $this->category,
            'stock_qty' => $this->stock_qty,
            'condition' => $this->condition,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
