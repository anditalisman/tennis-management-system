<?php

namespace Tests\Feature\Programs;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramManagementTest extends TestCase
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

    public function test_guardian_can_view_programs_but_not_manage(): void
    {
        Program::factory()->count(2)->create();
        $guardian = $this->userWithRole(Role::GUARDIAN);

        $this->actingAs($guardian, 'sanctum')->getJson('/api/v1/programs')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($guardian, 'sanctum')->postJson('/api/v1/programs', ['name' => 'X'])->assertForbidden();
    }

    public function test_administrator_can_create_and_delete_a_program(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/programs', [
            'name' => 'Junior Development',
            'age_group' => 'anak',
        ]);
        $response->assertCreated()
            ->assertJsonPath('data.name', 'Junior Development')
            ->assertJsonPath('data.status', Program::STATUS_ACTIVE);

        $id = $response->json('data.id');
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/programs/{$id}")->assertOk();
        $this->assertSoftDeleted('programs', ['id' => $id]);
    }
}
