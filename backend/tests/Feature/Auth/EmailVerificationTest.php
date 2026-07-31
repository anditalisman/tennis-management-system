<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function signedLink(User $user, int $expiresInHours = 24): array
    {
        $expires = now()->addHours($expiresInHours)->timestamp;

        return [
            'id' => $user->id,
            'expires' => $expires,
            'signature' => User::emailVerificationSignature($user->id, $expires),
        ];
    }

    public function test_unverified_user_cannot_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'belumverif@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'belumverif@example.com',
            'password' => 'Password123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_new_guardian_registration_cannot_login_until_verified(): void
    {
        $branch = Branch::factory()->create();

        $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Anak Verif',
            'email' => 'anakverif@example.com',
            'age_category' => Participant::AGE_U10,
            'policy_accepted' => true,
            'guardian' => [
                'name' => 'Wali Verif',
                'relation' => 'Ayah',
                'phone' => '+6281200000010',
                'email' => 'waliverif@example.com',
                'password' => 'Password123',
            ],
        ])->assertCreated();

        $guardianUser = User::query()->where('email', 'waliverif@example.com')->firstOrFail();
        $this->assertNull($guardianUser->email_verified_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $guardianUser->id, 'channel' => 'email']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'waliverif@example.com',
            'password' => 'Password123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_valid_verification_link_unlocks_login(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'sahverif@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/verify-email', $this->signedLink($user))
            ->assertOk()
            ->assertJsonPath('data.message', 'Email berhasil diverifikasi. Silakan masuk ke portal.');

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'sahverif@example.com',
            'password' => 'Password123',
        ])->assertOk();
    }

    public function test_verification_rejects_tampered_signature(): void
    {
        $user = User::factory()->unverified()->create();
        $link = $this->signedLink($user);
        $link['signature'] = 'not-the-real-signature';

        $this->postJson('/api/v1/auth/verify-email', $link)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_rejects_an_expired_link(): void
    {
        $user = User::factory()->unverified()->create();
        $link = $this->signedLink($user, expiresInHours: -1);

        $this->postJson('/api/v1/auth/verify-email', $link)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_queues_a_new_email_for_an_unverified_account(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'kirimulang@example.com']);

        $this->postJson('/api/v1/auth/verify-email/resend', ['email' => 'kirimulang@example.com'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'channel' => 'email']);
    }

    public function test_resend_gives_the_same_response_for_an_unknown_email(): void
    {
        // Same generic message whether or not the address exists, so this
        // endpoint can't be used to enumerate registered accounts.
        $known = $this->postJson('/api/v1/auth/verify-email/resend', ['email' => 'tidakada@example.com']);
        $known->assertOk()->assertJsonPath(
            'data.message',
            'Jika email terdaftar dan belum diverifikasi, tautan verifikasi baru sudah dikirim.',
        );
    }
}
