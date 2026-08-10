<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\GlassFinderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_builder_page_loads_successfully(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);

        $response = $this->get('/order-builder?store_slug=main-store');
        $response->assertStatus(200);
        $response->assertSee('name="contact_identifier"', false);
        $response->assertSee('Telegram Username');
        $response->assertSee("contactChannel: 'phone'", false);
        $response->assertSee("contactChannel === 'viber' || contactChannel === 'telegram'", false);
        $response->assertDontSee('name="contact_identifier" required', false);
        // Heading renders via the order_builder translation key (locale-aware).
        $response->assertSee(__('messages.order_builder'));
    }

    public function test_product_card_and_detail_views_contain_order_builder_action_hooks(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-HOOK-1',
            'name' => "Tester's Special Glass",            'slug' => 'testers-special-glass',
            'retail_price' => 12000,
            'wholesale_price' => 9000,
            'stock_status' => 'in_stock',
        ]);

        $responseCatalog = $this->get('/products?store_slug=main-store');
        $responseCatalog->assertStatus(200);
        $responseCatalog->assertSee('$store.orderBuilder.addItem', false);

        $responseDetail = $this->get('/store/main-store/product/testers-special-glass');
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee('$store.orderBuilder.addItem', false);
    }

    public function test_multi_item_order_builder_submission(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);

        $p1 = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-BUILD-1',
            'name' => 'Tempered Glass 11',
            'slug' => 'tempered-glass-11',
            'retail_price' => 5000,
            'wholesale_price' => 3500,
            'stock_status' => 'in_stock',
        ]);

        $p2 = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-BUILD-2',
            'name' => 'Silicone Case 11',
            'slug' => 'silicone-case-11',
            'retail_price' => 8000,
            'wholesale_price' => 6000,
            'stock_status' => 'in_stock',
        ]);

        $itemsJson = json_encode([
            ['product_id' => $p1->id, 'quantity' => 2],
            ['product_id' => $p2->id, 'quantity' => 1],
        ]);

        $response = $this->post('/store/main-store/orders', [
            'items_json' => $itemsJson,
            'customer_name' => 'Aung Aung',
            'customer_phone' => '09123456789',
            'customer_address' => 'Kyauktada, Yangon',
            'contact_channel' => 'viber',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'customer_name' => 'Aung Aung',
            'customer_phone' => '09123456789',
            'total_amount' => 18000, // (5000 * 2) + (8000 * 1)
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Tempered Glass 11',
            'quantity' => 2,
            'subtotal' => 10000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_name' => 'Silicone Case 11',
            'quantity' => 1,
            'subtotal' => 8000,
        ]);
    }

    public function test_order_builder_uses_selected_variant_price_and_stock(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);

        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'PHONE-BASE',
            'name' => 'iPhone 15 Pro Max',
            'slug' => 'iphone-15-pro-max',
            'retail_price' => 1000000,
            'wholesale_price' => 900000,
            'stock_status' => 'in_stock',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '512GB Black',
            'sku' => 'PHONE-512-BLK',
            'retail_price' => 1500000,
            'wholesale_price' => 1400000,
            'stock_status' => 'in_stock',
            'is_default' => true,
        ]);

        $response = $this->post('/store/main-store/orders', [
            'items_json' => json_encode([
                ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 2],
            ]),
            'customer_name' => 'Variant Buyer',
            'customer_phone' => '09123456789',
            'customer_address' => 'Yangon',
            'contact_channel' => 'phone',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'customer_name' => 'Variant Buyer',
            'total_amount' => 3000000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'iPhone 15 Pro Max - 512GB Black',
            'variant_name' => '512GB Black',
            'variant_sku' => 'PHONE-512-BLK',
            'unit_price' => 1500000,
            'quantity' => 2,
            'subtotal' => 3000000,
        ]);
    }

    public function test_order_builder_rejects_out_of_stock_variant(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);

        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'LAPTOP-BASE',
            'name' => 'Laptop',
            'slug' => 'laptop',
            'retail_price' => 1000000,
            'wholesale_price' => 900000,
            'stock_status' => 'in_stock',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '16GB RAM',
            'sku' => 'LAPTOP-16',
            'retail_price' => 1200000,
            'stock_status' => 'out_of_stock',
        ]);

        $response = $this->from('/order-builder?store_slug=main-store')
            ->post('/store/main-store/orders', [
                'items_json' => json_encode([
                    ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1],
                ]),
                'customer_name' => 'Variant Buyer',
                'customer_phone' => '09123456789',
                'customer_address' => 'Yangon',
                'contact_channel' => 'phone',
            ]);

        $response->assertRedirect('/order-builder?store_slug=main-store');
        $response->assertSessionHasErrors('items_json');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_wholesale_user_gets_wholesale_rates_in_order_builder(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $wholesaleUser = User::create([
            'name' => 'Wholesale Dealer',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $wholesaleUser->stores()->attach($store->id, ['role' => 'wholesale_customer', 'status' => 'active']);

        $p1 = Product::create([
            'store_id' => $store->id,
            'sku' => 'SKU-WS-1',
            'name' => 'Bulk Glass',
            'slug' => 'bulk-glass',
            'retail_price' => 10000,
            'wholesale_price' => 7000,
            'stock_status' => 'in_stock',
        ]);

        $itemsJson = json_encode([
            ['product_id' => $p1->id, 'quantity' => 10],
        ]);

        $response = $this->actingAs($wholesaleUser)->post('/store/main-store/orders', [
            'items_json' => $itemsJson,
            'customer_name' => 'Wholesale Dealer',
            'customer_phone' => '09888888888',
            'customer_address' => 'Mandalay',
            'contact_channel' => 'telegram',
            'contact_identifier' => '@dealer_mm',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'pricing_type' => 'wholesale',
            'contact_identifier' => '@dealer_mm',
            'total_amount' => 70000, // 7000 * 10
        ]);
    }

    public function test_order_builder_accepts_glass_finder_items(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);

        $glassItem = GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone 15',
            'glass_code' => 'IP15',
            'normalized_glass_code' => 'ip15',
            'stock_status' => 'in_stock',
        ]);

        $response = $this->post('/store/main-store/orders', [
            'items_json' => json_encode([
                ['glass_finder_item_id' => $glassItem->id, 'quantity' => 2],
            ]),
            'customer_name' => 'Glass Buyer',
            'customer_phone' => '09123456789',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('orders', [
            'store_id' => $store->id,
            'customer_name' => 'Glass Buyer',
            'total_amount' => 0,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => null,
            'product_name' => 'Glass: iPhone 15 (Code: IP15)',
            'quantity' => 2,
            'subtotal' => 0,
        ]);
    }

    public function test_invalid_order_builder_data_does_not_clear_cart_before_success(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);

        $response = $this->from('/order-builder?store_slug=main-store')
            ->post('/store/main-store/orders', [
                'items_json' => '{bad-json',
                'customer_name' => 'Invalid Cart',
                'customer_phone' => '09123456789',
                'customer_address' => 'Yangon',
                'contact_channel' => 'viber',
            ]);

        $response->assertRedirect('/order-builder?store_slug=main-store');
        $response->assertSessionHasErrors('items_json');
        $this->assertDatabaseCount('orders', 0);

        $builderResponse = $this->get('/order-builder?store_slug=main-store');
        $builderResponse->assertStatus(200);
        $builderResponse->assertDontSee('$store.orderBuilder.clear()', false);
    }

    public function test_order_builder_shows_delivery_and_payment_info_when_configured(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $store->setting()->create([
            'store_name' => 'Main Store',
            'delivery_info' => "Yangon: 2000 Ks (1-2 days)\nOther states: 4000 Ks (2-4 days)",
            'payment_info' => 'KPay: 09999999999 | Wave: 09999999999 | MMQR',
        ]);

        $response = $this->get('/order-builder?store_slug=main-store');

        $response->assertStatus(200);
        $response->assertSee(__('messages.delivery'));
        $response->assertSee('Yangon: 2000 Ks (1-2 days)');
        $response->assertSee(__('messages.payment'));
        $response->assertSee('KPay: 09999999999 | Wave: 09999999999 | MMQR');
    }
}
