<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\StorefrontNavigationItem;
use App\Models\StorefrontPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontNavigationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name'      => 'Test Nav Store',
            'slug'      => 'test-nav-store',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_admin_can_view_navigation_management_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('store.admin.navigation.index', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $response->assertSee(__('messages.storefront_navigation'));
        $response->assertSee(__('messages.desktop_tabs'));
        $response->assertSee(__('messages.mobile_bottom'));
    }

    public function test_admin_can_create_system_navigation_item(): void
    {
        $payload = [
            'label_my'            => 'ဆားဗစ်',
            'label_en'            => 'Service',
            'label_zh_cn'         => '维修',
            'icon_key'            => 'repair',
            'destination_type'    => 'system',
            'destination_key'     => 'service_tracking',
            'show_desktop'        => 1,
            'show_mobile_drawer'  => 1,
            'show_mobile_bottom'  => 1,
            'requires_auth'       => 0,
            'is_enabled'          => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('store.admin.navigation.store', ['store_slug' => $this->store->slug]), $payload);

        $response->assertRedirect(route('store.admin.navigation.index', ['store_slug' => $this->store->slug]));

        $this->assertDatabaseHas('storefront_navigation_items', [
            'store_id'         => $this->store->id,
            'label_en'         => 'Service',
            'destination_key'  => 'service_tracking',
            'show_desktop'     => true,
            'show_mobile_bottom'=> true,
        ]);
    }

    public function test_admin_can_create_custom_url_navigation_item(): void
    {
        $payload = [
            'label_my'            => 'ပရိုမိုးရှင်း',
            'label_en'            => 'Special Offer',
            'label_zh_cn'         => '特惠活动',
            'icon_key'            => 'gift',
            'destination_type'    => 'custom_url',
            'custom_url'          => 'https://example.com/promo',
            'show_desktop'        => 1,
            'show_mobile_drawer'  => 1,
            'show_mobile_bottom'  => 0,
            'requires_auth'       => 0,
            'is_enabled'          => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('store.admin.navigation.store', ['store_slug' => $this->store->slug]), $payload);

        $response->assertRedirect(route('store.admin.navigation.index', ['store_slug' => $this->store->slug]));

        $this->assertDatabaseHas('storefront_navigation_items', [
            'store_id'         => $this->store->id,
            'label_en'         => 'Special Offer',
            'destination_type' => 'custom_url',
            'custom_url'       => 'https://example.com/promo',
        ]);
    }

    public function test_admin_can_reorder_navigation_items(): void
    {
        $item1 = StorefrontNavigationItem::create([
            'store_id'         => $this->store->id,
            'menu_key'         => 'item_1',
            'label_my'         => 'မီနူး ၁',
            'label_en'         => 'Item 1',
            'icon_key'         => 'home',
            'destination_type' => 'system',
            'destination_key'  => 'home',
            'sort_order'       => 10,
        ]);

        $item2 = StorefrontNavigationItem::create([
            'store_id'         => $this->store->id,
            'menu_key'         => 'item_2',
            'label_my'         => 'မီနူး ၂',
            'label_en'         => 'Item 2',
            'icon_key'         => 'products',
            'destination_type' => 'system',
            'destination_key'  => 'products',
            'sort_order'       => 20,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('store.admin.navigation.reorder', [
                'store_slug' => $this->store->slug,
                'id'         => $item2->id,
                'direction'  => 'up',
            ]));

        $response->assertRedirect(route('store.admin.navigation.index', ['store_slug' => $this->store->slug]));

        $item1->refresh();
        $item2->refresh();

        $this->assertTrue($item2->sort_order <= $item1->sort_order);
    }

    public function test_admin_can_toggle_navigation_item_status(): void
    {
        $item = StorefrontNavigationItem::create([
            'store_id'         => $this->store->id,
            'menu_key'         => 'toggle_item',
            'label_my'         => 'စမ်းသပ်',
            'label_en'         => 'Test Toggle',
            'icon_key'         => 'home',
            'destination_type' => 'system',
            'destination_key'  => 'home',
            'is_enabled'       => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('store.admin.navigation.toggle', [
                'store_slug' => $this->store->slug,
                'id'         => $item->id,
            ]));

        $response->assertRedirect();
        $this->assertFalse((bool) $item->fresh()->is_enabled);
    }

    public function test_admin_can_reset_to_defaults(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('store.admin.navigation.reset_defaults', ['store_slug' => $this->store->slug]));

        $response->assertRedirect(route('store.admin.navigation.index', ['store_slug' => $this->store->slug]));
        $this->assertTrue(StorefrontNavigationItem::where('store_id', $this->store->id)->exists());
    }

    public function test_admin_can_export_navigation_items_to_excel_and_csv(): void
    {
        $responseXlsx = $this->actingAs($this->adminUser)
            ->get(route('store.admin.navigation.export', ['store_slug' => $this->store->slug, 'format' => 'xlsx']));
        $responseXlsx->assertOk();

        $responseCsv = $this->actingAs($this->adminUser)
            ->get(route('store.admin.navigation.export', ['store_slug' => $this->store->slug, 'format' => 'csv']));
        $responseCsv->assertOk();
    }
}
