<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Coach;
use App\Models\Court;
use App\Models\Package;
use App\Models\Program;
use App\Models\TrainingClass;
use App\Models\TrainingSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo catalogue content (programs, packages, coaches, courts, classes,
 * schedules) so the public marketing site and portals have something real
 * to render in local development / staging demos. Never seeded in
 * production (see DatabaseSeeder).
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $pusat = Branch::query()->where('slug', 'pusat')->firstOrFail();

        $selatan = Branch::query()->updateOrCreate(
            ['slug' => 'selatan'],
            [
                'name' => 'Zul Tennis Clinic — Cabang Selatan',
                'address' => 'Jl. Kebayoran Raya No. 25, Jakarta Selatan',
                'phone' => '+62 811 0000 0001',
                'status' => Branch::STATUS_ACTIVE,
            ],
        );

        Court::query()->updateOrCreate(
            ['branch_id' => $pusat->id, 'name' => 'Lapangan 2'],
            ['surface_type' => 'hard', 'operating_hours' => ['06:00', '21:00'], 'status' => Court::STATUS_ACTIVE],
        );
        $courtSelatan = Court::query()->updateOrCreate(
            ['branch_id' => $selatan->id, 'name' => 'Lapangan 1'],
            ['surface_type' => 'clay', 'operating_hours' => ['06:00', '21:00'], 'status' => Court::STATUS_ACTIVE],
        );

        $coachAccounts = [
            ['name' => 'Coach Rina', 'email' => 'rina.coach@zultennisclinic.test', 'branch' => $pusat, 'bio' => 'Mantan atlet nasional junior, fokus pembinaan usia dini.'],
            ['name' => 'Coach Andra', 'email' => 'andra.coach@zultennisclinic.test', 'branch' => $selatan, 'bio' => 'Pelatih bersertifikat ITF Level 1, spesialis teknik pukulan.'],
        ];
        $coaches = [];
        foreach ($coachAccounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'branch_id' => $account['branch']->id,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'status' => User::STATUS_ACTIVE,
                ],
            );
            $coaches[] = Coach::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'branch_id' => $account['branch']->id,
                    'employment_status' => Coach::STATUS_ACTIVE,
                    'bio' => $account['bio'],
                    'certifications' => ['ITF Level 1'],
                ],
            );
        }

        $mainCoach = Coach::query()->where('user_id', User::query()->where('email', 'coach@zultennisclinic.test')->value('id'))->first();
        $mainCourt = Court::query()->where('branch_id', $pusat->id)->where('name', 'Lapangan Utama')->firstOrFail();

        $programs = [
            [
                'name' => 'Little Aces (Anak-anak)',
                'age_group' => 'anak',
                'skill_level' => 'beginner',
                'target_competency' => 'Pengenalan raket, koordinasi bola dasar, kedisiplinan berlatih.',
                'description' => 'Program pengenalan tenis untuk usia 5-10 tahun dengan pendekatan bermain sambil belajar.',
                'branch' => $pusat,
                'coach' => $coaches[0],
                'court' => $mainCourt,
                'packages' => [
                    ['name' => 'Paket 8x Sesi', 'session_count' => 8, 'price' => 1200000],
                    ['name' => 'Paket 16x Sesi', 'session_count' => 16, 'price' => 2200000],
                ],
            ],
            [
                'name' => 'Junior Development (Remaja)',
                'age_group' => 'remaja',
                'skill_level' => 'intermediate',
                'target_competency' => 'Teknik pukulan menengah, strategi permainan tunggal & ganda.',
                'description' => 'Program pembinaan usia 11-17 tahun untuk peserta yang sudah menguasai dasar tenis.',
                'branch' => $pusat,
                'coach' => $mainCoach,
                'court' => $mainCourt,
                'packages' => [
                    ['name' => 'Paket 12x Sesi', 'session_count' => 12, 'price' => 2400000],
                ],
            ],
            [
                'name' => 'Performance Adult (Dewasa)',
                'age_group' => 'dewasa',
                'skill_level' => 'advanced',
                'target_competency' => 'Konsistensi pukulan lanjutan, taktik pertandingan, kebugaran spesifik tenis.',
                'description' => 'Program intensif untuk dewasa yang ingin meningkatkan performa kompetitif.',
                'branch' => $selatan,
                'coach' => $coaches[1],
                'court' => $courtSelatan,
                'packages' => [
                    ['name' => 'Paket 10x Sesi', 'session_count' => 10, 'price' => 3000000],
                    ['name' => 'Paket 20x Sesi', 'session_count' => 20, 'price' => 5600000],
                ],
            ],
        ];

        $weekdays = ['Senin', 'Rabu', 'Jumat'];

        foreach ($programs as $spec) {
            $program = Program::query()->updateOrCreate(
                ['name' => $spec['name']],
                [
                    'age_group' => $spec['age_group'],
                    'skill_level' => $spec['skill_level'],
                    'target_competency' => $spec['target_competency'],
                    'description' => $spec['description'],
                    'status' => Program::STATUS_ACTIVE,
                ],
            );

            foreach ($spec['packages'] as $pkg) {
                Package::query()->updateOrCreate(
                    ['program_id' => $program->id, 'name' => $pkg['name']],
                    [
                        'session_count' => $pkg['session_count'],
                        'validity_days' => 90,
                        'price' => $pkg['price'],
                        'type' => 'regular',
                        'status' => Package::STATUS_ACTIVE,
                    ],
                );
            }

            $class = TrainingClass::query()->updateOrCreate(
                ['program_id' => $program->id, 'name' => $spec['name'].' — Kelas Reguler'],
                [
                    'branch_id' => $spec['branch']->id,
                    'coach_id' => $spec['coach']?->id,
                    'court_id' => $spec['court']->id,
                    'capacity_min' => 1,
                    'capacity_max' => 8,
                    'session_duration' => 60,
                    'status' => TrainingClass::STATUS_ACTIVE,
                ],
            );

            foreach (range(0, 5) as $week) {
                foreach ($weekdays as $i => $label) {
                    $date = now()->next($label === 'Senin' ? 'Monday' : ($label === 'Rabu' ? 'Wednesday' : 'Friday'))->addWeeks($week);

                    TrainingSchedule::query()->updateOrCreate(
                        ['class_id' => $class->id, 'session_date' => $date->format('Y-m-d')],
                        [
                            'court_id' => $spec['court']->id,
                            'coach_id' => $spec['coach']?->id,
                            'start_time' => $spec['age_group'] === 'anak' ? '15:00' : '16:30',
                            'end_time' => $spec['age_group'] === 'anak' ? '16:00' : '17:30',
                            'type' => TrainingSchedule::TYPE_REGULAR,
                            'status' => TrainingSchedule::STATUS_SCHEDULED,
                        ],
                    );
                }
            }
        }
    }
}
