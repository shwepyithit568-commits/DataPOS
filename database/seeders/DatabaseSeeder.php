<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * UAT seed data is NOT loaded automatically. Production deployments should
     * use the explicit production-safe seeder:
     *
     *   php artisan db:seed --class=ProductionSeeder
     *
     * Run the UAT seeder explicitly only in local/UAT environments when needed:
     *
     *   php artisan db:seed --class=UatSeeder
     *
     * The UatSeeder requires ALLOW_UAT_SEEDING=true in .env
     * and will abort in production environments.
     */
    public function run(): void
    {
        // No automatic seeding.
        // Production use: php artisan db:seed --class=ProductionSeeder
        // Local/UAT use: php artisan db:seed --class=UatSeeder
    }
}
