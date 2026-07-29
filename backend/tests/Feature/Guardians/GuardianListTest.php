<?php

namespace Tests\Feature\Guardians;

use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianListTest extends TestCase
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

    public function test_guest_cannot_list_guardians(): void
    {
        $this->getJson('/api/v1/guardians')->assertUnauthorized();
    }

    public function test_coach_cannot_list_guardians(): void
    {
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->getJson('/api/v1/guardians')->assertForbidden();
    }

    public function test_administrator_can_list_guardians_with_participant_count(): void
    {
        $guardian = Guardian::factory()->create();
        $participant = Participant::factory()->create();
        $guardian->participants()->attach($participant->id, ['is_primary' => true]);

        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/guardians')->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.participant_count', 1);
    }

    public function test_search_filters_guardians_by_name(): void
    {
        Guardian::factory()->for(User::factory()->state(['name' => 'Budi Wali']), 'user')->create();
        Guardian::factory()->for(User::factory()->state(['name' => 'Siti Wali']), 'user')->create();

        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/guardians?search=Budi')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budi Wali');
    }
}
