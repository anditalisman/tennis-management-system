<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Coach;
use App\Models\Guardian;
use App\Models\Participant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * DatabaseSeeder skips DemoUserSeeder in production (no accounts with a
 * publicly-known password on a real deployment), so a fresh production
 * database has zero users. This is the supported way to create the first
 * real account instead of doing it ad-hoc via `tinker`. Upserts by email,
 * so it's also safe to re-run after a redeploy that reset the database.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Nama lengkap}
        {--email= : Email login}
        {--password= : Kata sandi (minimal 8 karakter)}
        {--role=super-admin : Slug peran, mis. super-admin, administrator, management}';

    protected $description = 'Buat atau perbarui satu akun staf (dipakai untuk membuat admin pertama di production, di mana akun demo sengaja tidak di-seed)';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nama lengkap');
        $email = $this->option('email') ?: $this->ask('Email login');
        $password = $this->option('password') ?: $this->secret('Kata sandi (minimal 8 karakter)');
        $roleSlug = $this->option('role');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'role' => $roleSlug],
            ['name' => 'required|string', 'email' => 'required|email', 'password' => 'required|string|min:8', 'role' => 'required|string'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $role = Role::query()->where('slug', $roleSlug)->first();
        if (! $role) {
            $this->error("Peran '{$roleSlug}' tidak ditemukan. Peran yang tersedia: ".Role::query()->pluck('slug')->implode(', '));

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'status' => User::STATUS_ACTIVE,
                'branch_id' => Branch::query()->value('id'),
            ],
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        // Role alone only unlocks menus/permissions — several features key off
        // a *profile* row instead (Coach for evaluations/gallery uploads,
        // Participant for check-in/enrollment, Guardian for linked children).
        // Without this, an account created here for one of these roles would
        // pass every permission check and still fail at the first
        // profile-dependent action (e.g. a coach unable to submit an
        // evaluation because $user->coach is null).
        match ($role->slug) {
            Role::COACH => Coach::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['branch_id' => $user->branch_id, 'employment_status' => Coach::STATUS_ACTIVE],
            ),
            Role::PARTICIPANT => Participant::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'branch_id' => $user->branch_id,
                    'full_name' => $user->name,
                    'status' => Participant::STATUS_ACTIVE,
                    'policy_accepted_at' => now(),
                ],
            ),
            Role::GUARDIAN => Guardian::query()->updateOrCreate(['user_id' => $user->id], []),
            default => null,
        };

        $this->info("Akun '{$user->email}' siap dengan peran '{$role->slug}'.");

        return self::SUCCESS;
    }
}
