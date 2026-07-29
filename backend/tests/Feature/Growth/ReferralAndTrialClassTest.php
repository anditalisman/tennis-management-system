<?php

namespace Tests\Feature\Growth;

use App\Models\Branch;
use App\Models\ClassMember;
use App\Models\Participant;
use App\Models\Referral;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\TrialClass;
use App\Models\User;
use App\Models\WaitingList;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralAndTrialClassTest extends TestCase
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

    public function test_participant_can_generate_a_referral_code(): void
    {
        $user = $this->userWithRole(Role::PARTICIPANT);
        $participant = Participant::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/participants/{$participant->uuid}/referral-code");

        $response->assertCreated();
        $this->assertNotEmpty($response->json('data.code'));
    }

    public function test_registration_with_a_referral_code_links_the_referrer(): void
    {
        $referrerUser = $this->userWithRole(Role::PARTICIPANT);
        $referrer = Participant::factory()->create(['user_id' => $referrerUser->id]);
        $referral = Referral::query()->create(['referrer_participant_id' => $referrer->id]);
        $branch = Branch::factory()->create();

        $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Anak Referral',
            'email' => 'anakreferral@example.com',
            'birth_date' => '2016-01-01',
            'age_category' => Participant::AGE_U10,
            'policy_accepted' => true,
            'referral_code' => $referral->code,
            'guardian' => [
                'name' => 'Wali Referral',
                'relation' => 'Ayah',
                'phone' => '+6281200001111',
                'email' => 'walireferral@example.com',
                'password' => 'Password123',
            ],
        ])->assertCreated();

        $referral->refresh();
        $this->assertNotNull($referral->referred_participant_id);
        $this->assertSame(Referral::STATUS_REDEEMED, $referral->reward_status);
    }

    public function test_administrator_can_book_and_convert_a_trial_class(): void
    {
        $class = TrainingClass::factory()->create(['capacity_max' => 5]);
        $participant = Participant::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $trialResponse = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/classes/{$class->id}/trial", [
            'participant_id' => $participant->uuid,
            'trial_date' => now()->addDay()->toDateString(),
        ]);
        $trialResponse->assertCreated();
        $trialId = $trialResponse->json('data.id');

        $convertResponse = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/trial-classes/{$trialId}/convert");

        $convertResponse->assertOk()->assertJsonPath('data.enrollment_status', 'enrolled');
        $this->assertDatabaseHas('class_members', ['class_id' => $class->id, 'participant_id' => $participant->id]);
        $this->assertTrue(TrialClass::query()->find($trialId)->converted_to_member);
    }

    public function test_administrator_can_list_trials_booked_for_a_class(): void
    {
        $class = TrainingClass::factory()->create(['capacity_max' => 5]);
        $participant = Participant::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/classes/{$class->id}/trial", [
            'participant_id' => $participant->uuid,
            'trial_date' => now()->addDay()->toDateString(),
        ])->assertCreated();

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/classes/{$class->id}/trials");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame($participant->full_name, $response->json('data.0.participant_name'));
        $this->assertFalse($response->json('data.0.converted_to_member'));
    }

    public function test_removing_a_member_promotes_the_next_waiting_list_entry(): void
    {
        $class = TrainingClass::factory()->create(['capacity_max' => 1]);
        $activeParticipant = Participant::factory()->create();
        ClassMember::query()->create([
            'class_id' => $class->id,
            'participant_id' => $activeParticipant->id,
            'status' => ClassMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        $waitingParticipant = Participant::factory()->create();
        WaitingList::query()->create([
            'class_id' => $class->id,
            'participant_id' => $waitingParticipant->id,
            'status' => WaitingList::STATUS_WAITING,
        ]);

        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/classes/{$class->id}/members/{$activeParticipant->uuid}")
            ->assertOk();

        $this->assertDatabaseHas('class_members', [
            'class_id' => $class->id,
            'participant_id' => $waitingParticipant->id,
            'status' => 'active',
        ]);
        $this->assertSame(WaitingList::STATUS_CONVERTED, WaitingList::query()->where('participant_id', $waitingParticipant->id)->firstOrFail()->status);
    }
}
