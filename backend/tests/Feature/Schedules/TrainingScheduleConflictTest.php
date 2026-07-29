<?php

namespace Tests\Feature\Schedules;

use App\Models\Coach;
use App\Models\Court;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingScheduleConflictTest extends TestCase
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

    public function test_administrator_can_create_a_schedule(): void
    {
        $class = TrainingClass::factory()->create();
        $court = Court::factory()->create();
        $coach = Coach::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/schedules', [
            'class_id' => $class->id,
            'court_id' => $court->id,
            'coach_id' => $coach->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '17:00',
        ]);

        $response->assertCreated()->assertJsonPath('data.status', TrainingSchedule::STATUS_SCHEDULED);
    }

    public function test_overlapping_court_booking_is_rejected(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDay()->toDateString();
        TrainingSchedule::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '16:00',
            'end_time' => '17:00',
        ]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/schedules', [
            'class_id' => TrainingClass::factory()->create()->id,
            'court_id' => $court->id,
            'coach_id' => Coach::factory()->create()->id,
            'session_date' => $date,
            'start_time' => '16:30',
            'end_time' => '17:30',
        ]);

        $response->assertUnprocessable();
        $this->assertStringContainsString('Lapangan', $response->json('message'));
    }

    public function test_overlapping_coach_booking_is_rejected(): void
    {
        $coach = Coach::factory()->create();
        $date = now()->addDay()->toDateString();
        TrainingSchedule::factory()->create([
            'coach_id' => $coach->id,
            'session_date' => $date,
            'start_time' => '16:00',
            'end_time' => '17:00',
        ]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/schedules', [
            'class_id' => TrainingClass::factory()->create()->id,
            'court_id' => Court::factory()->create()->id,
            'coach_id' => $coach->id,
            'session_date' => $date,
            'start_time' => '16:30',
            'end_time' => '17:30',
        ]);

        $response->assertUnprocessable();
        $this->assertStringContainsString('Pelatih', $response->json('message'));
    }

    public function test_non_overlapping_schedules_on_the_same_court_are_allowed(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDay()->toDateString();
        TrainingSchedule::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '16:00',
            'end_time' => '17:00',
        ]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/schedules', [
            'class_id' => TrainingClass::factory()->create()->id,
            'court_id' => $court->id,
            'coach_id' => Coach::factory()->create()->id,
            'session_date' => $date,
            'start_time' => '17:00',
            'end_time' => '18:00',
        ]);

        $response->assertCreated();
    }

    public function test_cancelled_schedule_does_not_block_the_slot(): void
    {
        $court = Court::factory()->create();
        $date = now()->addDay()->toDateString();
        $existing = TrainingSchedule::factory()->create([
            'court_id' => $court->id,
            'session_date' => $date,
            'start_time' => '16:00',
            'end_time' => '17:00',
            'status' => TrainingSchedule::STATUS_CANCELLED,
        ]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/schedules', [
            'class_id' => TrainingClass::factory()->create()->id,
            'court_id' => $court->id,
            'coach_id' => Coach::factory()->create()->id,
            'session_date' => $date,
            'start_time' => '16:00',
            'end_time' => '17:00',
        ])->assertCreated();

        $this->assertSame(TrainingSchedule::STATUS_CANCELLED, $existing->fresh()->status);
    }

    public function test_administrator_can_cancel_a_schedule(): void
    {
        $schedule = TrainingSchedule::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/cancel", [
            'reason' => 'Hujan deras',
        ]);

        $response->assertOk()->assertJsonPath('data.status', TrainingSchedule::STATUS_CANCELLED);
    }

    public function test_administrator_can_reschedule_without_losing_history(): void
    {
        $schedule = TrainingSchedule::factory()->create();
        $newCourt = Court::factory()->create();
        $newCoach = Coach::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/reschedule", [
            'court_id' => $newCourt->id,
            'coach_id' => $newCoach->id,
            'session_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', TrainingSchedule::TYPE_REPLACEMENT)
            ->assertJsonPath('data.replaces_schedule_id', $schedule->id);

        $this->assertSame(TrainingSchedule::STATUS_CANCELLED, $schedule->fresh()->status);
    }

    public function test_coach_cannot_create_schedules(): void
    {
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->postJson('/api/v1/schedules', [
            'class_id' => TrainingClass::factory()->create()->id,
            'court_id' => Court::factory()->create()->id,
            'coach_id' => Coach::factory()->create()->id,
            'session_date' => now()->addDay()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '17:00',
        ])->assertForbidden();
    }
}
