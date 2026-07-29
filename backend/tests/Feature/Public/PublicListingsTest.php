<?php

namespace Tests\Feature\Public;

use App\Models\Branch;
use App\Models\Coach;
use App\Models\Court;
use App\Models\Gallery;
use App\Models\GalleryMedia;
use App\Models\Package;
use App\Models\Program;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_active_programs_only(): void
    {
        Program::factory()->create(['name' => 'Aktif', 'status' => Program::STATUS_ACTIVE]);
        Program::factory()->create(['name' => 'Nonaktif', 'status' => Program::STATUS_INACTIVE]);

        $this->getJson('/api/v1/public/programs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Aktif');
    }

    public function test_guest_can_list_active_packages_with_program_name(): void
    {
        $program = Program::factory()->create(['name' => 'Junior']);
        Package::factory()->create(['program_id' => $program->id, 'status' => Package::STATUS_ACTIVE]);
        Package::factory()->create(['status' => Package::STATUS_INACTIVE]);

        $response = $this->getJson('/api/v1/public/packages')->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.program_name', 'Junior');
    }

    public function test_guest_can_list_active_coaches_without_email(): void
    {
        Coach::factory()->create(['employment_status' => Coach::STATUS_ACTIVE]);
        Coach::factory()->create(['employment_status' => Coach::STATUS_INACTIVE]);

        $response = $this->getJson('/api/v1/public/coaches')->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonMissingPath('data.0.email');
    }

    public function test_guest_can_list_active_courts_with_branch_info(): void
    {
        $branch = Branch::factory()->create(['name' => 'Cabang Uji']);
        Court::factory()->create(['branch_id' => $branch->id, 'status' => Court::STATUS_ACTIVE]);
        Court::factory()->create(['status' => Court::STATUS_MAINTENANCE]);

        $response = $this->getJson('/api/v1/public/courts')->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.branch_name', 'Cabang Uji');
    }

    public function test_guest_can_list_upcoming_scheduled_sessions(): void
    {
        $class = TrainingClass::factory()->create();
        TrainingSchedule::factory()->create([
            'class_id' => $class->id,
            'session_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => TrainingSchedule::STATUS_SCHEDULED,
        ]);
        TrainingSchedule::factory()->create([
            'class_id' => $class->id,
            'session_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => TrainingSchedule::STATUS_CANCELLED,
        ]);
        TrainingSchedule::factory()->create([
            'class_id' => $class->id,
            'session_date' => now()->subDays(3)->format('Y-m-d'),
            'status' => TrainingSchedule::STATUS_SCHEDULED,
        ]);

        $this->getJson('/api/v1/public/schedules')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_guest_can_list_public_approved_galleries_only(): void
    {
        $class = TrainingClass::factory()->create();
        $uploader = User::factory()->create();

        $public = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $uploader->id, 'title' => 'Publik', 'visibility' => Gallery::VISIBILITY_PUBLIC, 'status' => Gallery::STATUS_APPROVED]);
        GalleryMedia::query()->create(['gallery_id' => $public->id, 'type' => GalleryMedia::TYPE_IMAGE, 'file_path' => 'galleries/demo.jpg']);
        Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $uploader->id, 'title' => 'Privat', 'visibility' => Gallery::VISIBILITY_PRIVATE, 'status' => Gallery::STATUS_APPROVED]);
        Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $uploader->id, 'title' => 'Pending', 'visibility' => Gallery::VISIBILITY_PUBLIC, 'status' => Gallery::STATUS_PENDING]);

        $response = $this->getJson('/api/v1/public/galleries')->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Publik');
        $response->assertJsonCount(1, 'data.0.media');
    }
}
