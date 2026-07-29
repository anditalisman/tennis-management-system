<?php

namespace Tests\Feature\Participants;

use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantVisibilityTest extends TestCase
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

    public function test_administrator_can_see_all_participants(): void
    {
        Participant::factory()->count(3)->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/participants')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_participant_only_sees_their_own_record(): void
    {
        $user = $this->userWithRole(Role::PARTICIPANT);
        $own = Participant::factory()->create(['user_id' => $user->id]);
        Participant::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/participants');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->uuid);
    }

    public function test_guardian_only_sees_linked_children(): void
    {
        $guardianUser = $this->userWithRole(Role::GUARDIAN);
        $guardian = Guardian::factory()->create(['user_id' => $guardianUser->id]);
        $ownChild = Participant::factory()->create();
        $guardian->participants()->attach($ownChild->id, ['is_primary' => true]);
        Participant::factory()->create();

        $response = $this->actingAs($guardianUser, 'sanctum')->getJson('/api/v1/participants');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownChild->uuid);
    }

    public function test_participant_cannot_view_someone_elses_record(): void
    {
        $user = $this->userWithRole(Role::PARTICIPANT);
        $other = Participant::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/participants/{$other->uuid}")
            ->assertForbidden();
    }

    public function test_administrator_can_approve_a_pending_participant(): void
    {
        $participant = Participant::factory()->create(['status' => Participant::STATUS_PENDING_VERIFICATION]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/participants/{$participant->uuid}/verify", [
            'action' => 'approve',
        ]);

        $response->assertOk()->assertJsonPath('data.status', Participant::STATUS_ACTIVE);
    }

    public function test_administrator_can_reject_a_pending_participant(): void
    {
        $participant = Participant::factory()->create(['status' => Participant::STATUS_PENDING_VERIFICATION]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/participants/{$participant->uuid}/verify", [
            'action' => 'reject',
            'note' => 'Data tidak lengkap',
        ]);

        $response->assertOk()->assertJsonPath('data.status', Participant::STATUS_REJECTED);
    }

    public function test_cannot_verify_an_already_active_participant(): void
    {
        $participant = Participant::factory()->create(['status' => Participant::STATUS_ACTIVE]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/participants/{$participant->uuid}/verify", ['action' => 'approve'])
            ->assertUnprocessable();
    }

    public function test_coach_cannot_verify_participants(): void
    {
        $participant = Participant::factory()->create(['status' => Participant::STATUS_PENDING_VERIFICATION]);
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/participants/{$participant->uuid}/verify", ['action' => 'approve'])
            ->assertForbidden();
    }
}
