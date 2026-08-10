<?php

namespace Tests\Feature;

use Database\Seeders\UatSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_allowed_in_local_with_flag_true(): void
    {
        $this->app->detectEnvironment(fn() => 'local');
        config(['app.allow_uat_seeding' => true]);

        $seeder = new UatSeeder();
        $seeder->run();

        $this->assertDatabaseHas('stores', ['slug' => 'datapos-mobile']);
    }

    public function test_seeding_rejected_in_local_with_flag_false(): void
    {
        $this->app->detectEnvironment(fn() => 'local');
        config(['app.allow_uat_seeding' => false]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UatSeeder requires config("app.allow_uat_seeding") to be true');

        $seeder = new UatSeeder();
        $seeder->run();
    }

    public function test_seeding_allowed_in_testing_with_flag_true(): void
    {
        $this->app->detectEnvironment(fn() => 'testing');
        config(['app.allow_uat_seeding' => true]);

        $seeder = new UatSeeder();
        $seeder->run();

        $this->assertDatabaseHas('stores', ['slug' => 'datapos-mobile']);
    }

    public function test_seeding_allowed_in_uat_with_flag_true(): void
    {
        $this->app->detectEnvironment(fn() => 'uat');
        config(['app.allow_uat_seeding' => true]);

        $seeder = new UatSeeder();
        $seeder->run();

        $this->assertDatabaseHas('stores', ['slug' => 'datapos-mobile']);
    }

    public function test_seeding_rejected_in_production_even_with_flag_true(): void
    {
        $this->app->detectEnvironment(fn() => 'production');
        config(['app.allow_uat_seeding' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UatSeeder is NOT safe for non-local/testing/uat environments (production)');

        $seeder = new UatSeeder();
        $seeder->run();
    }

    public function test_seeding_rejected_in_staging_even_with_flag_true(): void
    {
        $this->app->detectEnvironment(fn() => 'staging');
        config(['app.allow_uat_seeding' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('UatSeeder is NOT safe for non-local/testing/uat environments (staging)');

        $seeder = new UatSeeder();
        $seeder->run();
    }
}
