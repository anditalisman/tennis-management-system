<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeds the 7 core roles and a baseline permission catalog derived from the
 * access matrix in the Tahap 1 design document (§04). Permission granularity
 * is coarse (view / manage / verify per module) — per-record scoping (e.g.
 * "own data only") is enforced by Policies, not by splitting permissions.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * module => ['view' => [roles...], 'manage' => [roles...], 'verify' => [roles...]]
     */
    private const MATRIX = [
        'system-config' => ['view' => ['management'], 'manage' => ['super-admin']],
        'users' => ['manage' => ['super-admin']],
        'branches' => ['view' => ['management'], 'manage' => ['super-admin']],
        'participants' => [
            'view' => ['management'],
            'manage' => ['super-admin', 'participant', 'guardian'],
            'verify' => ['administrator'],
        ],
        'programs-schedules' => [
            'view' => ['management', 'coach', 'participant', 'guardian'],
            'manage' => ['super-admin', 'administrator'],
        ],
        'courts-inventory' => [
            'view' => ['management', 'coach'],
            'manage' => ['super-admin', 'administrator'],
        ],
        'attendance-participants' => [
            'view' => ['management', 'guardian'],
            'manage' => ['super-admin', 'coach', 'participant'],
            'verify' => ['administrator'],
        ],
        'attendance-coaches' => [
            'view' => ['management'],
            'manage' => ['super-admin', 'coach'],
            'verify' => ['administrator'],
        ],
        'evaluations' => [
            'view' => ['management', 'administrator', 'participant', 'guardian'],
            'manage' => ['super-admin', 'coach'],
        ],
        'galleries' => [
            'view' => ['management', 'participant', 'guardian'],
            'manage' => ['super-admin', 'coach'],
            'verify' => ['administrator'],
        ],
        'packages' => [
            'view' => ['management', 'participant', 'guardian', 'finance'],
            'manage' => ['super-admin', 'administrator'],
        ],
        'invoices' => [
            'view' => ['management', 'participant', 'guardian'],
            'manage' => ['super-admin', 'administrator', 'finance'],
        ],
        'payments' => [
            'view' => ['management'],
            'manage' => ['super-admin', 'participant', 'guardian'],
            'verify' => ['finance'],
        ],
        'vouchers-refunds' => ['view' => ['management'], 'manage' => ['super-admin', 'finance']],
        'coaches' => [
            'view' => ['management', 'participant', 'guardian'],
            'manage' => ['super-admin', 'administrator', 'coach'],
        ],
        'announcements' => [
            'view' => ['management', 'participant', 'guardian'],
            'manage' => ['super-admin', 'administrator'],
        ],
        'reports' => ['view' => ['administrator', 'coach', 'finance'], 'manage' => ['super-admin', 'management']],
        'audit-logs' => ['view' => ['management'], 'manage' => ['super-admin']],
    ];

    private const ROLES = [
        ['slug' => Role::SUPER_ADMIN, 'name' => 'Super Admin', 'description' => 'Kontrol penuh atas seluruh sistem, konfigurasi, dan cabang.'],
        ['slug' => Role::MANAGEMENT, 'name' => 'Manajemen/Pemilik', 'description' => 'Visibilitas menyeluruh atas operasional dan keuangan untuk pengambilan keputusan.'],
        ['slug' => Role::ADMINISTRATOR, 'name' => 'Administrator', 'description' => 'Operasional harian: pendaftaran, kelas, jadwal, galeri, pengumuman.'],
        ['slug' => Role::COACH, 'name' => 'Pelatih', 'description' => 'Mengajar, absensi, evaluasi, dan dokumentasi sesi latihan.'],
        ['slug' => Role::PARTICIPANT, 'name' => 'Peserta', 'description' => 'Peserta latihan tenis.'],
        ['slug' => Role::GUARDIAN, 'name' => 'Orang Tua/Wali', 'description' => 'Mengelola satu atau beberapa akun peserta anak.'],
        ['slug' => Role::FINANCE, 'name' => 'Petugas Keuangan', 'description' => 'Tagihan, verifikasi pembayaran, voucher, dan laporan keuangan.'],
    ];

    public function run(): void
    {
        $roles = collect(self::ROLES)->mapWithKeys(function (array $attrs) {
            $role = Role::query()->updateOrCreate(['slug' => $attrs['slug']], $attrs);

            return [$attrs['slug'] => $role];
        });

        foreach (self::MATRIX as $module => $actions) {
            foreach ($actions as $action => $roleSlugs) {
                $permission = Permission::query()->updateOrCreate(
                    ['slug' => "{$module}.{$action}"],
                    ['name' => ucfirst($action)." {$module}", 'group' => $module],
                );

                $roleIds = $roles->only($roleSlugs)->pluck('id');
                $permission->roles()->syncWithoutDetaching($roleIds);
            }
        }
    }
}
