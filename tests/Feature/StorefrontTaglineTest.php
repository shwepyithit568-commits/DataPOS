<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTaglineTest extends TestCase
{
    use RefreshDatabase;

    /** Task 10B: tagline accepts null. */
    public function test_tagline_accepts_null(): void
    {
        $store = $this->storeWithManager('store-a', '09881110001');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'store_name' => 'Store A',
            'default_language' => 'my',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store['store']->id,
            'tagline' => null,
        ]);
    }

    /** Task 10B: tagline accepts a valid value. */
    public function test_tagline_accepts_valid_value(): void
    {
        $store = $this->storeWithManager('store-a', '09881110002');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'store_name' => 'Store A',
            'tagline' => 'Genuine Mobile Accessories',
            'default_language' => 'my',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store['store']->id,
            'tagline' => 'Genuine Mobile Accessories',
        ]);
    }

    /** Task 10B: tagline rejects values over 160 characters. */
    public function test_tagline_rejects_values_over_160_characters(): void
    {
        $store = $this->storeWithManager('store-a', '09881110003');

        $response = $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'store_name' => 'Store A',
            'tagline' => str_repeat('y', 161),
            'default_language' => 'my',
        ]);

        $response->assertSessionHasErrors(['tagline']);
        $this->assertDatabaseMissing('storefront_settings', [
            'store_id' => $store['store']->id,
        ]);
    }

    /** Task 10B: tagline persists safely across updates. */
    public function test_tagline_persists_safely_and_can_be_updated(): void
    {
        $store = $this->storeWithManager('store-a', '09881110004');

        $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'store_name' => 'Store A',
            'tagline' => 'First tagline',
            'default_language' => 'my',
        ])->assertSessionHasNoErrors();

        $this->assertSame('First tagline', $store['store']->fresh()->setting->tagline);

        // Update to a new value.
        $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'store_name' => 'Store A',
            'tagline' => 'Second tagline',
            'default_language' => 'my',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Second tagline', $store['store']->fresh()->setting->tagline);

        // An empty tagline is accepted (nullable validation) and clears the stored value.
        $this->actingAs($store['manager'])->post('/store/store-a/admin/settings', [
            'store_name' => 'Store A',
            'tagline' => '',
            'default_language' => 'my',
        ])->assertSessionHasNoErrors();

        $this->assertNull($store['store']->fresh()->setting->tagline);
    }

    /** Task 10B: cannot update another store's setting. */
    public function test_cannot_update_another_store_setting(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::factory()->create(['phone' => '09881110005', 'role' => 'customer']);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        // Manager A is only attached to store A; updating store B must be forbidden.
        $response = $this->actingAs($managerA)->post('/store/store-b/admin/settings', [
            'store_name' => 'Hacked',
            'tagline' => 'Stolen tagline',
            'default_language' => 'my',
        ]);

        $response->assertStatus(403);
        $this->assertNull($storeB->fresh()->setting);
    }

    /**
     * Helper: create a store with a single attached manager.
     *
     * @return array{store: Store, manager: User}
     */
    private function storeWithManager(string $slug, string $phone): array
    {
        $store = Store::create(['name' => 'Store A', 'slug' => $slug]);
        $manager = User::factory()->create(['phone' => $phone, 'role' => 'customer']);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return ['store' => $store, 'manager' => $manager];
    }
}
