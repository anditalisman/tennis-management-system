<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_user_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'budi@example.com')
            ->assertJsonPath('data.user.roles.0', Role::PARTICIPANT)
            ->assertJsonPath('data.user.locale', 'id')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
    }

    public function test_registration_requires_valid_unique_email_and_matching_password(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Budi',
            'email' => 'taken@example.com',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_a_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'Password123',
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'Password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->uuid)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => 'Password123',
            'status' => User::STATUS_SUSPENDED,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'Password123',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_own_profile(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', Role::COACH)->first();
        $user->roles()->attach($role);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->uuid)
            ->assertJsonPath('data.roles.0', Role::COACH);
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_a_user_can_logout_and_revoke_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }
}
