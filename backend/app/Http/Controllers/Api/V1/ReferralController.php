<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReferralResource;
use App\Models\Participant;
use App\Models\Referral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function store(Request $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        $referral = Referral::query()->create(['referrer_participant_id' => $participant->id]);

        return response()->json(['data' => new ReferralResource($referral)], 201);
    }

    public function index(Request $request, Participant $participant): JsonResponse
    {
        app(ParticipantController::class)->authorizeView($request->user(), $participant);

        $referrals = Referral::query()
            ->where('referrer_participant_id', $participant->id)
            ->with('referred')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => ReferralResource::collection($referrals)]);
    }
}
