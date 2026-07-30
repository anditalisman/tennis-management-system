<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ClassMember;
use App\Models\Role;
use App\Models\User;

trait RestrictsParticipantAccess
{
    /**
     * Program/Kelas/Paket catalog data is staff/coach territory — participants
     * and guardians only ever need the schedule, evaluation, and gallery data
     * scoped to what they're actually enrolled in (see the other helper
     * below), never the raw catalog itself.
     */
    protected function denyParticipantAndGuardian(User $user): void
    {
        abort_if(
            $user->hasRole(Role::PARTICIPANT) || $user->hasRole(Role::GUARDIAN),
            403,
            'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
        );
    }

    /**
     * Class IDs a participant (or a guardian's participants) is actively
     * enrolled in — the scoping unit for "my schedules" / "my gallery",
     * since both TrainingSchedule and Gallery key off class_id. Returns an
     * empty array (never null) so callers can safely whereIn() with it.
     *
     * @return array<int, int>
     */
    protected function enrolledClassIds(User $user): array
    {
        if ($user->hasRole(Role::PARTICIPANT) && $user->participant) {
            return ClassMember::query()
                ->where('participant_id', $user->participant->id)
                ->where('status', ClassMember::STATUS_ACTIVE)
                ->pluck('class_id')
                ->all();
        }

        if ($user->hasRole(Role::GUARDIAN) && $user->guardian) {
            $participantIds = $user->guardian->participants()->pluck('participants.id');

            return ClassMember::query()
                ->whereIn('participant_id', $participantIds)
                ->where('status', ClassMember::STATUS_ACTIVE)
                ->pluck('class_id')
                ->all();
        }

        return [];
    }
}
