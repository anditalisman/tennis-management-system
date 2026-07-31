<?php

namespace Tests\Feature\Packages;

use App\Models\Guardian;
use App\Models\Package;
use App\Models\Participant;
use App\Models\ParticipantPackage;
use App\Models\Role;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCheckoutTest extends TestCase
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

    public function test_participant_can_check_out_a_package_for_themselves(): void
    {
        $package = Package::factory()->create(['price' => 800000, 'session_count' => 8]);
        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        $participant = Participant::factory()->create(['user_id' => $participantUser->id]);

        $response = $this->actingAs($participantUser, 'sanctum')->postJson("/api/v1/packages/{$package->id}/checkout", []);

        $response->assertCreated()
            ->assertJsonPath('data.total_amount', 800000)
            ->assertJsonPath('data.status', 'unpaid');

        $this->assertDatabaseHas('participant_packages', [
            'participant_id' => $participant->id,
            'package_id' => $package->id,
            'status' => ParticipantPackage::STATUS_PENDING,
        ]);
    }

    public function test_guardian_can_check_out_a_package_for_their_own_child(): void
    {
        $package = Package::factory()->create(['price' => 500000]);
        $guardianUser = $this->userWithRole(Role::GUARDIAN);
        $guardian = Guardian::factory()->create(['user_id' => $guardianUser->id]);
        $child = Participant::factory()->create();
        $guardian->participants()->attach($child->id, ['is_primary' => true]);

        $response = $this->actingAs($guardianUser, 'sanctum')->postJson("/api/v1/packages/{$package->id}/checkout", [
            'participant_id' => $child->uuid,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('participant_packages', ['participant_id' => $child->id, 'package_id' => $package->id]);
    }

    public function test_guardian_cannot_check_out_a_package_for_an_unrelated_child(): void
    {
        $package = Package::factory()->create();
        $guardianUser = $this->userWithRole(Role::GUARDIAN);
        Guardian::factory()->create(['user_id' => $guardianUser->id]);
        $someoneElsesChild = Participant::factory()->create();

        $this->actingAs($guardianUser, 'sanctum')
            ->postJson("/api/v1/packages/{$package->id}/checkout", ['participant_id' => $someoneElsesChild->uuid])
            ->assertForbidden();
    }

    public function test_guardian_must_specify_which_child_is_checking_out(): void
    {
        $package = Package::factory()->create();
        $guardianUser = $this->userWithRole(Role::GUARDIAN);
        Guardian::factory()->create(['user_id' => $guardianUser->id]);

        $this->actingAs($guardianUser, 'sanctum')
            ->postJson("/api/v1/packages/{$package->id}/checkout", [])
            ->assertUnprocessable();
    }

    public function test_cannot_check_out_an_inactive_package(): void
    {
        $package = Package::factory()->create(['status' => Package::STATUS_INACTIVE]);
        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        Participant::factory()->create(['user_id' => $participantUser->id]);

        $this->actingAs($participantUser, 'sanctum')
            ->postJson("/api/v1/packages/{$package->id}/checkout", [])
            ->assertUnprocessable();
    }

    public function test_coach_cannot_check_out_a_package(): void
    {
        $package = Package::factory()->create();
        $coachUser = $this->userWithRole(Role::COACH);

        $this->actingAs($coachUser, 'sanctum')->postJson("/api/v1/packages/{$package->id}/checkout", [])->assertForbidden();
    }

    public function test_voucher_code_is_applied_at_checkout(): void
    {
        Voucher::factory()->create(['code' => 'HEMAT50K', 'discount_type' => Voucher::TYPE_FIXED, 'discount_value' => 50000]);
        $package = Package::factory()->create(['price' => 500000]);
        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        Participant::factory()->create(['user_id' => $participantUser->id]);

        $response = $this->actingAs($participantUser, 'sanctum')->postJson("/api/v1/packages/{$package->id}/checkout", [
            'voucher_code' => 'HEMAT50K',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.discount_amount', 50000)
            ->assertJsonPath('data.total_amount', 450000);
    }
}
