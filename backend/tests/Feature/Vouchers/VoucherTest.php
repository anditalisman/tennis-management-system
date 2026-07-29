<?php

namespace Tests\Feature\Vouchers;

use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherTest extends TestCase
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

    public function test_finance_can_create_a_voucher(): void
    {
        $finance = $this->userWithRole(Role::FINANCE);

        $response = $this->actingAs($finance, 'sanctum')->postJson('/api/v1/vouchers', [
            'code' => 'HEMAT50',
            'discount_type' => Voucher::TYPE_FIXED,
            'discount_value' => 50000,
        ]);

        $response->assertCreated()->assertJsonPath('data.code', 'HEMAT50');
    }

    public function test_administrator_cannot_create_vouchers(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/vouchers', [
            'code' => 'X', 'discount_type' => Voucher::TYPE_FIXED, 'discount_value' => 1000,
        ])->assertForbidden();
    }

    public function test_participant_can_validate_a_usable_voucher(): void
    {
        Voucher::factory()->create(['code' => 'DISKON10', 'discount_type' => Voucher::TYPE_PERCENTAGE, 'discount_value' => 10]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $response = $this->actingAs($participant, 'sanctum')->postJson('/api/v1/vouchers/validate', [
            'code' => 'DISKON10',
            'subtotal' => 100000,
        ]);

        $response->assertOk()->assertJsonPath('data.discount', 10000);
    }

    public function test_exhausted_voucher_fails_validation(): void
    {
        Voucher::factory()->create(['code' => 'HABIS', 'usage_limit' => 1, 'used_count' => 1]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->postJson('/api/v1/vouchers/validate', [
            'code' => 'HABIS',
            'subtotal' => 100000,
        ])->assertUnprocessable();
    }

    public function test_invoice_issuance_applies_voucher_discount(): void
    {
        Voucher::factory()->create(['code' => 'SAVE20K', 'discount_type' => Voucher::TYPE_FIXED, 'discount_value' => 20000]);
        $participant = Participant::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/invoices', [
            'participant_id' => $participant->uuid,
            'due_date' => now()->addDays(7)->toDateString(),
            'voucher_code' => 'SAVE20K',
            'items' => [['item_type' => 'other', 'description' => 'x', 'unit_price' => 100000]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.discount_amount', 20000)
            ->assertJsonPath('data.total_amount', 80000);

        $this->assertSame(1, Voucher::query()->where('code', 'SAVE20K')->firstOrFail()->used_count);
    }
}
