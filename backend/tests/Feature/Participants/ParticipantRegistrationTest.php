<?php

namespace Tests\Feature\Participants;

use App\Models\Branch;
use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_can_register_a_child_with_a_new_guardian_account(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Andi Kecil',
            'email' => 'andikecil@example.com',
            'age_category' => Participant::AGE_U10,
            'gender' => 'male',
            'skill_level' => 'beginner',
            'policy_accepted' => true,
            'guardian' => [
                'name' => 'Budi Wali',
                'relation' => 'Ayah',
                'phone' => '+6281200000001',
                'email' => 'buditwali@example.com',
                'password' => 'Password123',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.full_name', 'Andi Kecil')
            ->assertJsonPath('data.status', Participant::STATUS_PENDING_VERIFICATION)
            ->assertJsonPath('data.guardians.0.name', 'Budi Wali')
            ->assertJsonPath('data.guardians.0.is_primary', true);

        $this->assertDatabaseHas('users', ['email' => 'buditwali@example.com']);
        $this->assertDatabaseHas('participants', ['full_name' => 'Andi Kecil', 'user_id' => null]);

        $guardianUser = User::query()->where('email', 'buditwali@example.com')->firstOrFail();
        $this->assertTrue($guardianUser->hasRole(Role::GUARDIAN));
    }

    public function test_registration_reuses_an_existing_guardian_account_for_a_second_child(): void
    {
        $branch = Branch::factory()->create();
        $guardianUser = User::factory()->create(['email' => 'wali2@example.com']);
        $guardianUser->roles()->attach(Role::query()->where('slug', Role::GUARDIAN)->firstOrFail());
        Guardian::factory()->create(['user_id' => $guardianUser->id]);

        $response = $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Anak Kedua',
            'email' => 'anakkedua@example.com',
            'age_category' => Participant::AGE_U12,
            'policy_accepted' => true,
            'guardian' => [
                'name' => 'Wali Dua',
                'relation' => 'Ibu',
                'phone' => '+6281200000002',
                'email' => 'wali2@example.com',
                'password' => 'Password123',
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(1, User::query()->where('email', 'wali2@example.com')->count());
        $this->assertSame(1, $guardianUser->guardian->participants()->count());
    }

    public function test_registration_rejects_guardian_email_already_used_by_a_non_guardian(): void
    {
        $branch = Branch::factory()->create();
        User::factory()->create(['email' => 'staff@example.com']);

        $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Anak Tiga',
            'email' => 'anaktiga@example.com',
            'age_category' => Participant::AGE_U14,
            'policy_accepted' => true,
            'guardian' => [
                'name' => 'Staff',
                'relation' => 'Ibu',
                'phone' => '+6281200000003',
                'email' => 'staff@example.com',
                'password' => 'Password123',
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['guardian.email']);
    }

    public function test_registration_requires_guardian_for_a_minor_category(): void
    {
        $branch = Branch::factory()->create();

        $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Tanpa Wali',
            'email' => 'tanpawali@example.com',
            'age_category' => Participant::AGE_U16,
            'policy_accepted' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['guardian']);
    }

    public function test_registration_requires_guardian_for_the_prestasi_category_regardless_of_age(): void
    {
        $branch = Branch::factory()->create();

        $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Atlet Prestasi',
            'email' => 'atletprestasi@example.com',
            'age_category' => Participant::AGE_PRESTASI,
            'policy_accepted' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['guardian']);
    }

    public function test_guest_can_self_register_as_an_adult_with_their_own_password(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Dewi Dewasa',
            'email' => 'dewidewasa@example.com',
            'phone' => '+6281200000099',
            'age_category' => Participant::AGE_DEWASA,
            'password' => 'Password123',
            'policy_accepted' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.full_name', 'Dewi Dewasa');

        $user = User::query()->where('email', 'dewidewasa@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole(Role::PARTICIPANT));
        $this->assertDatabaseHas('participants', ['full_name' => 'Dewi Dewasa', 'user_id' => $user->id]);
    }

    public function test_registration_requires_a_password_for_an_adult_guest_without_a_guardian(): void
    {
        $branch = Branch::factory()->create();

        $this->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => 'Tanpa Password',
            'email' => 'tanpapassword@example.com',
            'age_category' => Participant::AGE_DEWASA,
            'policy_accepted' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
    }

    public function test_authenticated_adult_can_self_register_without_a_guardian_or_password(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('slug', Role::PARTICIPANT)->firstOrFail());
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/v1/participants', [
            'branch_id' => $branch->id,
            'full_name' => $user->name,
            'email' => 'sudahlogin@example.com',
            'age_category' => Participant::AGE_DEWASA,
            'policy_accepted' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.user_id', $user->uuid);
    }

    public function test_registration_generates_sequential_registration_numbers(): void
    {
        $branch = Branch::factory()->create(['slug' => 'pusat']);

        $payload = fn (string $name, string $email, string $guardianEmail) => [
            'branch_id' => $branch->id,
            'full_name' => $name,
            'email' => $email,
            'age_category' => Participant::AGE_U10,
            'policy_accepted' => true,
            'guardian' => [
                'name' => 'Wali',
                'relation' => 'Ayah',
                'phone' => '+6281200000000',
                'email' => $guardianEmail,
                'password' => 'Password123',
            ],
        ];

        $first = $this->postJson('/api/v1/participants', $payload('Anak Satu', 'anaksatu@example.com', 'walisatu@example.com'))->json('data');
        $second = $this->postJson('/api/v1/participants', $payload('Anak Dua', 'anakdua@example.com', 'waliduaa@example.com'))->json('data');

        $this->assertMatchesRegularExpression('/^ZT-\d{6}-\d{4}$/', $first['registration_no']);
        $this->assertNotSame($first['registration_no'], $second['registration_no']);
    }
}
