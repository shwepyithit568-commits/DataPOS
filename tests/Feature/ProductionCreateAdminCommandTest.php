<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionCreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_admin_command_rejects_weak_password(): void
    {
        $this->artisan('production:create-admin', [
            '--role' => 'platform_owner',
            '--name' => 'Production Owner',
            '--phone' => '09911111111',
            '--password' => 'password',
            '--password-confirmation' => 'password',
        ])
            ->expectsOutputToContain('password')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['phone' => '09911111111']);
    }

    public function test_initial_admin_command_rejects_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '09922222222']);

        $this->artisan('production:create-admin', [
            '--role' => 'platform_owner',
            '--name' => 'Production Owner',
            '--phone' => '09922222222',
            '--password' => 'StrongPass#12345',
            '--password-confirmation' => 'StrongPass#12345',
        ])
            ->expectsOutputToContain('phone')
            ->assertExitCode(1);
    }

    public function test_initial_admin_command_creates_platform_owner_without_printing_plaintext_password(): void
    {
        $password = 'StrongPass#12345';

        $this->artisan('production:create-admin', [
            '--role' => 'platform_owner',
            '--name' => 'Production Owner',
            '--phone' => '09933333333',
            '--password' => $password,
            '--password-confirmation' => $password,
        ])
            ->expectsOutput('Production admin account created.')
            ->doesntExpectOutput($password)
            ->assertExitCode(0);

        $user = User::where('phone', '09933333333')->firstOrFail();

        $this->assertSame('platform_owner', $user->role);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertDatabaseMissing('store_user', ['user_id' => $user->id]);
    }

    public function test_initial_admin_command_creates_store_manager_assignment(): void
    {
        $store = Store::create([
            'name' => 'Production Store',
            'slug' => 'production-store',
            'is_active' => true,
        ]);

        $this->artisan('production:create-admin', [
            '--role' => 'store_manager',
            '--store' => 'production-store',
            '--name' => 'Store Manager',
            '--phone' => '09944444444',
            '--password' => 'StrongPass#12345',
            '--password-confirmation' => 'StrongPass#12345',
        ])
            ->expectsOutput('Production admin account created.')
            ->assertExitCode(0);

        $user = User::where('phone', '09944444444')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertDatabaseHas('store_user', [
            'user_id' => $user->id,
            'store_id' => $store->id,
            'role' => 'store_manager',
            'status' => 'active',
        ]);
    }
}
