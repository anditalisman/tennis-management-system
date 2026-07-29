<?php

namespace Tests\Feature\Inventory;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
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

    public function test_coach_can_view_inventory_but_not_manage(): void
    {
        InventoryItem::factory()->create();
        $coach = $this->userWithRole(Role::COACH);

        $this->actingAs($coach, 'sanctum')->getJson('/api/v1/inventory-items')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($coach, 'sanctum')->postJson('/api/v1/inventory-items', ['name' => 'X'])->assertForbidden();
    }

    public function test_administrator_can_create_an_item_and_record_stock_in(): void
    {
        $branch = Branch::factory()->create();
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/inventory-items', [
            'branch_id' => $branch->id,
            'name' => 'Raket Wilson',
            'category' => 'equipment',
            'stock_qty' => 10,
        ]);
        $response->assertCreated()->assertJsonPath('data.stock_qty', 10);
        $itemId = $response->json('data.id');

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/inventory-items/{$itemId}/transactions", [
            'type' => 'in',
            'qty' => 5,
        ])->assertCreated();

        $this->assertSame(15, InventoryItem::query()->find($itemId)->stock_qty);
    }

    public function test_administrator_can_create_an_item_without_a_branch(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/inventory-items', [
            'name' => 'Bola Tenis',
            'category' => 'equipment',
        ]);

        $response->assertCreated()->assertJsonPath('data.branch_id', null);
    }

    public function test_borrowing_decreases_stock_and_returning_increases_it_back(): void
    {
        $item = InventoryItem::factory()->create(['stock_qty' => 5]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/inventory-items/{$item->id}/transactions", [
            'type' => 'borrow',
            'qty' => 2,
        ])->assertCreated();
        $this->assertSame(3, $item->fresh()->stock_qty);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/inventory-items/{$item->id}/transactions", [
            'type' => 'return',
            'qty' => 2,
        ])->assertCreated();
        $this->assertSame(5, $item->fresh()->stock_qty);
    }

    public function test_cannot_take_out_more_stock_than_available(): void
    {
        $item = InventoryItem::factory()->create(['stock_qty' => 3]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/inventory-items/{$item->id}/transactions", [
            'type' => 'out',
            'qty' => 10,
        ])->assertUnprocessable();

        $this->assertSame(3, $item->fresh()->stock_qty);
    }

    public function test_loss_records_a_transaction_and_writes_off_stock(): void
    {
        $item = InventoryItem::factory()->create(['stock_qty' => 8]);
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/v1/inventory-items/{$item->id}/transactions", [
            'type' => 'loss',
            'qty' => 1,
            'note' => 'Hilang saat sesi latihan',
        ]);

        $response->assertCreated();
        $this->assertSame(7, $item->fresh()->stock_qty);
        $this->assertDatabaseHas('inventory_transactions', ['item_id' => $item->id, 'type' => 'loss', 'qty' => 1]);
    }

    public function test_participant_cannot_access_inventory(): void
    {
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/inventory-items')->assertForbidden();
    }
}
