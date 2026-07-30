<?php

namespace Tests\Feature\Packages;

use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageManagementTest extends TestCase
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

    public function test_participant_cannot_view_or_manage_packages(): void
    {
        Package::factory()->count(2)->create();
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/packages')->assertForbidden();
        $this->actingAs($participant, 'sanctum')->postJson('/api/v1/packages', ['name' => 'X'])->assertForbidden();
    }

    public function test_administrator_can_create_a_package(): void
    {
        $program = Program::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/packages', [
            'program_id' => $program->id,
            'name' => 'Paket 8x',
            'session_count' => 8,
            'price' => 800000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Paket 8x')
            ->assertJsonPath('data.status', Package::STATUS_ACTIVE)
            ->assertJsonPath('data.validity_days', 90);
    }

    public function test_coach_cannot_manage_packages(): void
    {
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->getJson('/api/v1/packages')->assertForbidden();
    }
}
