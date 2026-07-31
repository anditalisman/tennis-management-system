<?php

namespace Tests\Feature\PaymentMethods;

use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentMethodManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('s3');
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_super_admin_can_create_a_qris_payment_method_with_an_image(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/payment-methods', [
            'type' => 'qris',
            'label' => 'QRIS Zul Tennis Clinic',
            'details' => 'Scan untuk membayar.',
            'image' => UploadedFile::fake()->image('qris.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'qris')
            ->assertJsonPath('data.label', 'QRIS Zul Tennis Clinic')
            ->assertJsonPath('data.is_active', true);

        $this->assertNotNull($response->json('data.image_url'));
        $this->assertDatabaseHas('payment_methods', ['label' => 'QRIS Zul Tennis Clinic']);
    }

    public function test_finance_can_manage_payment_methods(): void
    {
        $finance = $this->userWithRole(Role::FINANCE);

        $this->actingAs($finance, 'sanctum')->postJson('/api/v1/payment-methods', [
            'type' => 'bank_transfer',
            'label' => 'BCA — Zul Tennis Clinic',
            'details' => 'a.n. Zul Tennis Clinic, No. Rek 1234567890',
        ])->assertCreated();
    }

    public function test_administrator_cannot_manage_payment_methods(): void
    {
        $admin = $this->userWithRole(Role::ADMINISTRATOR);

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/payment-methods', [
            'type' => 'qris',
            'label' => 'QRIS',
        ])->assertForbidden();
    }

    public function test_participant_can_view_but_not_manage_payment_methods(): void
    {
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $this->actingAs($participant, 'sanctum')->getJson('/api/v1/payment-methods')->assertOk();
        $this->actingAs($participant, 'sanctum')->postJson('/api/v1/payment-methods', [
            'type' => 'qris',
            'label' => 'QRIS',
        ])->assertForbidden();
    }

    public function test_participant_only_sees_active_payment_methods(): void
    {
        PaymentMethod::factory()->create(['label' => 'Aktif', 'is_active' => true]);
        PaymentMethod::factory()->create(['label' => 'Nonaktif', 'is_active' => false]);
        $participant = $this->userWithRole(Role::PARTICIPANT);

        $response = $this->actingAs($participant, 'sanctum')->getJson('/api/v1/payment-methods');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.label', 'Aktif');
    }

    public function test_super_admin_sees_inactive_payment_methods_too(): void
    {
        PaymentMethod::factory()->create(['label' => 'Aktif', 'is_active' => true]);
        PaymentMethod::factory()->create(['label' => 'Nonaktif', 'is_active' => false]);
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/payment-methods');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_super_admin_can_update_and_delete_a_payment_method(): void
    {
        $method = PaymentMethod::factory()->create(['label' => 'QRIS Lama']);
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/payment-methods/{$method->id}", ['label' => 'QRIS Baru', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.label', 'QRIS Baru')
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/payment-methods/{$method->id}")->assertOk();
        $this->assertSoftDeleted('payment_methods', ['id' => $method->id]);
    }
}
