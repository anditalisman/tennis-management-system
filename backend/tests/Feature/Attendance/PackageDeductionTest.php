<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\ClassMember;
use App\Models\Coach;
use App\Models\Package;
use App\Models\Participant;
use App\Models\ParticipantPackage;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageDeductionTest extends TestCase
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

    private function setUpSession(): array
    {
        $coachUser = $this->userWithRole(Role::COACH);
        $coach = Coach::factory()->create(['user_id' => $coachUser->id]);
        $class = TrainingClass::factory()->create(['coach_id' => $coach->id]);
        $schedule = TrainingSchedule::factory()->create(['class_id' => $class->id, 'coach_id' => $coach->id]);

        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        $participant = Participant::factory()->create(['user_id' => $participantUser->id]);
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => $participant->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        return compact('coachUser', 'schedule', 'participant');
    }

    public function test_self_check_in_deducts_a_session_immediately(): void
    {
        ['schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        $package = ParticipantPackage::query()->create([
            'participant_id' => $participant->id,
            'package_id' => Package::factory()->create()->id,
            'sessions_remaining' => 3,
            'status' => ParticipantPackage::STATUS_ACTIVE,
        ]);

        $this->actingAs($participant->user, 'sanctum')
            ->postJson("/api/v1/schedules/{$schedule->id}/check-in", [])
            ->assertCreated()
            ->assertJsonPath('data.status', Attendance::STATUS_PRESENT);

        $this->assertSame(2, $package->fresh()->sessions_remaining);
    }

    public function test_checking_in_twice_does_not_double_deduct(): void
    {
        ['schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        $package = ParticipantPackage::query()->create([
            'participant_id' => $participant->id,
            'package_id' => Package::factory()->create()->id,
            'sessions_remaining' => 3,
            'status' => ParticipantPackage::STATUS_ACTIVE,
        ]);

        $this->actingAs($participant->user, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/check-in", []);
        $this->actingAs($participant->user, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/check-in", []);

        $this->assertSame(2, $package->fresh()->sessions_remaining);
    }

    public function test_coach_can_still_correct_a_self_check_in_afterwards(): void
    {
        ['coachUser' => $coachUser, 'schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        $package = ParticipantPackage::query()->create([
            'participant_id' => $participant->id,
            'package_id' => Package::factory()->create()->id,
            'sessions_remaining' => 3,
            'status' => ParticipantPackage::STATUS_ACTIVE,
        ]);

        $this->actingAs($participant->user, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/check-in", []);
        $this->assertSame(2, $package->fresh()->sessions_remaining);

        $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
            'records' => [['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_ABSENT]],
        ])->assertOk()->assertJsonPath('data.0.status', Attendance::STATUS_ABSENT);

        // Correcting present -> absent doesn't refund automatically (out of
        // scope per the same note in AttendanceController::bulkVerify), but
        // the status itself must reflect the coach's correction.
        $this->assertDatabaseHas('attendance', ['participant_id' => $participant->id, 'status' => Attendance::STATUS_ABSENT]);
    }

    public function test_present_status_deducts_one_session_from_the_active_package(): void
    {
        ['coachUser' => $coachUser, 'schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        $package = ParticipantPackage::query()->create([
            'participant_id' => $participant->id,
            'package_id' => Package::factory()->create()->id,
            'sessions_remaining' => 3,
            'status' => ParticipantPackage::STATUS_ACTIVE,
        ]);

        $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
            'records' => [['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_PRESENT]],
        ])->assertOk();

        $this->assertSame(2, $package->fresh()->sessions_remaining);
    }

    public function test_absent_status_does_not_deduct_a_session(): void
    {
        ['coachUser' => $coachUser, 'schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        $package = ParticipantPackage::query()->create([
            'participant_id' => $participant->id,
            'package_id' => Package::factory()->create()->id,
            'sessions_remaining' => 3,
            'status' => ParticipantPackage::STATUS_ACTIVE,
        ]);

        $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
            'records' => [['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_ABSENT]],
        ])->assertOk();

        $this->assertSame(3, $package->fresh()->sessions_remaining);
    }

    public function test_pending_unpaid_package_is_not_used_for_deduction(): void
    {
        ['coachUser' => $coachUser, 'schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        ParticipantPackage::query()->create([
            'participant_id' => $participant->id,
            'package_id' => Package::factory()->create()->id,
            'sessions_remaining' => 3,
            'status' => ParticipantPackage::STATUS_PENDING,
        ]);

        $response = $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
            'records' => [['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_PRESENT]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('participant_packages', ['participant_id' => $participant->id, 'sessions_remaining' => 3]);
    }

    public function test_package_becomes_exhausted_when_last_session_is_used(): void
    {
        ['coachUser' => $coachUser, 'schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        $package = ParticipantPackage::query()->create([
            'participant_id' => $participant->id,
            'package_id' => Package::factory()->create()->id,
            'sessions_remaining' => 1,
            'status' => ParticipantPackage::STATUS_ACTIVE,
        ]);

        $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
            'records' => [['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_LATE]],
        ])->assertOk();

        $package->refresh();
        $this->assertSame(0, $package->sessions_remaining);
        $this->assertSame(ParticipantPackage::STATUS_EXHAUSTED, $package->status);
    }

    public function test_cannot_verify_attendance_for_a_cancelled_schedule(): void
    {
        ['coachUser' => $coachUser, 'schedule' => $schedule, 'participant' => $participant] = $this->setUpSession();
        $schedule->update(['status' => TrainingSchedule::STATUS_CANCELLED]);

        $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/schedules/{$schedule->id}/attendance", [
            'records' => [['participant_id' => $participant->uuid, 'status' => Attendance::STATUS_PRESENT]],
        ])->assertUnprocessable();
    }
}
