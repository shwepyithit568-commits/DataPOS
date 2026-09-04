<?php

namespace Tests\Feature\Storefront;

use App\Models\Store;
use App\Models\StorefrontNavigationItem;
use App\Models\StorefrontPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicNavigationRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::create([
            'name'      => 'Nav Render Store',
            'slug'      => 'nav-render-store',
            'is_active' => true,
        ]);
    }

    public function test_storefront_renders_custom_desktop_navigation_items(): void
    {
        StorefrontNavigationItem::create([
            'store_id'         => $this->store->id,
            'menu_key'         => 'custom_promo',
            'label_my'         => 'အထူးလျှော့ဈေး',
            'label_en'         => 'Flash Sale',
            'icon_key'         => 'gift',
            'destination_type' => 'custom_url',
            'custom_url'       => 'https://example.com/sale',
            'show_desktop'     => true,
            'show_mobile_drawer'=> true,
            'show_mobile_bottom'=> false,
            'is_enabled'       => true,
            'sort_order'       => 1,
        ]);

        $response = $this->get('/store/' . $this->store->slug);
        $response->assertOk();
        $response->assertSee('အထူးလျှော့ဈေး');
    }

    public function test_storefront_renders_custom_page_navigation_item_only_when_published(): void
    {
        $page = StorefrontPage::create([
            'store_id' => $this->store->id,
            'title_my' => 'အမေးများသောမေးခွန်းများ',
            'title_en' => 'FAQ Guide',
            'slug'     => 'faq-guide',
            'status'   => 'draft', // Draft page
            'is_enabled' => true,
        ]);

        StorefrontNavigationItem::create([
            'store_id'           => $this->store->id,
            'menu_key'           => 'faq_menu',
            'label_my'           => 'အမေးများသောမေးခွန်းများ',
            'label_en'           => 'FAQ',
            'icon_key'           => 'info',
            'destination_type'   => 'page',
            'storefront_page_id' => $page->id,
            'show_desktop'       => true,
            'is_enabled'         => true,
        ]);

        // When page is draft, it shouldn't appear
        $response = $this->get('/store/' . $this->store->slug);
        $response->assertDontSee('/page/faq-guide');

        // Publish page
        $page->update(['status' => 'published']);

        $responsePublished = $this->get('/store/' . $this->store->slug);
        $responsePublished->assertSee('/page/faq-guide');
    }

    public function test_auth_restricted_navigation_item_hidden_for_guests(): void
    {
        StorefrontNavigationItem::create([
            'store_id'         => $this->store->id,
            'menu_key'         => 'vip_lounge',
            'label_my'         => 'VIP အဖွဲ့ဝင်',
            'label_en'         => 'VIP Lounge',
            'icon_key'         => 'star',
            'destination_type' => 'custom_url',
            'custom_url'       => '/vip',
            'show_desktop'     => true,
            'requires_auth'    => true,
            'is_enabled'       => true,
        ]);

        $guestResponse = $this->get('/store/' . $this->store->slug);
        $guestResponse->assertDontSee('VIP အဖွဲ့ဝင်');

        $customer = User::factory()->create();
        $authResponse = $this->actingAs($customer)->get('/store/' . $this->store->slug);
        $authResponse->assertSee('VIP အဖွဲ့ဝင်');
    }
}
