<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreEmergencyContactRequest;
use App\Http\Resources\EmergencyContactResource;
use App\Models\EmergencyContact;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmergencyContactController extends Controller
{
    public function index(Request $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        return response()->json(['data' => EmergencyContactResource::collection($participant->emergencyContacts)]);
    }

    public function store(StoreEmergencyContactRequest $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        $contact = $participant->emergencyContacts()->create($request->validated());

        return response()->json(['data' => new EmergencyContactResource($contact)], 201);
    }

    public function destroy(Request $request, Participant $participant, EmergencyContact $emergencyContact): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);
        abort_unless($emergencyContact->participant_id === $participant->id, 404);

        $emergencyContact->delete();

        return response()->json(['data' => ['message' => 'Kontak darurat berhasil dihapus.']]);
    }
}
