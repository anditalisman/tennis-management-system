<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('roles');
        $canManage = $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR);

        $announcements = Announcement::query()
            ->with('creator')
            ->when(! $canManage, fn ($query) => $query->where('status', Announcement::STATUS_PUBLISHED))
            ->orderByDesc('publish_at')
            ->get()
            ->filter(fn (Announcement $a) => $canManage || $a->isVisibleTo($user))
            ->values();

        return response()->json(['data' => AnnouncementResource::collection($announcements)]);
    }

    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $announcement = Announcement::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'publish_at' => $request->validated('publish_at') ?: now(),
        ]);

        return response()->json(['data' => new AnnouncementResource($announcement->load('creator'))], 201);
    }

    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user()->loadMissing('roles');
        $canManage = $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR);

        abort_unless($canManage || $announcement->isVisibleTo($user), 403, 'Pengumuman ini tidak tersedia untuk Anda.');

        return response()->json(['data' => new AnnouncementResource($announcement->load('creator'))]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $announcement->fill($request->validated())->save();

        return response()->json(['data' => new AnnouncementResource($announcement->load('creator'))]);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json(['data' => ['message' => 'Pengumuman berhasil dihapus.']]);
    }
}
