<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BulkVerifyAttendanceRequest;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Participant;
use App\Models\ParticipantPackage;
use App\Models\Role;
use App\Models\TrainingSchedule;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request, TrainingSchedule $schedule): JsonResponse
    {
        $session = $schedule->session;
        $records = $session
            ? $session->attendance()->with(['participant', 'verifier'])->get()
            : collect();

        $user = $request->user();
        if (! $this->isStaff($user)) {
            $records = $records->filter(fn (Attendance $attendance) => $this->canSeeParticipant($user, $attendance->participant));
        }

        return response()->json(['data' => AttendanceResource::collection($records->values())]);
    }

    public function checkIn(CheckInRequest $request, TrainingSchedule $schedule): JsonResponse
    {
        abort_if($schedule->status === TrainingSchedule::STATUS_CANCELLED, 422, 'Sesi ini sudah dibatalkan.');

        $user = $request->user();
        $participantUuid = $request->validated('participant_id');

        if ($participantUuid) {
            abort_unless(
                $this->isStaff($user) || ($user->coach && $user->coach->id === $schedule->coach_id),
                403,
                'Anda tidak memiliki izin untuk mencatat check-in peserta lain.',
            );
            $participantId = Participant::query()->where('uuid', $participantUuid)->firstOrFail()->id;
        } else {
            abort_unless($user->participant, 422, 'Akun ini tidak memiliki data peserta.');
            $participantId = $user->participant->id;
        }

        $isMember = $schedule->trainingClass->members()->where('participants.id', $participantId)->wherePivot('status', 'active')->exists();
        abort_unless($isMember, 422, 'Peserta bukan anggota aktif kelas ini.');

        $session = $schedule->session ?? TrainingSession::query()->create(['schedule_id' => $schedule->id]);

        $attendance = Attendance::query()->updateOrCreate(
            ['session_id' => $session->id, 'participant_id' => $participantId],
            [
                'method' => $request->validated('method') ?: Attendance::METHOD_MANUAL,
                'check_in_at' => now(),
                'status' => Attendance::STATUS_PENDING,
            ],
        );

        return response()->json(['data' => new AttendanceResource($attendance->load('participant'))], 201);
    }

    public function bulkVerify(BulkVerifyAttendanceRequest $request, TrainingSchedule $schedule): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN) || ($user->coach && $user->coach->id === $schedule->coach_id),
            403,
            'Hanya pelatih pengampu sesi ini atau super admin yang dapat memverifikasi absensi.',
        );
        abort_if($schedule->status === TrainingSchedule::STATUS_CANCELLED, 422, 'Sesi yang dibatalkan tidak dapat diverifikasi absensinya.');

        $session = $schedule->session ?? TrainingSession::query()->create(['schedule_id' => $schedule->id]);

        $records = DB::transaction(function () use ($request, $session, $user) {
            $results = [];

            foreach ($request->validated('records') as $record) {
                $participantId = Participant::query()->where('uuid', $record['participant_id'])->firstOrFail()->id;

                $attendance = Attendance::query()->firstOrNew([
                    'session_id' => $session->id,
                    'participant_id' => $participantId,
                ]);
                $oldStatus = $attendance->exists ? $attendance->status : null;

                $attendance->fill([
                    'status' => $record['status'],
                    'verified_by' => $user->id,
                ]);
                $attendance->save();

                if ($oldStatus !== $record['status']) {
                    AuditLog::query()->create([
                        'user_id' => $user->id,
                        'action' => 'attendance.verify',
                        'model_type' => Attendance::class,
                        'model_id' => $attendance->id,
                        'old_values' => ['status' => $oldStatus],
                        'new_values' => ['status' => $record['status']],
                    ]);

                    // Deduct a package session only the first time a record reaches a
                    // final Hadir/Terlambat status (Tahap 1 §15 #05). Correcting a
                    // status back and forth afterwards does not re-run this — that
                    // reversal path is intentionally out of scope for now.
                    $wasDeducting = in_array($oldStatus, Attendance::DEDUCTING_STATUSES, true);
                    $nowDeducting = in_array($record['status'], Attendance::DEDUCTING_STATUSES, true);
                    if (! $wasDeducting && $nowDeducting) {
                        $this->deductPackageSession($attendance->participant_id);
                    }
                }

                $results[] = $attendance;
            }

            return $results;
        });

        return response()->json(['data' => AttendanceResource::collection(EloquentCollection::make($records)->load('participant'))]);
    }

    private function deductPackageSession(int $participantId): void
    {
        $package = ParticipantPackage::query()
            ->where('participant_id', $participantId)
            ->where('status', ParticipantPackage::STATUS_ACTIVE)
            ->where('sessions_remaining', '>', 0)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', now()->toDateString()))
            ->orderBy('valid_until')
            ->lockForUpdate()
            ->first();

        if (! $package) {
            return;
        }

        $package->decrement('sessions_remaining');

        if ($package->sessions_remaining <= 0) {
            $package->update(['status' => ParticipantPackage::STATUS_EXHAUSTED]);
        }
    }

    public function stats(Request $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        $counts = Attendance::query()
            ->where('participant_id', $participant->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json(['data' => ['participant_id' => $participant->uuid, 'by_status' => $counts]]);
    }

    private function isStaff(User $user): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR);
    }

    private function canSeeParticipant(User $user, Participant $participant): bool
    {
        if ($user->participant && $user->participant->id === $participant->id) {
            return true;
        }

        if ($user->guardian) {
            return $user->guardian->participants()->where('participants.id', $participant->id)->exists();
        }

        if ($user->coach) {
            return true;
        }

        return false;
    }
}
