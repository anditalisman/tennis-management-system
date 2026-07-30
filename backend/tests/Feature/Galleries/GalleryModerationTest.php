<?php

namespace Tests\Feature\Galleries;

use App\Models\ClassMember;
use App\Models\Coach;
use App\Models\Gallery;
use App\Models\Participant;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('s3');
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_new_gallery_defaults_to_private_and_pending(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        $coach = Coach::factory()->create(['user_id' => $coachUser->id]);
        $class = TrainingClass::factory()->create(['coach_id' => $coach->id]);

        $response = $this->actingAs($coachUser, 'sanctum')->postJson('/api/v1/galleries', [
            'class_id' => $class->id,
            'title' => 'Sesi Latihan Pagi',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.visibility', Gallery::VISIBILITY_PRIVATE)
            ->assertJsonPath('data.status', Gallery::STATUS_PENDING);
    }

    public function test_coach_can_upload_media_to_own_gallery(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        $coach = Coach::factory()->create(['user_id' => $coachUser->id]);
        $class = TrainingClass::factory()->create(['coach_id' => $coach->id]);
        $gallery = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $coachUser->id]);

        $response = $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/galleries/{$gallery->id}/media", [
            'files' => [UploadedFile::fake()->image('sesi.jpg')],
        ]);

        $response->assertCreated()->assertJsonCount(1, 'data.media');
        $this->assertDatabaseHas('gallery_media', ['gallery_id' => $gallery->id, 'type' => 'image']);
    }

    public function test_a_different_coach_cannot_upload_to_someone_elses_gallery(): void
    {
        $ownerUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $ownerUser->id]);
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $ownerUser->id]);

        $intruderUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $intruderUser->id]);

        $this->actingAs($intruderUser, 'sanctum')
            ->postJson("/api/v1/galleries/{$gallery->id}/media", ['files' => [UploadedFile::fake()->image('x.jpg')]])
            ->assertForbidden();
    }

    public function test_pending_private_gallery_is_hidden_from_participants(): void
    {
        $class = TrainingClass::factory()->create();
        Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $this->userWithRole(Role::COACH)->id]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $response = $this->actingAs($participant, 'sanctum')->getJson('/api/v1/galleries');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_administrator_can_publish_a_gallery_making_it_visible(): void
    {
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $this->userWithRole(Role::COACH)->id]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/galleries/{$gallery->id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', Gallery::STATUS_APPROVED)
            ->assertJsonPath('data.visibility', Gallery::VISIBILITY_PUBLIC);
    }

    public function test_participant_enrolled_in_the_class_can_view_its_published_gallery(): void
    {
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create([
            'class_id' => $class->id,
            'uploaded_by' => $this->userWithRole(Role::COACH)->id,
            'status' => Gallery::STATUS_APPROVED,
            'visibility' => Gallery::VISIBILITY_PUBLIC,
        ]);

        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        $participant = Participant::factory()->create(['user_id' => $participantUser->id]);
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => $participant->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $this->actingAs($participantUser, 'sanctum')->getJson('/api/v1/galleries')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($participantUser, 'sanctum')->getJson("/api/v1/galleries/{$gallery->id}")->assertOk();
    }

    public function test_participant_not_enrolled_in_the_class_cannot_view_its_published_gallery(): void
    {
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create([
            'class_id' => $class->id,
            'uploaded_by' => $this->userWithRole(Role::COACH)->id,
            'status' => Gallery::STATUS_APPROVED,
            'visibility' => Gallery::VISIBILITY_PUBLIC,
        ]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/galleries')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($participant, 'sanctum')->getJson("/api/v1/galleries/{$gallery->id}")->assertForbidden();
    }

    public function test_coach_cannot_publish_their_own_gallery(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        $coach = Coach::factory()->create(['user_id' => $coachUser->id]);
        $class = TrainingClass::factory()->create(['coach_id' => $coach->id]);
        $gallery = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $coachUser->id]);

        $this->actingAs($coachUser, 'sanctum')
            ->postJson("/api/v1/galleries/{$gallery->id}/publish")
            ->assertForbidden();
    }

    public function test_participant_cannot_view_a_pending_private_gallery_by_direct_id(): void
    {
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $this->userWithRole(Role::COACH)->id]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')
            ->getJson("/api/v1/galleries/{$gallery->id}")
            ->assertForbidden();
    }

    public function test_uploader_can_view_their_own_pending_gallery_by_direct_id(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $coachUser->id]);

        $this->actingAs($coachUser, 'sanctum')
            ->getJson("/api/v1/galleries/{$gallery->id}")
            ->assertOk();
    }

    public function test_administrator_can_view_a_pending_gallery_by_direct_id(): void
    {
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create(['class_id' => $class->id, 'uploaded_by' => $this->userWithRole(Role::COACH)->id]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/galleries/{$gallery->id}")
            ->assertOk();
    }

    public function test_a_role_not_scoped_to_class_enrollment_can_view_a_published_gallery_by_direct_id(): void
    {
        $class = TrainingClass::factory()->create();
        $gallery = Gallery::query()->create([
            'class_id' => $class->id,
            'uploaded_by' => $this->userWithRole(Role::COACH)->id,
            'status' => Gallery::STATUS_APPROVED,
            'visibility' => Gallery::VISIBILITY_PUBLIC,
        ]);
        $finance = $this->userWithRole(Role::FINANCE);

        $this->actingAs($finance, 'sanctum')
            ->getJson("/api/v1/galleries/{$gallery->id}")
            ->assertOk();
    }
}
