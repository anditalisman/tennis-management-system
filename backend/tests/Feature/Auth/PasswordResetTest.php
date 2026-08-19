<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\WhatsappOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_code_via_whatsapp(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'channel' => 'whatsapp']);
    }

    public function test_forgot_password_for_unknown_email_gives_the_same_response_without_erroring(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'tidak-ada@example.com']);

        $response->assertOk()->assertJsonPath(
            'data.message',
            'Jika akun ditemukan, kode reset kata sandi sudah dikirim via WhatsApp.',
        );
    }

    public function test_correct_code_resets_the_password_and_revokes_existing_tokens(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        $oldToken = $user->createToken('old-device')->plainTextToken;
        app(WhatsappOtpService::class)->generateAndSendPasswordReset($user);
        $code = Cache::get("password_reset_otp:{$user->id}");

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertOk();

        $this->assertSame(0, $user->tokens()->count());

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'NewPassword456'])
            ->assertOk();

        $this->withHeader('Authorization', "Bearer {$oldToken}")
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_wrong_code_does_not_reset_the_password(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        app(WhatsappOtpService::class)->generateAndSendPasswordReset($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'code' => '000000',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertUnprocessable();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'OldPassword123'])
            ->assertOk();
    }

    public function test_reset_code_cannot_be_reused(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123']);
        app(WhatsappOtpService::class)->generateAndSendPasswordReset($user);
        $code = Cache::get("password_reset_otp:{$user->id}");

        $payload = [
            'email' => $user->email,
            'code' => $code,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ];

        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
    }
}
