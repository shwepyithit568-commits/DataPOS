<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\StorefrontNavigationItem;
use App\Models\StorefrontPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontPagesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name'      => 'Test Pages Store',
            'slug'      => 'test-pages-store',
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_admin_can_view_pages_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.pages.index', ['store_slug' => $this->store->slug]));

        $response->assertOk();
        $response->assertSee(__('messages.custom_pages'));
    }

    public function test_admin_can_create_custom_page(): void
    {
        $payload = [
            'title_my'   => 'ကုမ္ပဏီအကြောင်း',
            'title_en'   => 'About Us',
            'title_zh_cn'=> '关于我们',
            'slug'       => 'about-us',
            'summary_en' => 'Leading retailer in Yangon',
            'content_en' => '# About Us\n\nWe provide top quality tech services.',
            'status'     => 'published',
            'is_enabled' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.pages.store', ['store_slug' => $this->store->slug]), $payload);

        $response->assertRedirect(route('admin.pages.index', ['store_slug' => $this->store->slug]));

        $this->assertDatabaseHas('storefront_pages', [
            'store_id' => $this->store->id,
            'slug'     => 'about-us',
            'title_en' => 'About Us',
            'status'   => 'published',
        ]);
    }

    public function test_reserved_slugs_are_rejected(): void
    {
        $payload = [
            'title_my'   => 'အိမ်',
            'title_en'   => 'Home Page',
            'slug'       => 'cart', // reserved slug
            'status'     => 'published',
            'is_enabled' => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.pages.store', ['store_slug' => $this->store->slug]), $payload);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_page_cannot_be_deleted_if_linked_in_navigation(): void
    {
        $page = StorefrontPage::create([
            'store_id' => $this->store->id,
            'title_my' => 'စည်းကမ်းချက်များ',
            'title_en' => 'Terms of Service',
            'slug'     => 'terms-conditions',
            'status'   => 'published',
        ]);

        StorefrontNavigationItem::create([
            'store_id'           => $this->store->id,
            'menu_key'           => 'terms_menu',
            'label_my'           => 'စည်းကမ်းချက်များ',
            'label_en'           => 'Terms',
            'icon_key'           => 'document',
            'destination_type'   => 'page',
            'storefront_page_id' => $page->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.pages.destroy', ['store_slug' => $this->store->slug, 'id' => $page->id]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('storefront_pages', ['id' => $page->id]);
    }

    public function test_unlinked_page_can_be_deleted(): void
    {
        $page = StorefrontPage::create([
            'store_id' => $this->store->id,
            'title_my' => 'ယာယီ',
            'title_en' => 'Temporary Page',
            'slug'     => 'temp-page',
            'status'   => 'draft',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.pages.destroy', ['store_slug' => $this->store->slug, 'id' => $page->id]));

        $response->assertRedirect(route('admin.pages.index', ['store_slug' => $this->store->slug]));
        $this->assertDatabaseMissing('storefront_pages', ['id' => $page->id]);
    }
}
