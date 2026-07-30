<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\RestrictsParticipantAccess;
use App\Http\Controllers\Concerns\ScopesToBranch;
use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingClass\StoreTrainingClassRequest;
use App\Http\Requests\TrainingClass\UpdateTrainingClassRequest;
use App\Http\Resources\ParticipantResource;
use App\Http\Resources\TrainingClassResource;
use App\Models\ClassMember;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\TrainingClass;
use App\Models\WaitingList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingClassController extends Controller
{
    use RestrictsParticipantAccess, ScopesToBranch;

    public function index(Request $request): JsonResponse
    {
        $this->denyParticipantAndGuardian($request->user());

        $classes = $this->scopeToStaffBranch(TrainingClass::query(), $request->user())
            ->when($request->string('branch_id')->isNotEmpty(), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->string('program_id')->isNotEmpty(), fn ($query) => $query->where('program_id', $request->integer('program_id')))
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => TrainingClassResource::collection($classes->items()),
            'meta' => [
                'current_page' => $classes->currentPage(),
                'last_page' => $classes->lastPage(),
                'per_page' => $classes->perPage(),
                'total' => $classes->total(),
            ],
        ]);
    }

    public function store(StoreTrainingClassRequest $request): JsonResponse
    {
        $class = TrainingClass::query()->create($request->validated());

        return response()->json(['data' => new TrainingClassResource($class)], 201);
    }

    public function show(Request $request, TrainingClass $trainingClass): JsonResponse
    {
        $this->denyParticipantAndGuardian($request->user());

        return response()->json(['data' => new TrainingClassResource($trainingClass)]);
    }

    public function update(UpdateTrainingClassRequest $request, TrainingClass $trainingClass): JsonResponse
    {
        $data = $request->validated();
        $oldCapacity = $trainingClass->capacity_max;

        if (array_key_exists('capacity_max', $data)) {
            $activeCount = $trainingClass->activeMemberCount();
            abort_if(
                $data['capacity_max'] < $activeCount,
                422,
                "Kapasitas tidak boleh lebih kecil dari jumlah anggota aktif saat ini ({$activeCount}).",
            );
        }

        $trainingClass->fill($data)->save();

        if (($data['capacity_max'] ?? $oldCapacity) > $oldCapacity) {
            $this->promoteFromWaitingList($trainingClass, $data['capacity_max'] - $oldCapacity);
        }

        return response()->json(['data' => new TrainingClassResource($trainingClass->fresh())]);
    }

    public function removeMember(Request $request, TrainingClass $trainingClass, string $participant): JsonResponse
    {
        $member = $trainingClass->members()->where('participants.uuid', $participant)->firstOrFail();

        DB::transaction(function () use ($trainingClass, $member) {
            ClassMember::query()
                ->where('class_id', $trainingClass->id)
                ->where('participant_id', $member->id)
                ->update(['status' => ClassMember::STATUS_INACTIVE]);

            $this->promoteFromWaitingList($trainingClass, 1);
        });

        return response()->json(['data' => ['message' => 'Peserta dikeluarkan dari kelas.']]);
    }

    public function destroy(TrainingClass $trainingClass): JsonResponse
    {
        $trainingClass->delete();

        return response()->json(['data' => ['message' => 'Kelas berhasil dihapus.']]);
    }

    public function members(Request $request, TrainingClass $trainingClass): JsonResponse
    {
        $this->denyParticipantAndGuardian($request->user());

        $members = $trainingClass->members()->with('guardians.user', 'user')->get();

        return response()->json(['data' => ParticipantResource::collection($members)]);
    }

    public function enroll(Request $request, TrainingClass $trainingClass): JsonResponse
    {
        $request->validate(['participant_id' => ['required', 'string', 'exists:participants,uuid']]);

        $participant = Participant::query()->where('uuid', $request->string('participant_id'))->firstOrFail();

        $result = $this->enrollParticipant($trainingClass, $participant->id);

        if ($result === 'waiting_list') {
            return response()->json([
                'data' => ['status' => 'waiting_list', 'message' => 'Kelas penuh, peserta dimasukkan ke waiting list.'],
            ]);
        }

        return response()->json([
            'data' => ['status' => 'enrolled', 'message' => 'Peserta berhasil didaftarkan ke kelas.'],
        ], 201);
    }

    /**
     * Enroll a participant, or fall back to the waiting list if the class is
     * full. Shared by the direct enroll endpoint and trial-class conversion.
     */
    public function enrollParticipant(TrainingClass $trainingClass, int $participantId): string
    {
        return DB::transaction(function () use ($trainingClass, $participantId) {
            // Lock the class row so concurrent enrollments serialize on the same
            // capacity check instead of racing (see Tahap 1 doc §14 risk register).
            $class = TrainingClass::query()->whereKey($trainingClass->id)->lockForUpdate()->firstOrFail();

            $existing = ClassMember::query()->where('class_id', $class->id)->where('participant_id', $participantId)->first();
            if ($existing) {
                abort_if($existing->status === ClassMember::STATUS_ACTIVE, 422, 'Peserta sudah terdaftar di kelas ini.');
                $existing->update(['status' => ClassMember::STATUS_ACTIVE, 'joined_at' => now()]);

                return 'enrolled';
            }

            $activeCount = $class->members()->wherePivot('status', ClassMember::STATUS_ACTIVE)->count();

            if ($activeCount >= $class->capacity_max) {
                WaitingList::query()->firstOrCreate(
                    ['class_id' => $class->id, 'participant_id' => $participantId],
                    ['status' => WaitingList::STATUS_WAITING],
                );

                return 'waiting_list';
            }

            ClassMember::query()->create([
                'class_id' => $class->id,
                'participant_id' => $participantId,
                'status' => ClassMember::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);

            return 'enrolled';
        });
    }

    /**
     * Promote up to $slots participants from the FIFO waiting list into
     * active membership — triggered when capacity opens up (Tahap 1 backlog
     * PB-16). Each promoted participant is notified.
     */
    private function promoteFromWaitingList(TrainingClass $trainingClass, int $slots): void
    {
        if ($slots < 1) {
            return;
        }

        $entries = $trainingClass->waitingList()
            ->where('status', WaitingList::STATUS_WAITING)
            ->orderBy('created_at')
            ->limit($slots)
            ->get();

        foreach ($entries as $entry) {
            $result = $this->enrollParticipant($trainingClass, $entry->participant_id);

            if ($result === 'enrolled') {
                $entry->update(['status' => WaitingList::STATUS_CONVERTED]);

                if ($recipient = $entry->participant->notifiableUser()) {
                    Notification::queue(
                        $recipient,
                        Notification::CHANNEL_EMAIL,
                        'Kuota kelas tersedia',
                        "Kuota kelas {$trainingClass->name} telah tersedia dan {$entry->participant->full_name} otomatis terdaftar.",
                    );
                }
            }
        }
    }

    public function waitingList(Request $request, TrainingClass $trainingClass): JsonResponse
    {
        $this->denyParticipantAndGuardian($request->user());

        $entries = $trainingClass->waitingList()->with('participant')->orderBy('created_at')->get();

        return response()->json([
            'data' => $entries->map(fn (WaitingList $entry) => [
                'participant_id' => $entry->participant->uuid,
                'participant_name' => $entry->participant->full_name,
                'status' => $entry->status,
                'created_at' => $entry->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
