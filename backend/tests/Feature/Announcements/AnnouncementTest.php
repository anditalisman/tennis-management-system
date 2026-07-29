<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $slug, ?int $branchId = null): User
    {
        $user = User::factory()->create(['branch_id' => $branchId]);
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_administrator_can_publish_an_announcement(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/announcements', [
            'title' => 'Libur Latihan',
            'body' => 'Latihan diliburkan tanggal 17 Agustus.',
            'status' => Announcement::STATUS_PUBLISHED,
        ]);

        $response->assertCreated()->assertJsonPath('data.status', Announcement::STATUS_PUBLISHED);
    }

    public function test_coach_cannot_access_announcements_module(): void
    {
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->getJson('/api/v1/announcements')->assertForbidden();
    }

    public function test_participant_sees_only_published_announcements(): void
    {
        Announcement::factory()->create(['status' => Announcement::STATUS_PUBLISHED]);
        Announcement::factory()->create(['status' => Announcement::STATUS_DRAFT]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $response = $this->actingAs($participant, 'sanctum')->getJson('/api/v1/announcements');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_branch_targeted_announcement_only_visible_to_that_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Announcement::factory()->create([
            'status' => Announcement::STATUS_PUBLISHED,
            'target_type' => Announcement::TARGET_BRANCH,
            'target_id' => $branchA->id,
        ]);

        $userInBranchA = $this->userWithRole(Role::PARTICIPANT, $branchA->id);
        $userInBranchB = $this->userWithRole(Role::PARTICIPANT, $branchB->id);

        $this->actingAs($userInBranchA, 'sanctum')->getJson('/api/v1/announcements')->assertJsonCount(1, 'data');
        $this->actingAs($userInBranchB, 'sanctum')->getJson('/api/v1/announcements')->assertJsonCount(0, 'data');
    }

    public function test_expired_announcement_is_not_visible(): void
    {
        Announcement::factory()->create([
            'status' => Announcement::STATUS_PUBLISHED,
            'publish_at' => now()->subDays(10),
            'expire_at' => now()->subDay(),
        ]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/announcements')->assertJsonCount(0, 'data');
    }

    public function test_administrator_sees_draft_announcements_too(): void
    {
        Announcement::factory()->create(['status' => Announcement::STATUS_DRAFT]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/announcements')->assertJsonCount(1, 'data');
    }
}
