<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            RolePermissionSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                DemoUserSeeder::class,
                DemoContentSeeder::class,
            ]);
        }
    }
}
