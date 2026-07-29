<?php

namespace Tests\Feature\Classes;

use App\Models\Branch;
use App\Models\ClassMember;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\User;
use App\Models\WaitingList;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingClassEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_creating_a_class_fills_in_defaults_immediately(): void
    {
        $program = Program::factory()->create();
        $branch = Branch::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/classes', [
            'program_id' => $program->id,
            'branch_id' => $branch->id,
            'name' => 'Kelas Sore',
            'capacity_max' => 6,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', TrainingClass::STATUS_ACTIVE)
            ->assertJsonPath('data.capacity_min', 1)
            ->assertJsonPath('data.session_duration', 60);
    }

    public function test_administrator_can_create_a_class_without_a_branch(): void
    {
        $program = Program::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/classes', [
            'program_id' => $program->id,
            'name' => 'Kelas Tunggal',
            'capacity_max' => 6,
        ]);

        $response->assertCreated()->assertJsonPath('data.branch_id', null);
    }

    public function test_administrator_can_enroll_a_participant(): void
    {
        $class = TrainingClass::factory()->create(['capacity_max' => 2]);
        $participant = Participant::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/classes/{$class->id}/members", [
            'participant_id' => $participant->uuid,
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'enrolled');
        $this->assertDatabaseHas('class_members', ['class_id' => $class->id, 'participant_id' => $participant->id]);
    }

    public function test_enrollment_falls_back_to_waiting_list_when_class_is_full(): void
    {
        $class = TrainingClass::factory()->create(['capacity_max' => 1]);
        $existingMember = Participant::factory()->create();
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => $existingMember->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $newParticipant = Participant::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/classes/{$class->id}/members", [
            'participant_id' => $newParticipant->uuid,
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'waiting_list');
        $this->assertDatabaseHas('waiting_lists', [
            'class_id' => $class->id,
            'participant_id' => $newParticipant->id,
            'status' => WaitingList::STATUS_WAITING,
        ]);
        $this->assertSame(1, $class->fresh()->activeMemberCount());
    }

    public function test_cannot_enroll_the_same_participant_twice(): void
    {
        $class = TrainingClass::factory()->create(['capacity_max' => 5]);
        $participant = Participant::factory()->create();
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => $participant->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/classes/{$class->id}/members", ['participant_id' => $participant->uuid])
            ->assertUnprocessable();
    }

    public function test_cannot_shrink_capacity_below_active_member_count(): void
    {
        $class = TrainingClass::factory()->create(['capacity_max' => 5]);
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => Participant::factory()->create()->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => Participant::factory()->create()->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/classes/{$class->id}", ['capacity_max' => 1])
            ->assertUnprocessable();
    }

    public function test_coach_can_view_classes_but_not_enroll(): void
    {
        $class = TrainingClass::factory()->create();
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->getJson('/api/v1/classes')->assertOk();
        $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/classes/{$class->id}/members", ['participant_id' => Participant::factory()->create()->id])
            ->assertForbidden();
    }
}
