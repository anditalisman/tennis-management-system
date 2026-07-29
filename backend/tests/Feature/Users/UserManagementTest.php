<?php

namespace Tests\Feature\Users;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    public function test_guest_cannot_list_users(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }

    public function test_non_super_admin_cannot_manage_users(): void
    {
        $management = $this->userWithRole(Role::MANAGEMENT);

        $this->actingAs($management, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_super_admin_can_list_users(): void
    {
        User::factory()->count(2)->create();
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_super_admin_can_create_a_user_with_roles(): void
    {
        $branch = Branch::factory()->create();
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $response = $this->actingAs($superAdmin, 'sanctum')->postJson('/api/v1/users', [
            'name' => 'Coach Baru',
            'email' => 'coachbaru@example.com',
            'password' => 'Password123',
            'branch_id' => $branch->id,
            'roles' => [Role::COACH],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'coachbaru@example.com')
            ->assertJsonPath('data.roles.0', Role::COACH)
            ->assertJsonPath('data.branch_id', $branch->id);

        $this->assertDatabaseHas('users', ['email' => 'coachbaru@example.com']);
    }

    public function test_user_creation_requires_at_least_one_role(): void
    {
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Tanpa Role',
                'email' => 'noroles@example.com',
                'password' => 'Password123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles']);
    }

    public function test_super_admin_can_update_a_users_roles_and_status(): void
    {
        $target = $this->userWithRole(Role::COACH);
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $response = $this->actingAs($superAdmin, 'sanctum')->putJson("/api/v1/users/{$target->uuid}", [
            'status' => User::STATUS_SUSPENDED,
            'roles' => [Role::ADMINISTRATOR],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', User::STATUS_SUSPENDED)
            ->assertJsonPath('data.roles.0', Role::ADMINISTRATOR)
            ->assertJsonCount(1, 'data.roles');

        $this->assertSame(User::STATUS_SUSPENDED, $target->fresh()->status);
    }

    public function test_super_admin_can_deactivate_a_user_and_revoke_tokens(): void
    {
        $target = $this->userWithRole(Role::COACH);
        $target->createToken('mobile');
        $superAdmin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($superAdmin, 'sanctum')
            ->deleteJson("/api/v1/users/{$target->uuid}")
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertSame(0, $target->tokens()->count());
    }
}
