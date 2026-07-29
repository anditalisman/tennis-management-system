<?php

namespace Tests\Feature\Coaches;

use App\Models\Coach;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachManagementTest extends TestCase
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

    public function test_participant_can_view_coach_list(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $coachUser->id]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')
            ->getJson('/api/v1/coaches')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_administrator_can_create_a_coach(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/coaches', [
            'name' => 'Coach Dimas',
            'email' => 'dimas@example.com',
            'password' => 'Password123',
            'employment_status' => Coach::STATUS_ACTIVE,
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Coach Dimas');

        $coachUser = User::query()->where('email', 'dimas@example.com')->firstOrFail();
        $this->assertTrue($coachUser->hasRole(Role::COACH));
    }

    public function test_coach_can_update_their_own_profile(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        $coach = Coach::factory()->create(['user_id' => $coachUser->id, 'bio' => 'lama']);

        $response = $this->actingAs($coachUser, 'sanctum')->putJson("/api/v1/coaches/{$coach->id}", [
            'bio' => 'Pelatih tenis 10 tahun pengalaman.',
        ]);

        $response->assertOk()->assertJsonPath('data.bio', 'Pelatih tenis 10 tahun pengalaman.');
    }

    public function test_coach_cannot_update_another_coachs_profile(): void
    {
        $coachA = $this->userWithRole(Role::COACH);
        $coachB = $this->userWithRole(Role::COACH);
        $profileB = Coach::factory()->create(['user_id' => $coachB->id]);

        $this->actingAs($coachA, 'sanctum')
            ->putJson("/api/v1/coaches/{$profileB->id}", ['bio' => 'hack'])
            ->assertForbidden();
    }

    public function test_coach_cannot_create_or_delete_another_coach(): void
    {
        $coach = $this->userWithRole(Role::COACH);
        $otherCoach = $this->userWithRole(Role::COACH);
        $otherProfile = Coach::factory()->create(['user_id' => $otherCoach->id]);

        $this->actingAs($coach, 'sanctum')
            ->postJson('/api/v1/coaches', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'Password123'])
            ->assertForbidden();

        $this->actingAs($coach, 'sanctum')
            ->deleteJson("/api/v1/coaches/{$otherProfile->id}")
            ->assertForbidden();
    }

    public function test_participant_cannot_create_a_coach(): void
    {
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')
            ->postJson('/api/v1/coaches', [
                'name' => 'Coach Baru',
                'email' => 'baru@example.com',
                'password' => 'Password123',
            ])
            ->assertForbidden();
    }
}
