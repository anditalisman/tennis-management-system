<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\RestrictsParticipantAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gallery\StoreGalleryRequest;
use App\Http\Requests\Gallery\UploadGalleryMediaRequest;
use App\Http\Resources\GalleryResource;
use App\Models\Gallery;
use App\Models\GalleryMedia;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\User;
use App\Services\ImageCompressor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    use RestrictsParticipantAccess;

    public function __construct(private readonly ImageCompressor $compressor) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $canModerate = $this->canModerate($user);
        $isParticipantOrGuardian = $user->hasRole(Role::PARTICIPANT) || $user->hasRole(Role::GUARDIAN);

        $galleries = Gallery::query()
            ->with(['media', 'uploader'])
            ->when($request->string('class_id')->isNotEmpty(), fn ($query) => $query->where('class_id', $request->integer('class_id')))
            ->when(! $canModerate, fn ($query) => $query->where('status', Gallery::STATUS_APPROVED)->where('visibility', Gallery::VISIBILITY_PUBLIC))
            // Participants/guardians only see galleries for classes they're
            // actually enrolled in, not every published gallery site-wide.
            ->when($isParticipantOrGuardian, fn ($query) => $query->whereIn('class_id', $this->enrolledClassIds($user)))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => GalleryResource::collection($galleries->items()),
            'meta' => [
                'current_page' => $galleries->currentPage(),
                'last_page' => $galleries->lastPage(),
                'per_page' => $galleries->perPage(),
                'total' => $galleries->total(),
            ],
        ]);
    }

    public function store(StoreGalleryRequest $request): JsonResponse
    {
        $user = $request->user();
        $class = TrainingClass::query()->findOrFail($request->validated('class_id'));

        $this->authorizeUpload($user, $class);

        $gallery = Gallery::query()->create([
            ...$request->validated(),
            'uploaded_by' => $user->id,
        ]);

        return response()->json(['data' => new GalleryResource($gallery->load('media', 'uploader'))], 201);
    }

    public function show(Request $request, Gallery $gallery): JsonResponse
    {
        $user = $request->user();
        $isVisible = $gallery->status === Gallery::STATUS_APPROVED && $gallery->visibility === Gallery::VISIBILITY_PUBLIC;

        if ($user->hasRole(Role::PARTICIPANT) || $user->hasRole(Role::GUARDIAN)) {
            $isVisible = $isVisible && in_array($gallery->class_id, $this->enrolledClassIds($user), true);
        }

        abort_unless(
            $isVisible || $this->canModerate($user) || $user->id === $gallery->uploaded_by,
            403,
            'Galeri ini belum dipublikasikan.',
        );

        return response()->json(['data' => new GalleryResource($gallery->load('media', 'uploader'))]);
    }

    public function uploadMedia(UploadGalleryMediaRequest $request, Gallery $gallery): JsonResponse
    {
        $user = $request->user();
        // Same rule as creating the gallery in the first place (any coach
        // teaching the class, or super-admin) — this used to only allow the
        // original uploader, so a co-teaching coach or an admin adding more
        // media to someone else's gallery always got a 403.
        $this->authorizeUpload($user, $gallery->trainingClass);

        $directory = 'galleries/'.$gallery->id;

        foreach ($request->file('files') as $file) {
            $isVideo = str_starts_with((string) $file->getMimeType(), 'video/');

            if (! $isVideo && $this->compressor->isCompressible($file)) {
                $path = $directory.'/'.Str::random(40).'.'.strtolower((string) $file->getClientOriginalExtension());
                Storage::disk('s3')->put($path, $this->compressor->compress($file));
            } else {
                $path = $file->store($directory, 's3');
            }

            GalleryMedia::query()->create([
                'gallery_id' => $gallery->id,
                'file_path' => $path,
                'type' => $isVideo ? GalleryMedia::TYPE_VIDEO : GalleryMedia::TYPE_IMAGE,
            ]);
        }

        return response()->json(['data' => new GalleryResource($gallery->load('media', 'uploader'))], 201);
    }

    public function publish(Request $request, Gallery $gallery): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR),
            403,
            'Hanya administrator yang dapat memoderasi dan mempublikasikan galeri.',
        );

        $gallery->update([
            'status' => Gallery::STATUS_APPROVED,
            'visibility' => Gallery::VISIBILITY_PUBLIC,
        ]);

        return response()->json(['data' => new GalleryResource($gallery->load('media', 'uploader'))]);
    }

    public function destroy(Request $request, Gallery $gallery): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->id === $gallery->uploaded_by || $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR),
            403,
            'Anda tidak memiliki izin untuk menghapus galeri ini.',
        );

        foreach ($gallery->media as $media) {
            Storage::disk('s3')->delete($media->file_path);
        }

        $gallery->delete();

        return response()->json(['data' => ['message' => 'Galeri berhasil dihapus.']]);
    }

    private function canModerate(User $user): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN) || $user->hasRole(Role::ADMINISTRATOR) || (bool) $user->coach;
    }

    private function authorizeUpload(User $user, TrainingClass $class): void
    {
        if ($user->hasRole(Role::SUPER_ADMIN)) {
            return;
        }

        abort_unless(
            $user->coach && $user->coach->id === $class->coach_id,
            403,
            'Hanya pelatih pengampu kelas ini yang dapat mengunggah galeri.',
        );
    }
}
