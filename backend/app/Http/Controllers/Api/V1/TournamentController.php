<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tournament\StoreTournamentParticipantRequest;
use App\Http\Requests\Tournament\UpdateTournamentParticipantRequest;
use App\Http\Resources\TournamentMatchResource;
use App\Http\Resources\TournamentParticipantResource;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use App\Models\TournamentTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    private const MATCH_RELATIONS = ['teamOne.participantOne', 'teamOne.participantTwo', 'teamTwo.participantOne', 'teamTwo.participantTwo'];

    public function index(): JsonResponse
    {
        $participants = TournamentParticipant::query()->orderBy('created_at')->get();

        return response()->json(['data' => TournamentParticipantResource::collection($participants)]);
    }

    public function store(StoreTournamentParticipantRequest $request): JsonResponse
    {
        $participant = TournamentParticipant::query()->create($request->validated());

        return response()->json(['data' => new TournamentParticipantResource($participant)], 201);
    }

    /**
     * Lets an admin correct a participant's category (e.g. picked the wrong
     * one at sign-up) before the draw is run. An existing draw, if any, is
     * left untouched — re-run generateDraw() to redraw with the correction.
     */
    public function update(UpdateTournamentParticipantRequest $request, TournamentParticipant $tournamentParticipant): JsonResponse
    {
        $tournamentParticipant->update($request->validated());

        return response()->json(['data' => new TournamentParticipantResource($tournamentParticipant)]);
    }

    public function matches(): JsonResponse
    {
        $matches = TournamentMatch::query()
            ->with(self::MATCH_RELATIONS)
            ->orderBy('position')
            ->get();

        return response()->json(['data' => TournamentMatchResource::collection($matches)]);
    }

    /**
     * Wipes the current draw (teams + bracket) without redrawing — e.g. so
     * an admin can fix participant categories with a clean slate before
     * running generateDraw() again.
     */
    public function clearDraw(): JsonResponse
    {
        DB::transaction(function () {
            TournamentMatch::query()->delete();
            TournamentTeam::query()->delete();
        });

        return response()->json(['data' => []]);
    }

    /**
     * Draws the tournament in two steps:
     *
     * 1. Partner up participants into teams — a Beginner with an Upper
     *    Beginner wherever both are available (they play AS a pair, not
     *    against each other). Any surplus once one category runs out
     *    (uneven sign-ups) is partnered off among itself, and a lone
     *    participant left after that gets a partner-less "solo" team.
     * 2. Draw the single-elimination bracket for those teams — team plays
     *    against team. An odd number of teams leaves one with a bye
     *    (advances automatically).
     *
     * Re-running this discards the previous draw and redraws from scratch.
     */
    public function generateDraw(): JsonResponse
    {
        $beginners = TournamentParticipant::query()
            ->where('category', TournamentParticipant::CATEGORY_BEGINNER)
            ->pluck('id')
            ->shuffle()
            ->values();

        $upperBeginners = TournamentParticipant::query()
            ->where('category', TournamentParticipant::CATEGORY_UPPER_BEGINNER)
            ->pluck('id')
            ->shuffle()
            ->values();

        $matched = min($beginners->count(), $upperBeginners->count());

        /** @var Collection<int, array{0: int, 1: int|null}> $teamSlots */
        $teamSlots = collect();
        for ($i = 0; $i < $matched; $i++) {
            $teamSlots->push([$beginners[$i], $upperBeginners[$i]]);
        }

        $leftover = $beginners->slice($matched)->merge($upperBeginners->slice($matched))->shuffle()->values();
        for ($i = 0; $i < $leftover->count(); $i += 2) {
            $teamSlots->push([$leftover[$i], $leftover->get($i + 1)]);
        }

        $teamSlots = $teamSlots->shuffle()->values();

        DB::transaction(function () use ($teamSlots) {
            TournamentMatch::query()->delete();
            TournamentTeam::query()->delete();

            $teamIds = $teamSlots->map(fn (array $slot) => TournamentTeam::query()->create([
                'participant_one_id' => $slot[0],
                'participant_two_id' => $slot[1],
            ])->id)->shuffle()->values();

            $position = 1;
            for ($i = 0; $i < $teamIds->count(); $i += 2) {
                TournamentMatch::query()->create([
                    'round' => 1,
                    'position' => $position++,
                    'team_one_id' => $teamIds[$i],
                    'team_two_id' => $teamIds->get($i + 1),
                ]);
            }
        });

        $matches = TournamentMatch::query()->with(self::MATCH_RELATIONS)->orderBy('position')->get();

        return response()->json(['data' => TournamentMatchResource::collection($matches)]);
    }
}
