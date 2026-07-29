<?php

namespace Tests\Feature\Branches;

use App\Models\Branch;
use App\Models\Court;
use App\Models\Invoice;
use App\Models\Participant;
use App\Models\Role;
use App\Models\TrainingClass;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function staffAt(string $roleSlug, Branch $branch): User
    {
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->roles()->attach(Role::query()->where('slug', $roleSlug)->firstOrFail());

        return $user;
    }

    public function test_administrator_assigned_to_a_branch_only_sees_that_branchs_participants(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Participant::factory()->count(2)->create(['branch_id' => $branchA->id]);
        Participant::factory()->count(3)->create(['branch_id' => $branchB->id]);

        $adminA = $this->staffAt(Role::ADMINISTRATOR, $branchA);

        $this->actingAs($adminA, 'sanctum')
            ->getJson('/api/v1/participants')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_administrator_without_a_branch_assigned_sees_all_participants(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Participant::factory()->count(2)->create(['branch_id' => $branchA->id]);
        Participant::factory()->count(3)->create(['branch_id' => $branchB->id]);

        $unassignedAdmin = User::factory()->create(['branch_id' => null]);
        $unassignedAdmin->roles()->attach(Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail());

        $this->actingAs($unassignedAdmin, 'sanctum')
            ->getJson('/api/v1/participants')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_super_admin_sees_participants_across_all_branches(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Participant::factory()->count(2)->create(['branch_id' => $branchA->id]);
        Participant::factory()->count(3)->create(['branch_id' => $branchB->id]);

        $superAdmin = $this->staffAt(Role::SUPER_ADMIN, $branchA);

        $this->actingAs($superAdmin, 'sanctum')
            ->getJson('/api/v1/participants')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_finance_only_sees_invoices_for_their_own_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Invoice::factory()->create(['branch_id' => $branchA->id]);
        Invoice::factory()->count(2)->create(['branch_id' => $branchB->id]);

        $financeA = $this->staffAt(Role::FINANCE, $branchA);

        $this->actingAs($financeA, 'sanctum')
            ->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_administrator_only_sees_courts_at_their_own_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        Court::factory()->create(['branch_id' => $branchA->id]);
        Court::factory()->count(2)->create(['branch_id' => $branchB->id]);

        $adminA = $this->staffAt(Role::ADMINISTRATOR, $branchA);

        $this->actingAs($adminA, 'sanctum')
            ->getJson('/api/v1/courts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_administrator_only_sees_classes_at_their_own_branch(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        TrainingClass::factory()->create(['branch_id' => $branchA->id]);
        TrainingClass::factory()->count(2)->create(['branch_id' => $branchB->id]);

        $adminA = $this->staffAt(Role::ADMINISTRATOR, $branchA);

        $this->actingAs($adminA, 'sanctum')
            ->getJson('/api/v1/classes')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
