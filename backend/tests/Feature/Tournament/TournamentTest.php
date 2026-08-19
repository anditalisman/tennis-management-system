<?php

namespace Tests\Feature\Tournament;

use App\Models\Role;
use App\Models\TournamentParticipant;
use App\Models\TournamentTeam;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentTest extends TestCase
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

    public function test_anyone_can_register_without_logging_in(): void
    {
        $response = $this->postJson('/api/v1/tournament/participants', [
            'full_name' => 'Muhammad Rizky',
            'nickname' => 'Kiki',
            'phone' => '081234567890',
            'category' => TournamentParticipant::CATEGORY_BEGINNER,
        ]);

        $response->assertCreated()->assertJsonPath('data.nickname', 'Kiki');
    }

    public function test_registered_participants_are_publicly_listed(): void
    {
        TournamentParticipant::factory()->count(3)->create();

        $this->getJson('/api/v1/public/tournament/participants')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_participant_list_does_not_leak_phone_numbers(): void
    {
        TournamentParticipant::factory()->create(['phone' => '089999999999']);

        $response = $this->getJson('/api/v1/public/tournament/participants');

        $response->assertOk()->assertJsonMissing(['phone' => '089999999999']);
    }

    public function test_administrator_can_correct_a_participants_category(): void
    {
        $participant = TournamentParticipant::factory()->create(['category' => TournamentParticipant::CATEGORY_BEGINNER]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->patchJson(
            "/api/v1/tournament/participants/{$participant->id}",
            ['category' => TournamentParticipant::CATEGORY_UPPER_BEGINNER],
        );

        $response->assertOk()->assertJsonPath('data.category', TournamentParticipant::CATEGORY_UPPER_BEGINNER);
    }

    public function test_non_admin_cannot_correct_a_participants_category(): void
    {
        $participant = TournamentParticipant::factory()->create(['category' => TournamentParticipant::CATEGORY_BEGINNER]);
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->patchJson(
            "/api/v1/tournament/participants/{$participant->id}",
            ['category' => TournamentParticipant::CATEGORY_UPPER_BEGINNER],
        )->assertForbidden();
    }

    public function test_non_admin_cannot_run_the_draw(): void
    {
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->postJson('/api/v1/tournament/draw')->assertForbidden();
    }

    public function test_guest_cannot_run_the_draw(): void
    {
        $this->postJson('/api/v1/tournament/draw')->assertUnauthorized();
    }

    public function test_administrator_can_draw_teams_pairing_beginner_with_upper_beginner_then_matches(): void
    {
        TournamentParticipant::factory()->count(4)->create(['category' => TournamentParticipant::CATEGORY_BEGINNER]);
        TournamentParticipant::factory()->count(4)->create(['category' => TournamentParticipant::CATEGORY_UPPER_BEGINNER]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/tournament/draw');

        // 4 teams (Beginner+Upper Beginner each) → 2 matches (team vs team).
        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame(4, TournamentTeam::count());

        foreach ($response->json('data') as $match) {
            $this->assertNotNull($match['team_one']);
            $this->assertNotNull($match['team_two']);
            foreach ([$match['team_one'], $match['team_two']] as $team) {
                $this->assertNotNull($team['participant_one']);
                $this->assertNotNull($team['participant_two']);
                $this->assertNotSame($team['participant_one']['category'], $team['participant_two']['category']);
            }
        }
    }

    public function test_lone_leftover_participant_forms_a_partner_less_solo_team(): void
    {
        TournamentParticipant::factory()->count(2)->create(['category' => TournamentParticipant::CATEGORY_BEGINNER]);
        TournamentParticipant::factory()->count(1)->create(['category' => TournamentParticipant::CATEGORY_UPPER_BEGINNER]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/tournament/draw')->assertOk();

        // 1 real Beginner+Upper Beginner team, plus 1 solo team from the leftover Beginner.
        $this->assertSame(2, TournamentTeam::count());
        $this->assertSame(1, TournamentTeam::whereNull('participant_two_id')->count());
    }

    public function test_odd_number_of_teams_leaves_one_with_a_bye_match(): void
    {
        TournamentParticipant::factory()->count(3)->create(['category' => TournamentParticipant::CATEGORY_BEGINNER]);
        TournamentParticipant::factory()->count(3)->create(['category' => TournamentParticipant::CATEGORY_UPPER_BEGINNER]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/tournament/draw');

        // 3 teams (odd) → 2 matches, one of them a bye.
        $response->assertOk()->assertJsonCount(2, 'data');
        $byes = collect($response->json('data'))->filter(fn (array $match) => $match['team_two'] === null);
        $this->assertCount(1, $byes);
    }

    public function test_rerunning_the_draw_replaces_the_previous_one(): void
    {
        TournamentParticipant::factory()->count(2)->create(['category' => TournamentParticipant::CATEGORY_BEGINNER]);
        TournamentParticipant::factory()->count(2)->create(['category' => TournamentParticipant::CATEGORY_UPPER_BEGINNER]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/tournament/draw')->assertJsonCount(1, 'data');
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/tournament/draw')->assertJsonCount(1, 'data');

        $this->assertSame(2, TournamentTeam::count());
        $this->getJson('/api/v1/public/tournament/matches')->assertJsonCount(1, 'data');
    }

    public function test_administrator_can_clear_the_draw_without_redrawing(): void
    {
        TournamentParticipant::factory()->count(2)->create(['category' => TournamentParticipant::CATEGORY_BEGINNER]);
        TournamentParticipant::factory()->count(2)->create(['category' => TournamentParticipant::CATEGORY_UPPER_BEGINNER]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/tournament/draw')->assertJsonCount(1, 'data');

        $this->actingAs($admin, 'sanctum')->deleteJson('/api/v1/tournament/draw')->assertOk()->assertJsonCount(0, 'data');

        $this->assertSame(0, TournamentTeam::count());
        $this->getJson('/api/v1/public/tournament/matches')->assertJsonCount(0, 'data');
        // Participants themselves are untouched — only the draw is cleared.
        $this->assertSame(4, TournamentParticipant::count());
    }

    public function test_non_admin_cannot_clear_the_draw(): void
    {
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->deleteJson('/api/v1/tournament/draw')->assertForbidden();
    }
}
