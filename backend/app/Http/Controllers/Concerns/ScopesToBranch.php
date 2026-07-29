<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesToBranch
{
    /**
     * Restrict a query to the acting staff member's branch (Tahap 1 §03/UC-16
     * multi-branch isolation). Super-admin and management always see every
     * branch. A staff account not yet assigned to a branch (branch_id null)
     * is intentionally left unrestricted rather than seeing nothing — that's
     * an onboarding gap to close by requiring branch_id at account creation,
     * not something this scope should silently hide behind an empty result.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scopeToStaffBranch(Builder $query, User $user, string $branchColumn = 'branch_id'): Builder
    {
        if ($user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT)) {
            return $query;
        }

        if ($user->branch_id) {
            return $query->where($branchColumn, $user->branch_id);
        }

        return $query;
    }
}
