<?php

namespace App\Http\Resources;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Voucher */
class VoucherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount_type' => $this->discount_type,
            'discount_value' => (float) $this->discount_value,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
