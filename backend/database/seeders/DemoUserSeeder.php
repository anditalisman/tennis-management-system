<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Coach;
use App\Models\Court;
use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts for local development / staging demos.
 * Password for every account below is: "password" — never seeded in production (see DatabaseSeeder).
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('slug', 'pusat')->first();

        $accounts = [
            ['name' => 'Super Admin', 'email' => 'superadmin@zultennisclinic.test', 'role' => Role::SUPER_ADMIN],
            ['name' => 'Pemilik Klinik', 'email' => 'owner@zultennisclinic.test', 'role' => Role::MANAGEMENT],
            ['name' => 'Admin Operasional', 'email' => 'admin@zultennisclinic.test', 'role' => Role::ADMINISTRATOR],
            ['name' => 'Coach Dimas', 'email' => 'coach@zultennisclinic.test', 'role' => Role::COACH],
            ['name' => 'Budi Peserta', 'email' => 'peserta@zultennisclinic.test', 'role' => Role::PARTICIPANT],
            ['name' => 'Ibu Wali', 'email' => 'wali@zultennisclinic.test', 'role' => Role::GUARDIAN],
            ['name' => 'Petugas Keuangan', 'email' => 'keuangan@zultennisclinic.test', 'role' => Role::FINANCE],
        ];

        $users = [];

        foreach ($accounts as $account) {
            $role = Role::query()->where('slug', $account['role'])->first();

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'branch_id' => $branch?->id,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => User::STATUS_ACTIVE,
                ],
            );

            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            $users[$account['role']] = $user;
        }

        if (! $branch) {
            return;
        }

        Court::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Lapangan Utama'],
            ['surface_type' => 'hard', 'status' => Court::STATUS_ACTIVE],
        );

        Coach::query()->updateOrCreate(
            ['user_id' => $users[Role::COACH]->id],
            ['branch_id' => $branch->id, 'employment_status' => Coach::STATUS_ACTIVE, 'bio' => 'Pelatih inti Zul Tennis Clinic.'],
        );

        $guardian = Guardian::query()->updateOrCreate(
            ['user_id' => $users[Role::GUARDIAN]->id],
            ['relation' => 'Ibu'],
        );

        Participant::query()->firstOrCreate(
            ['user_id' => $users[Role::PARTICIPANT]->id],
            [
                'branch_id' => $branch->id,
                'full_name' => $users[Role::PARTICIPANT]->name,
                'skill_level' => 'intermediate',
                'status' => Participant::STATUS_ACTIVE,
                'policy_accepted_at' => now(),
            ],
        );

        $child = Participant::query()->firstOrCreate(
            ['registration_no' => 'PUSAT-DEMO-00001'],
            [
                'branch_id' => $branch->id,
                'full_name' => 'Adik Peserta',
                'birth_date' => now()->subYears(9),
                'gender' => 'female',
                'skill_level' => 'beginner',
                'status' => Participant::STATUS_ACTIVE,
                'policy_accepted_at' => now(),
            ],
        );
        $guardian->participants()->syncWithoutDetaching([$child->id => ['is_primary' => true]]);
    }
}
