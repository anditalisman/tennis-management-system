<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreInjuryRequest;
use App\Http\Resources\InjuryResource;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InjuryController extends Controller
{
    public function index(Request $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        return response()->json(['data' => InjuryResource::collection($participant->injuries()->with('reporter')->get())]);
    }

    public function store(StoreInjuryRequest $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        $injury = $participant->injuries()->create([
            ...$request->validated(),
            'reported_by' => $request->user()->id,
        ]);

        return response()->json(['data' => new InjuryResource($injury->load('reporter'))], 201);
    }
}
