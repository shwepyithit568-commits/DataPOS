<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_owner_can_permanently_delete_inactive_store(): void
    {
        $owner = User::factory()->create(['role' => 'platform_owner', 'phone' => '09100000001']);
        $store1 = Store::create(['name' => 'Active Store', 'slug' => 'active-store', 'is_active' => true, 'is_primary' => true]);
        $store2 = Store::create(['name' => 'Legacy Store', 'slug' => 'legacy-store', 'is_active' => false, 'is_primary' => false]);

        $response = $this->actingAs($owner)
            ->delete("/admin/stores/{$store2->id}/force");

        $response->assertRedirect('/admin/stores');
        $this->assertDatabaseMissing('stores', ['id' => $store2->id]);
    }

    public function test_store_owner_cannot_access_or_see_admin_stores(): void
    {
        $store = Store::create(['name' => 'My Store', 'slug' => 'my-store', 'is_active' => true, 'is_primary' => true]);
        $storeOwner = User::factory()->create(['role' => 'customer', 'phone' => '09222222222']);
        $store->users()->attach($storeOwner->id, ['role' => 'store_manager', 'status' => 'active']);

        // 1. Direct access to /admin/stores is blocked with 403
        $this->actingAs($storeOwner)
            ->get('/admin/stores')
            ->assertForbidden();

        // 2. Store Manager admin dashboard sidebar does not show Store Management link
        $response = $this->actingAs($storeOwner)
            ->get("/store/{$store->slug}/admin/dashboard");
        $response->assertOk();
        $response->assertDontSee('/admin/stores');
    }

    public function test_platform_owner_sees_delete_button_on_stores_page(): void
    {
        $owner = User::factory()->create(['role' => 'platform_owner', 'phone' => '09100000001']);
        $store = Store::create(['name' => 'Test Store', 'slug' => 'test-store', 'is_active' => true, 'is_primary' => true]);

        $response = $this->actingAs($owner)->get('/admin/stores');
        $response->assertOk();
        $response->assertSee('အပြီးတိုင်ဖျက်မည်');
    }
}
