<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Coupons on the online storefront checkout.
 *
 * The POS has carried `promotion_id`/`coupon_code` from the start, but an order
 * placed online had no column for a discount at all — a coupon typed at checkout
 * had nowhere to land, so the storefront could not honour a promotion it was
 * advertising.
 */
class StorefrontOrderCouponTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Product $product;

    private Product $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Web Shop',
            'slug' => 'web-shop',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Glass Protector',
            'slug' => 'glass-' . Str::random(5),
            'sku' => 'WS-' . Str::random(5),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
            'is_ecommerce' => true,
        ]);

        $this->other = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Phone Case',
            'slug' => 'case-' . Str::random(5),
            'sku' => 'WS2-' . Str::random(5),
            'retail_price' => 6000,
            'wholesale_price' => 5000,
            'is_ecommerce' => true,
        ]);
    }

    private function promotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'store_id' => $this->store->id,
            'name' => 'Web 10%',
            'code' => 'WEB10',
            'type' => 'percent_off',
            'value' => 10,
            'is_active' => true,
            'is_public' => true,
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function payload(string $coupon = null, int $quantity = 2): array
    {
        return array_filter([
            'items_json' => json_encode([[
                'product_id' => $this->product->id,
                'quantity' => $quantity,
                'price' => 10000,
            ]]),
            'customer_name' => 'Ko Web',
            'customer_phone' => '09971112223',
            'customer_address' => 'Yangon',
            'contact_channel' => 'phone',
            'coupon_code' => $coupon,
        ], fn ($v) => $v !== null);
    }

    public function test_a_coupon_discounts_an_online_order_and_is_recorded(): void
    {
        $promotion = $this->promotion();

        $response = $this->post("/store/{$this->store->slug}/orders", $this->payload('web10'));

        $response->assertRedirect();

        $order = Order::where('store_id', $this->store->id)->latest('id')->firstOrFail();

        // 2 × 10,000 = 20,000 bill, 10% off → 2,000 discount, 18,000 charged.
        $this->assertSame('18000.00', (string) $order->total_amount);
        $this->assertSame('2000.00', (string) $order->discount_amount);
        $this->assertSame('WEB10', $order->coupon_code);
        $this->assertSame($promotion->id, (int) $order->promotion_id);

        $usage = PromotionUsage::where('order_id', $order->id)->sole();
        $this->assertSame('2000.00', (string) $usage->discount_applied);
        $this->assertNull($usage->pos_sale_id);

        $this->assertSame(1, (int) $promotion->fresh()->used_count);
    }

    public function test_the_order_total_still_matches_subtotal_without_a_coupon(): void
    {
        $this->post("/store/{$this->store->slug}/orders", $this->payload())->assertRedirect();

        $order = Order::where('store_id', $this->store->id)->latest('id')->firstOrFail();

        $this->assertSame('20000.00', (string) $order->total_amount);
        $this->assertSame('0.00', (string) $order->discount_amount);
        $this->assertNull($order->coupon_code);
        $this->assertSame(0, PromotionUsage::count());
    }

    public function test_an_unknown_code_is_refused_and_no_order_is_created(): void
    {
        $response = $this->post("/store/{$this->store->slug}/orders", $this->payload('NOPE'));

        $response->assertSessionHasErrors('coupon_code');
        $this->assertSame(0, Order::count());
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->promotion(['expires_at' => now()->subDay()]);

        $this->post("/store/{$this->store->slug}/orders", $this->payload('WEB10'))
            ->assertSessionHasErrors('coupon_code');

        $this->assertSame(0, Order::count());
    }

    public function test_a_scoped_coupon_prices_only_its_own_product(): void
    {
        $this->promotion(['product_id' => $this->other->id]);

        // Only the glass is ordered; the promotion covers the case.
        $this->post("/store/{$this->store->slug}/orders", $this->payload('WEB10'))
            ->assertSessionHasErrors('coupon_code');

        $this->assertSame(0, Order::count());
    }

    public function test_a_scoped_coupon_discounts_only_the_matching_lines(): void
    {
        $this->promotion(['product_id' => $this->other->id]);

        $payload = $this->payload('WEB10');
        $payload['items_json'] = json_encode([
            ['product_id' => $this->product->id, 'quantity' => 1, 'price' => 10000],
            ['product_id' => $this->other->id, 'quantity' => 2, 'price' => 6000],
        ]);

        $this->post("/store/{$this->store->slug}/orders", $payload)->assertRedirect();

        $order = Order::where('store_id', $this->store->id)->latest('id')->firstOrFail();

        // 10,000 + 12,000 = 22,000 bill; 10% of the 12,000 that matches = 1,200.
        $this->assertSame('1200.00', (string) $order->discount_amount);
        $this->assertSame('20800.00', (string) $order->total_amount);
    }

    public function test_a_bogo_coupon_frees_every_second_unit_online_too(): void
    {
        $this->promotion(['type' => 'bogo', 'value' => 1]);

        $payload = $this->payload('WEB10', 3);

        $this->post("/store/{$this->store->slug}/orders", $payload)->assertRedirect();

        $order = Order::where('store_id', $this->store->id)->latest('id')->firstOrFail();

        // 3 × 10,000 = 30,000 bill, one unit free → 10,000 off.
        $this->assertSame('10000.00', (string) $order->discount_amount);
        $this->assertSame('20000.00', (string) $order->total_amount);
    }

    public function test_the_preview_endpoint_prices_a_coupon_without_creating_anything(): void
    {
        $this->promotion();

        $response = $this->postJson("/store/{$this->store->slug}/orders/coupon", [
            'code' => 'web10',
            'items_json' => json_encode([['product_id' => $this->product->id, 'quantity' => 3, 'price' => 10000]]),
        ]);

        $response->assertOk()
            ->assertJson(['valid' => true, 'discount' => '3000.00', 'code' => 'WEB10']);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, PromotionUsage::count());
        $this->assertSame(0, (int) $this->promotion()->fresh()->used_count);
    }

    public function test_the_preview_endpoint_reports_why_a_coupon_is_refused(): void
    {
        $this->postJson("/store/{$this->store->slug}/orders/coupon", [
            'code' => 'NOPE',
            'items_json' => json_encode([['product_id' => $this->product->id, 'quantity' => 1, 'price' => 10000]]),
        ])->assertOk()->assertJson(['valid' => false]);

        $this->assertSame(0, Order::count());
    }

    public function test_a_per_customer_limit_is_enforced_for_the_same_account(): void
    {
        $this->promotion(['per_customer_limit' => 1]);

        $user = User::create([
            'name' => 'Web Customer',
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($user)->post("/store/{$this->store->slug}/orders", $this->payload('WEB10'))->assertRedirect();

        $this->assertSame(1, PromotionUsage::count());

        // Second attempt with the same account is refused.
        $this->actingAs($user)->post("/store/{$this->store->slug}/orders", $this->payload('WEB10'))
            ->assertSessionHasErrors('coupon_code');

        $this->assertSame(1, Order::count());
    }
}
