<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\ClassMember;
use App\Models\Coach;
use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
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

    private function enrolledSchedule(): array
    {
        $coachUser = $this->userWithRole(Role::COACH);
        $coach = Coach::factory()->create(['user_id' => $coachUser->id]);
        $class = TrainingClass::factory()->create(['coach_id' => $coach->id, 'capacity_max' => 5]);
        $schedule = TrainingSchedule::factory()->create(['class_id' => $class->id, 'coach_id' => $coach->id]);

        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        $participant = Participant::factory()->create(['user_id' => $participantUser->id]);
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => $participant->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        return compact('coachUser', 'coach', 'class', 'schedule', 'participantUser', 'participant');
    }

    public function test_participant_can_check_in_to_their_own_session(): void
    {
        ['schedule' => $schedule, 'participantUser' => $participantUser, 'participant' => $participant] = $this->enrolledSchedule();

        $response = $this->actingAs($participantUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/check-in", []);

        // Self check-in no longer waits on coach verification — it's
        // recorded as present immediately (see AttendanceController::checkIn).
        $response->assertCreated()->assertJsonPath('data.status', Attendance::STATUS_PRESENT);
        $this->assertDatabaseHas('attendance', ['participant_id' => $participant->id, 'status' => Attendance::STATUS_PRESENT]);
    }

    public function test_participant_cannot_check_in_a_non_member(): void
    {
        $schedule = TrainingSchedule::factory()->create();
        $outsider = Participant::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/schedules/{$schedule->id}/check-in", ['participant_id' => $outsider->uuid])
            ->assertUnprocessable();
    }

    public function test_cannot_check_in_to_a_cancelled_schedule(): void
    {
        ['schedule' => $schedule, 'participantUser' => $participantUser] = $this->enrolledSchedule();
        $schedule->update(['status' => TrainingSchedule::STATUS_CANCELLED]);

        $this->actingAs($participantUser, 'sanctum')
            ->postJson("/api/v1/schedules/{$schedule->id}/check-in", [])
            ->assertUnprocessable();
    }

    public function test_assigned_coach_can_bulk_verify_attendance_and_it_is_logged(): void
    {
        // Coach marking attendance for someone who didn't self check-in via
        // the app (walk-in style) — this is a genuine pending -> present
        // transition and should be audit-logged. (A self-checked-in
        // participant is already "present" by the time the coach looks at
        // it, so re-confirming that same status is a no-op, not something
        // that needs its own log entry.)
        ['coachUser' => $coachUser, 'schedule' => $schedule, 'participant' => $participant] = $this->enrolledSchedule();

        $response = $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
            'records' => [
                ['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_PRESENT],
            ],
        ]);

        $response->assertOk()->assertJsonPath('data.0.status', Attendance::STATUS_PRESENT);
        $this->assertDatabaseHas('attendance', ['participant_id' => $participant->id, 'status' => Attendance::STATUS_PRESENT]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'attendance.verify',
            'user_id' => $coachUser->id,
        ]);
    }

    public function test_a_different_coach_cannot_verify_attendance_for_this_class(): void
    {
        ['schedule' => $schedule, 'participant' => $participant] = $this->enrolledSchedule();
        $otherCoachUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $otherCoachUser->id]);

        $this->actingAs($otherCoachUser, 'sanctum')
            ->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
                'records' => [['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_PRESENT]],
            ])
            ->assertForbidden();
    }

    public function test_guardian_only_sees_their_childs_attendance_in_the_session_list(): void
    {
        ['schedule' => $schedule, 'participant' => $ownParticipant] = $this->enrolledSchedule();

        $otherParticipantUser = $this->userWithRole(Role::PARTICIPANT);
        $otherParticipant = Participant::factory()->create(['user_id' => $otherParticipantUser->id]);
        ClassMember::query()->create([
            'class_id' => $schedule->class_id,
            'participant_id' => $otherParticipant->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        $this->actingAs($otherParticipantUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/check-in", []);

        $guardianUser = $this->userWithRole(Role::GUARDIAN);
        $guardian = Guardian::factory()->create(['user_id' => $guardianUser->id]);
        $guardian->participants()->attach($ownParticipant->id, ['is_primary' => true]);
        $this->actingAs($ownParticipant->user, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/check-in", []);

        $response = $this->actingAs($guardianUser, 'sanctum')->getJson("/api/v1/schedules/{$schedule->id}/attendance");

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.participant_id', $ownParticipant->uuid);
    }
}
