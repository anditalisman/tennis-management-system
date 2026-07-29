<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_verifying_a_participant_queues_and_sends_a_notification_with_a_log_entry(): void
    {
        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        $participant = Participant::factory()->create([
            'user_id' => $participantUser->id,
            'status' => Participant::STATUS_PENDING_VERIFICATION,
        ]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/participants/{$participant->uuid}/verify", [
            'action' => 'approve',
        ])->assertOk();

        $notification = Notification::query()->where('user_id', $participantUser->id)->firstOrFail();
        $this->assertSame(Notification::STATUS_SENT, $notification->status);
        $this->assertDatabaseHas('notification_logs', [
            'notification_id' => $notification->id,
            'status' => Notification::STATUS_SENT,
        ]);
    }

    public function test_user_can_list_and_mark_their_own_notification_as_read(): void
    {
        $user = $this->userWithRole(Role::PARTICIPANT);
        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'channel' => Notification::CHANNEL_EMAIL,
            'title' => 'Test',
            'body' => 'Body',
        ]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(1, 'data');

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/notifications/{$notification->id}/read");
        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_someone_elses_notification_as_read(): void
    {
        $owner = $this->userWithRole(Role::PARTICIPANT);
        $notification = Notification::query()->create([
            'user_id' => $owner->id,
            'channel' => Notification::CHANNEL_EMAIL,
            'title' => 'Test',
            'body' => 'Body',
        ]);
        $intruder = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertForbidden();
    }
}
