<?php

namespace Tests\Feature\Branches;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
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

    public function test_guest_cannot_list_branches(): void
    {
        $this->getJson('/api/v1/branches')->assertUnauthorized();
    }

    public function test_guest_can_list_active_branches_via_the_public_endpoint(): void
    {
        Branch::factory()->create(['status' => Branch::STATUS_ACTIVE]);
        Branch::factory()->create(['status' => Branch::STATUS_INACTIVE]);

        $this->getJson('/api/v1/public/branches')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_management_can_view_branches_but_not_create(): void
    {
        Branch::factory()->count(3)->create();
        $user = $this->userWithRole(Role::MANAGEMENT);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/branches')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/branches', ['name' => 'Cabang Baru'])
            ->assertForbidden();
    }

    public function test_participant_cannot_view_branches(): void
    {
        $user = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/branches')
            ->assertForbidden();
    }

    public function test_super_admin_can_create_a_branch(): void
    {
        $user = $this->userWithRole(Role::SUPER_ADMIN);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/branches', [
            'name' => 'Zul Tennis Clinic Bandung',
            'address' => 'Jl. Merdeka No. 1',
            'phone' => '+6281200000000',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Zul Tennis Clinic Bandung')
            ->assertJsonPath('data.slug', 'zul-tennis-clinic-bandung')
            ->assertJsonPath('data.status', Branch::STATUS_ACTIVE);

        $this->assertDatabaseHas('branches', ['name' => 'Zul Tennis Clinic Bandung']);
    }

    public function test_branch_creation_requires_a_name(): void
    {
        $user = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/branches', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_super_admin_can_update_a_branch(): void
    {
        $branch = Branch::factory()->create(['status' => Branch::STATUS_ACTIVE]);
        $user = $this->userWithRole(Role::SUPER_ADMIN);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/branches/{$branch->id}", [
            'status' => Branch::STATUS_INACTIVE,
        ]);

        $response->assertOk()->assertJsonPath('data.status', Branch::STATUS_INACTIVE);
        $this->assertSame(Branch::STATUS_INACTIVE, $branch->fresh()->status);
    }

    public function test_super_admin_can_delete_a_branch(): void
    {
        $branch = Branch::factory()->create();
        $user = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/branches/{$branch->id}")
            ->assertOk();

        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }
}
