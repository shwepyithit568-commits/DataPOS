<?php

namespace Tests\Feature\Storefront;

use App\BusinessProfiles\BusinessProfile;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\ViewModels\Storefront\ProductCardViewModel;
use App\ViewModels\Storefront\StoreHeaderViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontDecouplingTest extends TestCase
{
    use RefreshDatabase;

    protected function createStoreWithProfile(string $profile, string $operationMode = BusinessProfile::MODE_OMNICHANNEL, array $attributes = []): Store
    {
        $store = Store::create(array_merge([
            'name'             => 'Store ' . ucfirst($profile),
            'slug'             => $profile . '-' . uniqid(),
            'business_profile' => $profile,
            'operation_mode'   => $operationMode,
            'is_active'        => true,
        ], $attributes));

        $store->setting()->create([
            'store_name'       => $store->name,
            'tagline'          => 'Quality Products and Services',
            'phone'            => '09123456789',
            'default_language' => 'en',
        ]);

        return $store;
    }

    public function test_mobile_electronics_storefront_renders_glass_finder_and_repair_shortcuts(): void
    {
        $store = $this->createStoreWithProfile(BusinessProfile::MOBILE_ELECTRONICS);

        $response = $this->get('/store/' . $store->slug);

        $response->assertStatus(200);
        $response->assertSee(url('/glass-finder?store_slug=' . $store->slug));
        $response->assertSee(url('/service-tracking?store_slug=' . $store->slug));
    }

    public function test_general_retail_storefront_omits_mobile_specific_widgets(): void
    {
        $store = $this->createStoreWithProfile(BusinessProfile::GENERAL_RETAIL);

        $response = $this->get('/store/' . $store->slug);

        $response->assertStatus(200);
        $response->assertDontSee(url('/glass-finder?store_slug=' . $store->slug));
        $response->assertDontSee(url('/service-tracking?store_slug=' . $store->slug));
    }

    public function test_pharmacy_storefront_omits_mobile_specific_widgets(): void
    {
        $store = $this->createStoreWithProfile(BusinessProfile::PHARMACY);

        $response = $this->get('/store/' . $store->slug);

        $response->assertStatus(200);
        $response->assertDontSee(url('/glass-finder?store_slug=' . $store->slug));
        $response->assertDontSee(url('/service-tracking?store_slug=' . $store->slug));
    }

    public function test_pos_only_store_shows_in_store_counter_landing_to_guests(): void
    {
        $store = $this->createStoreWithProfile(
            BusinessProfile::GENERAL_RETAIL,
            BusinessProfile::MODE_POS_ONLY
        );

        $response = $this->get('/store/' . $store->slug);

        $response->assertStatus(200);
        $response->assertSee('POS Only');
        $response->assertSee('In-Store');
    }

    public function test_pos_only_store_redirects_staff_to_pos_directly(): void
    {
        $store = $this->createStoreWithProfile(
            BusinessProfile::GENERAL_RETAIL,
            BusinessProfile::MODE_POS_ONLY
        );

        $cashier = User::factory()->create();
        $store->users()->attach($cashier->id, ['role' => 'cashier', 'status' => 'active']);

        $response = $this->actingAs($cashier)->get('/store/' . $store->slug);

        $response->assertRedirect(route('pos.index', ['store_slug' => $store->slug]));
    }

    public function test_product_card_view_model_computes_prices_and_discounts(): void
    {
        $store = $this->createStoreWithProfile(BusinessProfile::GENERAL_RETAIL);
        $category = Category::create(['store_id' => $store->id, 'name' => 'Snacks', 'slug' => 'snacks']);
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'BrandX', 'slug' => 'brand-x']);

        $product = Product::create([
            'store_id'        => $store->id,
            'category_id'     => $category->id,
            'brand_id'        => $brand->id,
            'name'            => 'Crispy Chips',
            'slug'            => 'crispy-chips',
            'sku'             => 'CRISP-001',
            'retail_price'    => 1500,
            'old_price'       => 2000,
            'wholesale_price' => 1200,
            'stock_status'    => 'in_stock',
            'is_ecommerce'    => true,
        ]);

        // Regular shopper
        $vm = new ProductCardViewModel($product, $store, isWholesaleApproved: false);
        $this->assertEquals(1500.0, $vm->price());
        $this->assertEquals('Ks 1,500', $vm->formattedPrice());
        $this->assertEquals(2000.0, $vm->oldPrice());
        $this->assertEquals('Ks 2,000', $vm->formattedOldPrice());
        $this->assertEquals(25, $vm->discountPercentage()); // 25% discount
        $this->assertFalse($vm->isOutOfStock());

        // Wholesale customer
        $vmWholesale = new ProductCardViewModel($product, $store, isWholesaleApproved: true);
        $this->assertEquals(1200.0, $vmWholesale->price());
        $this->assertEquals('Ks 1,200', $vmWholesale->formattedPrice());
        $this->assertNull($vmWholesale->oldPrice());
    }

    public function test_store_header_view_model_provides_metadata(): void
    {
        $store = $this->createStoreWithProfile(BusinessProfile::MOBILE_ELECTRONICS);
        $vm = new StoreHeaderViewModel($store, $store->setting);

        $this->assertEquals($store->name, $vm->storeName());
        $this->assertEquals('Quality Products and Services', $vm->tagline());
        $this->assertTrue($vm->can('storefront.glass_finder'));
        $this->assertFalse($vm->isPosOnly());
    }
}
