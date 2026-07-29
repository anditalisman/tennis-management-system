<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\VerifyCoachAttendanceRequest;
use App\Http\Resources\CoachAttendanceResource;
use App\Models\CoachAttendance;
use App\Models\Role;
use App\Models\TrainingSchedule;
use App\Models\TrainingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoachAttendanceController extends Controller
{
    public function record(Request $request, TrainingSchedule $schedule): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->coach && $user->coach->id === $schedule->coach_id, 403, 'Anda bukan pelatih yang ditugaskan pada sesi ini.');

        $session = $schedule->session ?? TrainingSession::query()->create(['schedule_id' => $schedule->id]);

        $attendance = CoachAttendance::query()->updateOrCreate(
            ['coach_id' => $user->coach->id, 'session_id' => $session->id],
            ['check_in_at' => now(), 'status' => CoachAttendance::STATUS_PRESENT],
        );

        return response()->json(['data' => new CoachAttendanceResource($attendance)], 201);
    }

    public function verify(VerifyCoachAttendanceRequest $request, TrainingSchedule $schedule): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR),
            403,
            'Hanya administrator yang dapat memverifikasi absensi pelatih.',
        );

        $session = $schedule->session;
        abort_if(! $session, 404, 'Belum ada catatan sesi untuk jadwal ini.');

        $attendance = CoachAttendance::query()
            ->where('session_id', $session->id)
            ->where('coach_id', $schedule->coach_id)
            ->firstOrFail();

        $attendance->update([
            'status' => $request->validated('status'),
            'verified_by' => $user->id,
        ]);

        return response()->json(['data' => new CoachAttendanceResource($attendance)]);
    }
}
