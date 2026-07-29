<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingSchedule\CancelTrainingScheduleRequest;
use App\Http\Requests\TrainingSchedule\RescheduleTrainingScheduleRequest;
use App\Http\Requests\TrainingSchedule\StoreTrainingScheduleRequest;
use App\Http\Resources\TrainingScheduleResource;
use App\Models\Role;
use App\Models\TrainingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $schedules = TrainingSchedule::query()
            // Schedules have no branch_id of their own — isolate via the court's
            // branch instead (a schedule always books a court at one branch).
            ->when(
                ! $user->hasRole(Role::SUPER_ADMIN) && ! $user->hasRole(Role::MANAGEMENT) && $user->branch_id,
                fn ($query) => $query->whereHas('court', fn ($q) => $q->where('branch_id', $user->branch_id)),
            )
            ->when($request->string('from')->isNotEmpty(), fn ($query) => $query->whereDate('session_date', '>=', $request->string('from')))
            ->when($request->string('to')->isNotEmpty(), fn ($query) => $query->whereDate('session_date', '<=', $request->string('to')))
            ->when($request->string('coach_id')->isNotEmpty(), fn ($query) => $query->where('coach_id', $request->integer('coach_id')))
            ->when($request->string('court_id')->isNotEmpty(), fn ($query) => $query->where('court_id', $request->integer('court_id')))
            ->when($request->string('class_id')->isNotEmpty(), fn ($query) => $query->where('class_id', $request->integer('class_id')))
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => TrainingScheduleResource::collection($schedules->items()),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    public function store(StoreTrainingScheduleRequest $request): JsonResponse
    {
        $schedule = DB::transaction(function () use ($request) {
            $this->assertNoConflict(
                $request->validated('court_id'),
                $request->validated('coach_id'),
                $request->validated('session_date'),
                $request->validated('start_time'),
                $request->validated('end_time'),
            );

            return TrainingSchedule::query()->create([
                'class_id' => $request->validated('class_id'),
                'court_id' => $request->validated('court_id'),
                'coach_id' => $request->validated('coach_id'),
                'session_date' => $request->validated('session_date'),
                'start_time' => $request->validated('start_time'),
                'end_time' => $request->validated('end_time'),
                'type' => $request->validated('type') ?: TrainingSchedule::TYPE_REGULAR,
                'status' => TrainingSchedule::STATUS_SCHEDULED,
            ]);
        });

        return response()->json(['data' => new TrainingScheduleResource($schedule)], 201);
    }

    public function show(TrainingSchedule $schedule): JsonResponse
    {
        return response()->json(['data' => new TrainingScheduleResource($schedule)]);
    }

    public function cancel(CancelTrainingScheduleRequest $request, TrainingSchedule $schedule): JsonResponse
    {
        abort_if($schedule->status === TrainingSchedule::STATUS_CANCELLED, 422, 'Jadwal ini sudah dibatalkan.');

        $schedule->update([
            'status' => TrainingSchedule::STATUS_CANCELLED,
            'cancellation_reason' => $request->validated('reason'),
        ]);

        return response()->json(['data' => new TrainingScheduleResource($schedule)]);
    }

    public function reschedule(RescheduleTrainingScheduleRequest $request, TrainingSchedule $schedule): JsonResponse
    {
        $replacement = DB::transaction(function () use ($request, $schedule) {
            $this->assertNoConflict(
                $request->validated('court_id'),
                $request->validated('coach_id'),
                $request->validated('session_date'),
                $request->validated('start_time'),
                $request->validated('end_time'),
                excludeId: $schedule->id,
            );

            if ($schedule->status !== TrainingSchedule::STATUS_CANCELLED) {
                $schedule->update([
                    'status' => TrainingSchedule::STATUS_CANCELLED,
                    'cancellation_reason' => 'Dijadwalkan ulang.',
                ]);
            }

            return TrainingSchedule::query()->create([
                'class_id' => $schedule->class_id,
                'court_id' => $request->validated('court_id'),
                'coach_id' => $request->validated('coach_id'),
                'session_date' => $request->validated('session_date'),
                'start_time' => $request->validated('start_time'),
                'end_time' => $request->validated('end_time'),
                'type' => TrainingSchedule::TYPE_REPLACEMENT,
                'status' => TrainingSchedule::STATUS_SCHEDULED,
                'replaces_schedule_id' => $schedule->id,
            ]);
        });

        return response()->json(['data' => new TrainingScheduleResource($replacement)], 201);
    }

    private function assertNoConflict(int $courtId, int $coachId, string $date, string $start, string $end, ?int $excludeId = null): void
    {
        $conflicts = TrainingSchedule::query()
            ->whereDate('session_date', $date)
            ->where('status', '!=', TrainingSchedule::STATUS_CANCELLED)
            ->where(fn ($q) => $q->where('court_id', $courtId)->orWhere('coach_id', $coachId))
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->lockForUpdate()
            ->get();

        foreach ($conflicts as $conflict) {
            if ((int) $conflict->court_id === $courtId) {
                abort(422, "Lapangan sudah terpakai pada jadwal #{$conflict->id} ({$conflict->start_time}-{$conflict->end_time}).");
            }

            abort(422, "Pelatih sudah memiliki jadwal lain (#{$conflict->id}) pada waktu tersebut ({$conflict->start_time}-{$conflict->end_time}).");
        }
    }
}
