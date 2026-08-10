<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Seed only production-required records.
     *
     * The current release has no lookup tables, roles, permissions, or system
     * records that must be inserted after migrations. Keep this seeder explicit
     * so production deploys never need to run demo/UAT seeders.
     */
    public function run(): void
    {
        $this->call([
            RefreshDataPOSBlogContentSeeder::class,
        ]);

        $this->command?->info('ProductionSeeder completed: production blog content seeded.');
    }
}
