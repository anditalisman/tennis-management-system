<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrialClass\StoreTrialClassRequest;
use App\Http\Resources\TrialClassResource;
use App\Models\Participant;
use App\Models\TrainingClass;
use App\Models\TrialClass;
use Illuminate\Http\JsonResponse;

class TrialClassController extends Controller
{
    public function index(TrainingClass $trainingClass): JsonResponse
    {
        $trials = TrialClass::query()
            ->where('class_id', $trainingClass->id)
            ->with('participant')
            ->orderByDesc('trial_date')
            ->get();

        return response()->json(['data' => TrialClassResource::collection($trials)]);
    }

    public function store(StoreTrialClassRequest $request, TrainingClass $trainingClass): JsonResponse
    {
        $participant = Participant::query()->where('uuid', $request->validated('participant_id'))->firstOrFail();

        $trial = TrialClass::query()->create([
            'class_id' => $trainingClass->id,
            'participant_id' => $participant->id,
            'trial_date' => $request->validated('trial_date'),
        ]);

        return response()->json(['data' => new TrialClassResource($trial)], 201);
    }

    public function convert(TrialClass $trialClass): JsonResponse
    {
        abort_if($trialClass->converted_to_member, 422, 'Trial ini sudah dikonversi menjadi anggota.');

        $result = app(TrainingClassController::class)->enrollParticipant($trialClass->trainingClass, $trialClass->participant_id);

        $trialClass->update(['converted_to_member' => true]);

        return response()->json(['data' => [
            'trial_class' => new TrialClassResource($trialClass->fresh()),
            'enrollment_status' => $result,
        ]]);
    }
}
