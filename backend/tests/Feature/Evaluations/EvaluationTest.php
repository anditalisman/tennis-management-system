<?php

namespace Tests\Feature\Evaluations;

use App\Models\Coach;
use App\Models\Evaluation;
use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
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

    private function detailsPayload(): array
    {
        return collect(Evaluation::ASPECTS)->map(fn ($aspect) => ['aspect' => $aspect, 'score' => 4])->all();
    }

    public function test_coach_can_create_an_evaluation_with_all_aspects(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $coachUser->id]);
        $participant = Participant::factory()->create();

        $response = $this->actingAs($coachUser, 'sanctum')->postJson('/api/v1/evaluations', [
            'participant_id' => $participant->uuid,
            'evaluation_date' => now()->toDateString(),
            'next_target' => 'Perbaiki backhand',
            'details' => $this->detailsPayload(),
        ]);

        $response->assertCreated()->assertJsonCount(count(Evaluation::ASPECTS), 'data.details');
    }

    public function test_participant_cannot_create_an_evaluation(): void
    {
        $participant = $this->userWithRole(Role::PARTICIPANT);
        $target = Participant::factory()->create();

        $this->actingAs($participant, 'sanctum')->postJson('/api/v1/evaluations', [
            'participant_id' => $target->uuid,
            'evaluation_date' => now()->toDateString(),
            'details' => $this->detailsPayload(),
        ])->assertForbidden();
    }

    public function test_evaluation_rejects_duplicate_aspects(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $coachUser->id]);
        $participant = Participant::factory()->create();

        $this->actingAs($coachUser, 'sanctum')->postJson('/api/v1/evaluations', [
            'participant_id' => $participant->uuid,
            'evaluation_date' => now()->toDateString(),
            'details' => [
                ['aspect' => 'forehand', 'score' => 4],
                ['aspect' => 'forehand', 'score' => 5],
            ],
        ])->assertUnprocessable();
    }

    public function test_guardian_can_view_their_childs_evaluation_history(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $coachUser->id]);
        $child = Participant::factory()->create();
        $this->actingAs($coachUser, 'sanctum')->postJson('/api/v1/evaluations', [
            'participant_id' => $child->uuid,
            'evaluation_date' => now()->toDateString(),
            'details' => $this->detailsPayload(),
        ]);

        $guardianUser = $this->userWithRole(Role::GUARDIAN);
        $guardian = Guardian::factory()->create(['user_id' => $guardianUser->id]);
        $guardian->participants()->attach($child->id, ['is_primary' => true]);

        $this->actingAs($guardianUser, 'sanctum')
            ->getJson("/api/v1/participants/{$child->uuid}/evaluations")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unrelated_guardian_cannot_view_a_childs_evaluation_history(): void
    {
        $coachUser = $this->userWithRole(Role::COACH);
        Coach::factory()->create(['user_id' => $coachUser->id]);
        $child = Participant::factory()->create();

        $outsiderGuardianUser = $this->userWithRole(Role::GUARDIAN);
        Guardian::factory()->create(['user_id' => $outsiderGuardianUser->id]);

        $this->actingAs($outsiderGuardianUser, 'sanctum')
            ->getJson("/api/v1/participants/{$child->uuid}/evaluations")
            ->assertForbidden();
    }
}
