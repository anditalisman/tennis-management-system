<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'method' => $this->method,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'reference_no' => $this->reference_no,
            'proof_url' => $this->whenLoaded('confirmation', fn () => $this->confirmation?->proof_path
                ? Storage::disk('s3')->url($this->confirmation->proof_path)
                : null),
            'verified_at' => $this->whenLoaded('confirmation', fn () => $this->confirmation?->verified_at?->toIso8601String()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
