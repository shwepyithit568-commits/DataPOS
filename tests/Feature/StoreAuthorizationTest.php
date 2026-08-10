<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_a_staff_cannot_access_store_b_dashboard(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $staffA = User::create([
            'name' => 'Staff A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $staffA->stores()->attach($storeA->id, ['role' => 'staff', 'status' => 'active']);

        // Staff A accessing Store A -> Success
        $responseA = $this->actingAs($staffA)->get('/store/store-a/dashboard');
        $responseA->assertStatus(200);

        // Staff A accessing Store B -> Forbidden (Security Requirement 1)
        $responseB = $this->actingAs($staffA)->get('/store/store-b/dashboard');
        $responseB->assertStatus(403);
    }

    public function test_platform_owner_can_access_any_store_dashboard(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $owner = User::create([
            'name' => 'Owner',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);

        $response = $this->actingAs($owner)->get('/store/store-a/dashboard');
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_platform_admin_dashboard(): void
    {
        $user = User::create([
            'name' => 'Regular Customer',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Security Requirement 3: Unauthorized admin route access
        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_user_cannot_bypass_store_access_by_changing_slug_parameter(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09333333333',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        // Security Requirement 2: Manual store_id/slug tampering
        $response = $this->actingAs($managerA)->get('/store/store-b/dashboard');
        $response->assertStatus(403);
    }
}
