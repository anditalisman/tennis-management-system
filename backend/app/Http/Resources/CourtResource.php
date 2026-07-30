<?php

namespace App\Http\Resources;

use App\Models\Court;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Court */
class CourtResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // courts-inventory.view now also covers participant/guardian (so
        // they can see which court their session is on) — rental_cost is
        // internal-only and shouldn't come along for the ride.
        $canSeeCost = $request->user()?->hasAnyRole([Role::SUPER_ADMIN, Role::MANAGEMENT, Role::ADMINISTRATOR]) ?? false;

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'surface_type' => $this->surface_type,
            'operating_hours' => $this->operating_hours,
            'rental_cost' => $this->when($canSeeCost, fn () => $this->rental_cost !== null ? (float) $this->rental_cost : null),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
