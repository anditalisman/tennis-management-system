<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\RestrictsParticipantAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Program\StoreProgramRequest;
use App\Http\Requests\Program\UpdateProgramRequest;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    use RestrictsParticipantAccess;

    public function index(Request $request): JsonResponse
    {
        $this->denyParticipantAndGuardian($request->user());

        $programs = Program::query()
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => ProgramResource::collection($programs->items()),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ],
        ]);
    }

    public function store(StoreProgramRequest $request): JsonResponse
    {
        $program = Program::query()->create($request->validated());

        return response()->json(['data' => new ProgramResource($program)], 201);
    }

    public function show(Request $request, Program $program): JsonResponse
    {
        $this->denyParticipantAndGuardian($request->user());

        return response()->json(['data' => new ProgramResource($program)]);
    }

    public function update(UpdateProgramRequest $request, Program $program): JsonResponse
    {
        $program->fill($request->validated())->save();

        return response()->json(['data' => new ProgramResource($program)]);
    }

    public function destroy(Program $program): JsonResponse
    {
        $program->delete();

        return response()->json(['data' => ['message' => 'Program berhasil dihapus.']]);
    }
}
