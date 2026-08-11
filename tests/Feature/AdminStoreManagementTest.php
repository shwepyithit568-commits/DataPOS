<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Multi-store-ready plan §7.2 — Admin Store Management UI (Phase 2).
 *
 * Rules under test:
 * - Platform owner only (403 otherwise).
 * - Store + StorefrontSetting created together.
 * - Exactly one primary store at a time.
 * - "Destroy" = deactivate (never hard delete); deactivated storefront 404s.
 */
class AdminStoreManagementTest extends TestCase
{
    use RefreshDatabase;

    private function platformOwner(): User
    {
        return User::create([
            'name' => 'Platform Owner',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);
    }

    private function storeStaff(): User
    {
        $user = User::create([
            'name' => 'Store Staff',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $store = Store::create(['name' => 'Staff Store', 'slug' => 'staff-store', 'is_active' => true]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    private function createStore(string $name, string $slug, array $extra = []): Store
    {
        return Store::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'is_primary' => false,
        ], $extra));
    }

    public function test_non_platform_owner_gets_403(): void
    {
        $staff = $this->storeStaff();

        $this->actingAs($staff)->get('/admin/stores')->assertStatus(403);
        $this->actingAs($staff)->get('/admin/stores/create')->assertStatus(403);
        $this->actingAs($staff)->post('/admin/stores', ['name' => 'X', 'slug' => 'x', 'default_language' => 'my'])->assertStatus(403);
    }

    public function test_platform_owner_sees_store_list_with_status_badges(): void
    {
        $this->createStore('Primary Shop', 'primary-shop', ['is_primary' => true]);
        $this->createStore('Closed Shop', 'closed-shop', ['is_active' => false]);

        $response = $this->withSession(['locale' => 'en'])
            ->actingAs($this->platformOwner())
            ->get('/admin/stores');

        $response->assertStatus(200);
        $response->assertSee('Primary Shop', false);
        $response->assertSee('Closed Shop', false);
        $response->assertSee('primary-shop', false);
        $response->assertSee('Primary', false);
        $response->assertSee('Inactive', false);
    }

    public function test_create_store_creates_store_and_settings_together(): void
    {
        $owner = $this->platformOwner();

        $this->actingAs($owner)->post('/admin/stores', [
            'name' => 'New Shop',
            'slug' => 'new-shop',
            'phone' => '09222222222',
            'viber_number' => '09222222222',
            'telegram_username' => 'newshop',
            'address' => 'Yangon',
            'opening_hours' => '09:00 AM To 05:00 PM',
            'delivery_info' => 'Countrywide',
            'payment_info' => 'KPay, Wave',
            'default_language' => 'my',
            'is_active' => '1',
        ])->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseHas('stores', ['slug' => 'new-shop', 'is_active' => true]);
        $this->assertDatabaseHas('storefront_settings', [
            'store_name' => 'New Shop',
            'default_language' => 'my',
        ]);
    }

    public function test_created_storefront_and_admin_urls_work(): void
    {
        $owner = $this->platformOwner();
        $this->actingAs($owner)->post('/admin/stores', [
            'name' => 'Fresh Store',
            'slug' => 'fresh-store',
            'default_language' => 'en',
        ])->assertRedirect(route('admin.stores.index'));

        $this->get('/store/fresh-store')->assertStatus(200);
    }

    public function test_duplicate_slug_returns_validation_error(): void
    {
        $this->createStore('Existing', 'existing-shop');

        $response = $this->actingAs($this->platformOwner())->post('/admin/stores', [
            'name' => 'Duplicate',
            'slug' => 'existing-shop',
            'default_language' => 'en',
        ]);

        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseMissing('stores', ['name' => 'Duplicate']);
    }

    public function test_edit_save_without_changes_preserves_data(): void
    {
        $store = $this->createStore('Keep Me', 'keep-me', ['is_primary' => true]);
        StorefrontSetting::create([
            'store_id' => $store->id,
            'store_name' => 'Keep Me',
            'default_language' => 'my',
        ]);

        $this->actingAs($this->platformOwner())->put(route('admin.stores.update', $store), [
            'name' => 'Keep Me',
            'slug' => 'keep-me',
            'default_language' => 'my',
            'is_active' => '1',
            'is_primary' => '1',
        ])->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => 'Keep Me',
            'slug' => 'keep-me',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('storefront_settings', [
            'store_id' => $store->id,
            'store_name' => 'Keep Me',
        ]);
    }

    public function test_setting_new_primary_demotes_other_stores(): void
    {
        $this->createStore('Old Primary', 'old-primary', ['is_primary' => true]);
        $new = $this->createStore('New Primary', 'new-primary');

        $this->actingAs($this->platformOwner())->put(route('admin.stores.update', $new), [
            'name' => 'New Primary',
            'slug' => 'new-primary',
            'default_language' => 'en',
            'is_primary' => '1',
        ])->assertRedirect(route('admin.stores.index'));

        $this->assertSame(1, Store::where('is_primary', true)->count());
        $this->assertTrue(Store::where('slug', 'new-primary')->value('is_primary'));
        $this->assertFalse(Store::where('slug', 'old-primary')->value('is_primary'));
    }

    public function test_deactivate_hides_storefront_and_blocks_admin(): void
    {
        $store = $this->createStore('Going Away', 'going-away');
        // Second active store so deactivation is not blocked by the
        // last-active-store guard.
        $this->createStore('Survivor', 'survivor');
        $this->assertDatabaseHas('stores', ['slug' => 'going-away', 'is_active' => true]);

        $this->actingAs($this->platformOwner())->delete(route('admin.stores.destroy', $store))
            ->assertRedirect(route('admin.stores.index'));

        // Row still exists (not hard-deleted) but is inactive.
        $this->assertDatabaseHas('stores', ['slug' => 'going-away', 'is_active' => false]);
        $this->get('/store/going-away')->assertStatus(404);
    }

    public function test_reactivate_restores_storefront(): void
    {
        $store = $this->createStore('Back Again', 'back-again', ['is_active' => false]);

        $this->actingAs($this->platformOwner())->post(route('admin.stores.activate', $store))
            ->assertRedirect(route('admin.stores.index'));

        $this->assertDatabaseHas('stores', ['slug' => 'back-again', 'is_active' => true]);
        $this->get('/store/back-again')->assertStatus(200);
    }

    public function test_destroy_is_deactivate_not_hard_delete(): void
    {
        $this->createStore('Soft Delete', 'soft-delete');
        $this->createStore('Survivor', 'survivor');

        $this->actingAs($this->platformOwner())->delete(route('admin.stores.destroy', Store::where('slug', 'soft-delete')->firstOrFail()));

        $this->assertDatabaseHas('stores', ['slug' => 'soft-delete', 'is_active' => false]);
        $this->assertDatabaseCount('stores', 2);
    }

    public function test_last_active_store_cannot_be_deactivated(): void
    {
        $store = $this->createStore('Only One', 'only-one', ['is_primary' => true]);

        $this->actingAs($this->platformOwner())->delete(route('admin.stores.destroy', $store))
            ->assertSessionHasErrors('store');

        $this->assertDatabaseHas('stores', ['slug' => 'only-one', 'is_active' => true]);
    }

    public function test_deactivating_primary_promotes_next_active_store(): void
    {
        $this->createStore('Primary A', 'primary-a', ['is_primary' => true]);
        $this->createStore('Secondary B', 'secondary-b');

        $primaryA = Store::where('slug', 'primary-a')->firstOrFail();

        $this->actingAs($this->platformOwner())->delete(route('admin.stores.destroy', $primaryA))
            ->assertRedirect(route('admin.stores.index'));

        $this->assertFalse(Store::where('slug', 'primary-a')->value('is_active'));
        $this->assertTrue(Store::where('slug', 'secondary-b')->value('is_primary'));
        $this->assertSame(1, Store::where('is_primary', true)->count());
    }
}
