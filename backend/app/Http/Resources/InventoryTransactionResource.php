<?php

namespace App\Http\Resources;

use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InventoryTransaction */
class InventoryTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'type' => $this->type,
            'qty' => $this->qty,
            'participant_id' => $this->whenLoaded('participant', fn () => $this->participant?->uuid),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
