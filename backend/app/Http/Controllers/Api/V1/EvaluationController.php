<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Evaluation\StoreEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use App\Models\EvaluationDetail;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->coach || $user->hasRole(Role::SUPER_ADMIN), 403, 'Hanya pelatih yang dapat membuat evaluasi.');

        $coachId = $user->coach?->id ?? $request->validated('coach_id');
        abort_unless($coachId, 422, 'coach_id wajib diisi.');

        $participantId = Participant::query()->where('uuid', $request->validated('participant_id'))->firstOrFail()->id;

        $evaluation = DB::transaction(function () use ($request, $coachId, $participantId) {
            $evaluation = Evaluation::query()->create([
                'participant_id' => $participantId,
                'coach_id' => $coachId,
                'evaluation_date' => $request->validated('evaluation_date'),
                'next_target' => $request->validated('next_target'),
                'recommended_class_id' => $request->validated('recommended_class_id'),
            ]);

            foreach ($request->validated('details') as $detail) {
                EvaluationDetail::query()->create([
                    'evaluation_id' => $evaluation->id,
                    'aspect' => $detail['aspect'],
                    'score' => $detail['score'],
                    'note' => $detail['note'] ?? null,
                ]);
            }

            return $evaluation;
        });

        return response()->json(['data' => new EvaluationResource($evaluation->load('details', 'participant', 'coach.user'))], 201);
    }

    public function show(Request $request, Evaluation $evaluation): JsonResponse
    {
        $this->authorizeView($request->user(), $evaluation);

        return response()->json(['data' => new EvaluationResource($evaluation->load('details', 'participant', 'coach.user'))]);
    }

    public function forParticipant(Request $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        $evaluations = Evaluation::query()
            ->where('participant_id', $participant->id)
            ->with('details', 'coach.user')
            ->orderByDesc('evaluation_date')
            ->get();

        return response()->json(['data' => EvaluationResource::collection($evaluations)]);
    }

    private function visibleEvaluations(User $user): Builder
    {
        if ($user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::MANAGEMENT) || $user->hasRole(Role::ADMINISTRATOR)) {
            return Evaluation::query();
        }

        if ($user->coach) {
            return Evaluation::query()->where('coach_id', $user->coach->id);
        }

        if ($user->hasRole(Role::PARTICIPANT) && $user->participant) {
            return Evaluation::query()->where('participant_id', $user->participant->id);
        }

        if ($user->hasRole(Role::GUARDIAN) && $user->guardian) {
            $participantIds = $user->guardian->participants()->pluck('participants.id');

            return Evaluation::query()->whereIn('participant_id', $participantIds);
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }

    private function authorizeView(User $user, Evaluation $evaluation): void
    {
        $allowed = $this->visibleEvaluations($user)->whereKey($evaluation->id)->exists();

        abort_unless($allowed, 403, 'Anda tidak memiliki izin untuk mengakses sumber daya ini.');
    }
}
