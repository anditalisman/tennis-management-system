<?php

namespace Tests\Feature\Invoices;

use App\Models\Guardian;
use App\Models\Package;
use App\Models\Participant;
use App\Models\ParticipantPackage;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceIssuanceTest extends TestCase
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

    public function test_finance_can_issue_an_invoice_for_a_package_purchase(): void
    {
        $package = Package::factory()->create(['price' => 1000000, 'session_count' => 8]);
        $participant = Participant::factory()->create();
        $finance = $this->userWithRole(Role::FINANCE);

        $response = $this->actingAs($finance, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $participant->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [
                ['item_type' => 'package', 'package_id' => $package->id],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total_amount', 1000000)
            ->assertJsonPath('data.status', 'unpaid')
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseHas('participant_packages', [
            'participant_id' => $participant->id,
            'package_id' => $package->id,
            'sessions_remaining' => 8,
            'status' => ParticipantPackage::STATUS_PENDING,
        ]);
    }

    public function test_invoice_supports_mixed_package_and_other_items_with_discount(): void
    {
        $package = Package::factory()->create(['price' => 500000]);
        $participant = Participant::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $participant->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'discount_amount' => 50000,
            'items' => [
                ['item_type' => 'package', 'package_id' => $package->id],
                ['item_type' => 'other', 'description' => 'Biaya pendaftaran', 'unit_price' => 100000],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal_amount', 600000)
            ->assertJsonPath('data.discount_amount', 50000)
            ->assertJsonPath('data.total_amount', 550000);
    }

    public function test_coach_cannot_issue_invoices(): void
    {
        $coach = $this->userWithRole(Role::COACH);
        $participant = Participant::factory()->create();

        $this->actingAs($coach, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $participant->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['item_type' => 'other', 'description' => 'x', 'unit_price' => 1000]],
        ])->assertForbidden();
    }

    public function test_participant_can_only_see_their_own_invoices(): void
    {
        $participantUser = $this->userWithRole(Role::PARTICIPANT);
        $ownParticipant = Participant::factory()->create(['user_id' => $participantUser->id]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $ownParticipant->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['item_type' => 'other', 'description' => 'x', 'unit_price' => 1000]],
        ]);

        $otherParticipant = Participant::factory()->create();
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $otherParticipant->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['item_type' => 'other', 'description' => 'y', 'unit_price' => 2000]],
        ]);

        $response = $this->actingAs($participantUser, 'sanctum')->getJson('/api/v1/invoices');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_guardian_can_see_invoices_for_linked_children(): void
    {
        $guardianUser = $this->userWithRole(Role::GUARDIAN);
        $guardian = Guardian::factory()->create(['user_id' => $guardianUser->id]);
        $child = Participant::factory()->create();
        $guardian->participants()->attach($child->id, ['is_primary' => true]);

        $admin = $this->userWithRole(Role::ADMINISTRATOR);
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $child->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => [['item_type' => 'other', 'description' => 'x', 'unit_price' => 1000]],
        ]);

        $this->actingAs($guardianUser, 'sanctum')
            ->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
