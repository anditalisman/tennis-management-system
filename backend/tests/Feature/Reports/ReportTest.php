<?php

namespace Tests\Feature\Reports;

use App\Models\Attendance;
use App\Models\ClassMember;
use App\Models\Coach;
use App\Models\Invoice;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\PaymentConfirmation;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
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

    private function seedAttendanceData(): TrainingClass
    {
        $class = TrainingClass::factory()->create();
        $schedule = TrainingSchedule::factory()->create(['class_id' => $class->id]);
        $session = TrainingSession::query()->create(['schedule_id' => $schedule->id]);

        foreach ([Attendance::STATUS_PRESENT, Attendance::STATUS_PRESENT, Attendance::STATUS_ABSENT] as $status) {
            $participant = Participant::factory()->create();
            ClassMember::query()->create([
                'class_id' => $class->id,
                'participant_id' => $participant->id,
                'status' => ClassMember::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);
            Attendance::query()->create([
                'session_id' => $session->id,
                'participant_id' => $participant->id,
                'status' => $status,
            ]);
        }

        return $class;
    }

    public function test_administrator_can_view_attendance_report(): void
    {
        $this->seedAttendanceData();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/reports/attendance');

        $response->assertOk()
            ->assertJsonPath('data.by_status.present', 2)
            ->assertJsonPath('data.by_status.absent', 1);
    }

    public function test_coach_attendance_report_is_scoped_to_their_own_classes(): void
    {
        $this->seedAttendanceData();

        $coachUser = $this->userWithRole(Role::COACH);
        $coach = Coach::factory()->create(['user_id' => $coachUser->id]);
        $ownClass = TrainingClass::factory()->create(['coach_id' => $coach->id]);
        $ownSchedule = TrainingSchedule::factory()->create(['class_id' => $ownClass->id, 'coach_id' => $coach->id]);
        $ownSession = TrainingSession::query()->create(['schedule_id' => $ownSchedule->id]);
        $ownParticipant = Participant::factory()->create();
        Attendance::query()->create([
            'session_id' => $ownSession->id,
            'participant_id' => $ownParticipant->id,
            'status' => Attendance::STATUS_LATE,
        ]);

        $response = $this->actingAs($coachUser, 'sanctum')->getJson('/api/v1/reports/attendance');

        $response->assertOk()
            ->assertJsonPath('data.by_status.late', 1)
            ->assertJsonMissingPath('data.by_status.present');
    }

    public function test_participant_cannot_view_reports(): void
    {
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/reports/attendance')->assertForbidden();
    }

    public function test_finance_can_view_revenue_report(): void
    {
        $invoice = Invoice::factory()->create();
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 300000,
            'status' => Payment::STATUS_VERIFIED,
        ]);
        PaymentConfirmation::query()->create(['payment_id' => $payment->id, 'verified_at' => now()]);

        $finance = $this->userWithRole(Role::FINANCE);
        $response = $this->actingAs($finance, 'sanctum')->getJson('/api/v1/reports/revenue');

        $response->assertOk()->assertJsonPath('data.total', 300000);
    }

    public function test_csv_export_is_downloadable(): void
    {
        $this->seedAttendanceData();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->get('/api/v1/reports/attendance/export?format=csv');

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('status,total', $response->getContent());
    }

    public function test_xlsx_export_returns_a_clear_not_implemented_error(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')
            ->get('/api/v1/reports/attendance/export?format=xlsx')
            ->assertUnprocessable();
    }
}
