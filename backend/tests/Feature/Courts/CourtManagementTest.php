<?php

namespace Tests\Feature\Courts;

use App\Models\Branch;
use App\Models\Court;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtManagementTest extends TestCase
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

    public function test_coach_can_view_courts_but_not_manage(): void
    {
        Court::factory()->count(2)->create();
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->getJson('/api/v1/courts')->assertOk()->assertJsonCount(2, 'data');
        $this->actingAs($coach, 'sanctum')->postJson('/api/v1/courts', ['branch_id' => 1, 'name' => 'X'])->assertForbidden();
    }

    public function test_administrator_can_create_and_delete_a_court(): void
    {
        $branch = Branch::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/courts', [
            'branch_id' => $branch->id,
            'name' => 'Lapangan 1',
            'surface_type' => 'hard',
        ]);
        $response->assertCreated()
            ->assertJsonPath('data.name', 'Lapangan 1')
            ->assertJsonPath('data.status', Court::STATUS_ACTIVE);

        $courtId = $response->json('data.id');
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/courts/{$courtId}")->assertOk();
        $this->assertSoftDeleted('courts', ['id' => $courtId]);
    }

    public function test_administrator_can_create_a_court_without_a_branch(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/courts', [
            'name' => 'Lapangan Tunggal',
        ]);

        $response->assertCreated()->assertJsonPath('data.branch_id', null);
    }

    public function test_participant_cannot_view_courts(): void
    {
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/courts')->assertForbidden();
    }
}
