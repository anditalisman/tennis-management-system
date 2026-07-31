<?php

namespace Tests\Feature\Packages;

use App\Models\Package;
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

    public function test_participant_can_view_but_not_manage_packages(): void
    {
        // Participants need to browse the package catalog to self-checkout
        // (see PackageCheckoutTest) — packages.view already granted them
        // this in RolePermissionSeeder; only .manage (create/edit/delete)
        // stays staff-only.
        Package::factory()->count(2)->create();
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/packages')->assertOk();
        $this->actingAs($participant, 'sanctum')->postJson('/api/v1/packages', ['name' => 'X'])->assertForbidden();
    }

    public function test_administrator_can_create_a_package(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/packages', [
            'name' => 'Paket 8x',
            'session_count' => 8,
            'price' => 800000,
            'type' => Package::TYPE_KELOMPOK,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Paket 8x')
            ->assertJsonPath('data.type', Package::TYPE_KELOMPOK)
            ->assertJsonPath('data.status', Package::STATUS_ACTIVE)
            ->assertJsonPath('data.validity_days', 90);
    }

    public function test_package_type_must_be_one_of_the_known_categories(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/packages', [
            'name' => 'Paket Aneh',
            'session_count' => 8,
            'price' => 800000,
            'type' => 'reguler',
        ])->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    public function test_coach_cannot_manage_packages(): void
    {
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->getJson('/api/v1/packages')->assertForbidden();
    }
}
