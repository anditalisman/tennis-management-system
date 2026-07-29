<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            ['slug' => 'pusat'],
            [
                'name' => 'Zul Tennis Clinic',
                'address' => 'Jl. Lapangan Tenis No. 1, Jakarta',
                'phone' => '+62 811 0000 0000',
                'status' => Branch::STATUS_ACTIVE,
            ],
        );
    }
}
