<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\WhatsappOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WhatsappVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_code_verifies_the_account_and_unlocks_login(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'Password123', 'phone' => '081234567890']);
        app(WhatsappOtpService::class)->generateAndSend($user);
        $code = Cache::get("whatsapp_otp:{$user->id}");

        $this->postJson('/api/v1/auth/verify-whatsapp', ['email' => $user->email, 'code' => $code])
            ->assertOk()
            ->assertJsonPath('data.message', 'Nomor WhatsApp berhasil diverifikasi. Silakan masuk ke portal.');

        $this->assertNotNull($user->fresh()->whatsapp_verified_at);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_wrong_code_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        app(WhatsappOtpService::class)->generateAndSend($user);

        $this->postJson('/api/v1/auth/verify-whatsapp', ['email' => $user->email, 'code' => '000000'])
            ->assertUnprocessable();

        $this->assertNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        app(WhatsappOtpService::class)->generateAndSend($user);
        $code = Cache::get("whatsapp_otp:{$user->id}");

        $this->travel(6)->minutes();

        $this->postJson('/api/v1/auth/verify-whatsapp', ['email' => $user->email, 'code' => $code])
            ->assertUnprocessable();

        $this->assertNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_unverified_user_cannot_login(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'Password123']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'Password123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_resend_sends_a_new_code(): void
    {
        $user = User::factory()->unverified()->create();

        $this->postJson('/api/v1/auth/verify-whatsapp/resend', ['email' => $user->email])->assertOk();

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'channel' => 'whatsapp']);
    }

    public function test_resend_for_unknown_account_gives_the_same_response_without_erroring(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-whatsapp/resend', ['email' => 'tidak-ada@example.com']);

        $response->assertOk()->assertJsonPath(
            'data.message',
            'Jika akun ditemukan dan belum terverifikasi, kode baru sudah dikirim via WhatsApp.',
        );
    }

    public function test_already_verified_account_gets_a_friendly_message_instead_of_an_error(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/verify-whatsapp', ['email' => $user->email, 'code' => '123456'])
            ->assertOk()
            ->assertJsonPath('data.message', 'Akun sudah terverifikasi. Silakan masuk.');
    }
}
