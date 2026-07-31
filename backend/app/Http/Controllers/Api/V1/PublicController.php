<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coach;
use App\Models\Court;
use App\Models\Gallery;
use App\Models\Package;
use App\Models\Program;
use App\Models\TrainingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Thin, unauthenticated read-only listings for the public marketing site.
 * Deliberately hand-shaped (not the authenticated CRUD resources) so no
 * staff-only or contact-detail fields ever leak to anonymous visitors.
 */
class PublicController extends Controller
{
    public function programs(): JsonResponse
    {
        $programs = Program::query()
            ->where('status', Program::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $programs->map(fn (Program $program) => [
                'id' => $program->id,
                'name' => $program->name,
                'age_group' => $program->age_group,
                'skill_level' => $program->skill_level,
                'target_competency' => $program->target_competency,
                'description' => $program->description,
            ]),
        ]);
    }

    public function packages(): JsonResponse
    {
        $packages = Package::query()
            ->where('status', Package::STATUS_ACTIVE)
            ->orderBy('price')
            ->get();

        return response()->json([
            'data' => $packages->map(fn (Package $package) => [
                'id' => $package->id,
                'name' => $package->name,
                'session_count' => $package->session_count,
                'validity_days' => $package->validity_days,
                'price' => (float) $package->price,
                'type' => $package->type,
            ]),
        ]);
    }

    public function coaches(): JsonResponse
    {
        $coaches = Coach::query()
            ->with(['user:id,name', 'branch:id,name'])
            ->where('employment_status', Coach::STATUS_ACTIVE)
            ->get();

        return response()->json([
            'data' => $coaches->map(fn (Coach $coach) => [
                'id' => $coach->id,
                'name' => $coach->user?->name,
                'branch_id' => $coach->branch_id,
                'branch_name' => $coach->branch?->name,
                'certifications' => $coach->certifications,
                'bio' => $coach->bio,
            ]),
        ]);
    }

    public function courts(): JsonResponse
    {
        $courts = Court::query()
            ->with('branch:id,name,address')
            ->where('status', Court::STATUS_ACTIVE)
            ->get();

        return response()->json([
            'data' => $courts->map(fn (Court $court) => [
                'id' => $court->id,
                'name' => $court->name,
                'surface_type' => $court->surface_type,
                'operating_hours' => $court->operating_hours,
                'branch_id' => $court->branch_id,
                'branch_name' => $court->branch?->name,
                'branch_address' => $court->branch?->address,
            ]),
        ]);
    }

    public function schedules(Request $request): JsonResponse
    {
        $days = min($request->integer('days', 14), 30);

        $schedules = TrainingSchedule::query()
            ->with(['trainingClass:id,name,program_id,branch_id', 'trainingClass.program:id,name', 'trainingClass.branch:id,name', 'coach.user:id,name', 'court:id,name'])
            ->where('status', TrainingSchedule::STATUS_SCHEDULED)
            ->whereDate('session_date', '>=', now()->toDateString())
            ->whereDate('session_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'data' => $schedules->map(fn (TrainingSchedule $schedule) => [
                'id' => $schedule->id,
                'session_date' => $schedule->session_date->toDateString(),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'class_name' => $schedule->trainingClass?->name,
                'program_name' => $schedule->trainingClass?->program?->name,
                'branch_name' => $schedule->trainingClass?->branch?->name,
                'coach_name' => $schedule->coach?->user?->name,
                'court_name' => $schedule->court?->name,
            ]),
        ]);
    }

    public function galleries(): JsonResponse
    {
        $galleries = Gallery::query()
            ->with('media')
            ->where('visibility', Gallery::VISIBILITY_PUBLIC)
            ->where('status', Gallery::STATUS_APPROVED)
            ->latest()
            ->limit(24)
            ->get();

        return response()->json([
            'data' => $galleries->map(fn (Gallery $gallery) => [
                'id' => $gallery->id,
                'title' => $gallery->title,
                'media' => $gallery->media->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'url' => Storage::disk('s3')->url($media->file_path),
                ]),
            ]),
        ]);
    }
}
