<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClassMember;
use App\Models\Gallery;
use App\Models\Participant;
use App\Models\Role;
use App\Models\TrainingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR)) {
            return response()->json(['data' => [
                'scope' => 'staff',
                'participants_active' => Participant::query()->where('status', Participant::STATUS_ACTIVE)->count(),
                'participants_pending_verification' => Participant::query()->where('status', Participant::STATUS_PENDING_VERIFICATION)->count(),
                'schedules_upcoming_7d' => TrainingSchedule::query()
                    ->where('status', TrainingSchedule::STATUS_SCHEDULED)
                    ->whereBetween('session_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
                'galleries_pending_moderation' => Gallery::query()->where('status', Gallery::STATUS_PENDING)->count(),
            ]]);
        }

        if ($user->coach) {
            return response()->json(['data' => [
                'scope' => 'coach',
                'my_schedules_upcoming_7d' => TrainingSchedule::query()
                    ->where('coach_id', $user->coach->id)
                    ->where('status', TrainingSchedule::STATUS_SCHEDULED)
                    ->whereBetween('session_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                    ->count(),
            ]]);
        }

        if ($user->participant) {
            return response()->json(['data' => [
                'scope' => 'participant',
                'my_active_classes' => ClassMember::query()
                    ->where('participant_id', $user->participant->id)
                    ->where('status', ClassMember::STATUS_ACTIVE)
                    ->count(),
            ]]);
        }

        if ($user->guardian) {
            return response()->json(['data' => [
                'scope' => 'guardian',
                'children_count' => $user->guardian->participants()->count(),
            ]]);
        }

        return response()->json(['data' => ['scope' => 'none']]);
    }
}
