<?php

namespace Tests\Feature\POS;

use App\Models\LoyaltyPointTransaction;
use App\Models\MembershipTier;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\MembershipLoyaltyService;
use App\POS\Services\PosReturnService;
use App\POS\Services\PosSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Coupons redeemable at the counter, and points earned from sales.
 *
 * Both features shipped half-built: the promotions admin could create coupons
 * and validate them, and members could be given points by hand, but nothing
 * wrote a redemption (`promotion_usages` / `used_count` were read-only) and
 * nothing credited points for a purchase (`loyalty_points` and `total_spent`
 * stayed 0, so spend-based tiers were unreachable).
 */
class PosCouponAndLoyaltyTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $cashier;

    private User $customer;

    private Product $product;

    private PosSaleService $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Coupon Shop',
            'slug' => 'coupon-shop',
            'is_active' => true,
        ]);

        // The mobile profile carries the loyalty capability by default; assert it
        // so these tests keep testing earning rather than capability plumbing.
        $this->assertTrue($this->store->hasCapability(\App\Capabilities\Capability::COMMERCE_LOYALTY));

        $this->cashier = User::create([
            'name' => 'Cashier',
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->cashier->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->customer = User::create([
            'name' => 'Daw Loyal',
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $this->customer->stores()->attach($this->store->id, [
            'role' => 'retail_customer',
            'status' => 'active',
            'loyalty_points' => 0,
            'total_spent' => 0,
        ]);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Glass Protector',
            'slug' => 'glass-' . Str::random(5),
            'sku' => 'CP-' . Str::random(5),
            'retail_price' => 10000,
            'wholesale_price' => 9000,
        ]);

        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '50',
            'unit_cost' => 4000,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
        ]);

        app(MembershipLoyaltyService::class)->ensureDefaultTiers($this->store);

        $this->sales = app(PosSaleService::class);
    }

    private function price(Store $store, array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'store_id' => $store->id,
            'name' => 'UAT 10% off',
            'code' => 'UAT10',
            'type' => 'percent_off',
            'value' => 10,
            'is_active' => true,
            'is_public' => true,
        ], $overrides));
    }

    private function setLoyaltyRate(string $rate): void
    {
        // store_name is NOT NULL on storefront_settings — a store always has one.
        $setting = StorefrontSetting::firstOrNew(
            ['store_id' => $this->store->id],
            ['store_name' => $this->store->name],
        );
        $settings = $setting->pos_settings ?? [];
        $settings['loyalty_amount_per_point'] = (float) $rate;
        $setting->pos_settings = $settings;
        $setting->save();

        $this->store->refresh()->load('setting');
    }

    /** What one point is worth when the customer spends it. */
    private function setPointValue(string $value): void
    {
        $setting = StorefrontSetting::firstOrNew(
            ['store_id' => $this->store->id],
            ['store_name' => $this->store->name],
        );
        $settings = $setting->pos_settings ?? [];
        $settings['loyalty_point_value'] = (float) $value;
        $setting->pos_settings = $settings;
        $setting->save();

        $this->store->refresh()->load('setting');
    }

    /** Put points in the customer's wallet for a redemption test. */
    private function givePoints(int $points): void
    {
        DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->update(['loyalty_points' => $points]);
    }

    /** Post a one-line cash sale and return it. */
    private function sell(string $quantity = '1', ?string $coupon = null, bool $withCustomer = true)
    {
        $shift = app(CashierShiftService::class)->openShift($this->store, [
            'register_name' => 'Counter',
            'opening_cash' => '0',
        ], $this->cashier);

        $subtotal = bcmul('10000', $quantity, 2);

        return $this->sales->post(
            store: $this->store->fresh()->load('setting'),
            lines: [[
                'product_id' => $this->product->id,
                'product_variant_id' => null,
                'quantity' => $quantity,
            ]],
            payments: [['method' => 'cash', 'amount' => $subtotal]],
            actor: $this->cashier,
            shift: $shift,
            customerId: $withCustomer ? $this->customer->id : null,
            couponCode: $coupon,
        );
    }

    // ── Coupons ───────────────────────────────────────────────────────────────

    // ── Spending points ───────────────────────────────────────────────────────

    public function test_points_can_be_spent_on_a_sale_and_the_balance_drops(): void
    {
        $this->setLoyaltyRate('1000'); // earn 1 point per 1,000 Ks
        $this->setPointValue('10');    // spend 1 point for 10 Ks
        $this->givePoints(100);        // worth 1,000 Ks

        $this->sales->setPoints($this->store->fresh()->load('setting'), 50);

        $sale = $this->sell('1'); // 10,000 bill

        // 50 points × 10 Ks = 500 off.
        $this->assertSame('500.00', $sale->discount);
        $this->assertSame('9500.00', $sale->total);

        $spent = LoyaltyPointTransaction::where('pos_sale_id', $sale->id)
            ->where('type', MembershipLoyaltyService::TYPE_REDEEMED)
            ->sole();

        $this->assertSame(-50, $spent->points);

        // 100 given − 50 spent + 9 earned on the 9,500 actually paid = 59
        $pivot = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->first();

        $this->assertSame(59, (int) $pivot->loyalty_points);
    }

    public function test_points_cannot_be_spent_beyond_the_customers_balance(): void
    {
        $this->setPointValue('10');
        $this->givePoints(10); // only 100 Ks worth

        $this->sales->setPoints($this->store->fresh()->load('setting'), 500);

        $sale = $this->sell('1');

        // Clamped to the balance: 10 points = 100 Ks, not 5,000.
        $this->assertSame('100.00', $sale->discount);
    }

    public function test_points_never_discount_more_than_the_bill(): void
    {
        $this->setPointValue('10');
        $this->givePoints(5000); // 50,000 Ks worth, more than the 10,000 bill

        $this->sales->setPoints($this->store->fresh()->load('setting'), 5000);

        $sale = $this->sell('1');

        $this->assertSame('10000.00', $sale->discount);
        $this->assertSame('0.00', $sale->total);
    }

    public function test_points_are_not_spendable_when_no_value_is_configured(): void
    {
        $this->setPointValue('0');
        $this->givePoints(100);

        $this->sales->setPoints($this->store->fresh()->load('setting'), 50);

        $sale = $this->sell('1');

        $this->assertSame('0.00', $sale->discount);
        $this->assertSame(0, LoyaltyPointTransaction::where('type', MembershipLoyaltyService::TYPE_REDEEMED)->count());
    }

    public function test_a_refund_returns_the_points_that_were_spent(): void
    {
        $this->setPointValue('10');
        $this->givePoints(200);

        $this->sales->setPoints($this->store->fresh()->load('setting'), 100); // 1,000 Ks off

        $sale = $this->sell('2'); // 20,000 bill → 1,000 off → 19,000 paid
        $item = $sale->items->first();

        $before = (int) DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->value('loyalty_points');

        $refund = app(PosReturnService::class)->post(
            store: $this->store->fresh()->load('setting'),
            sale: $sale->fresh(),
            items: [[
                'pos_sale_item_id' => $item->id,
                'quantity' => '1',
            ]],
            refunds: [['method' => 'cash', 'amount' => '9500']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Return counter',
                'opening_cash' => '0',
            ], $this->cashier),
        );

        $this->assertSame('9500.00', $refund->total);

        $after = (int) DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->value('loyalty_points');

        // The whole 100 points were spent at the till (one redeem row)...
        $this->assertSame(-100, (int) LoyaltyPointTransaction::where('pos_sale_id', $sale->id)
            ->where('type', MembershipLoyaltyService::TYPE_REDEEMED)
            ->sum('points'));

        // ...and the refund hands back the half that belongs to the returned line:
        // 200 given − 100 spent = 100, plus 50 returned = 150.
        $this->assertGreaterThan($before, $after);
        $this->assertSame(150, $after);
    }

    public function test_a_coupon_discounts_the_sale_and_records_the_redemption(): void
    {
        $this->price($this->store);

        $sale = $this->sell('2', 'UAT10');

        // 20,000 − 10% = 18,000
        $this->assertSame('20000.00', $sale->subtotal);
        $this->assertSame('2000.00', $sale->discount);
        $this->assertSame('18000.00', $sale->total);
        $this->assertSame('UAT10', $sale->coupon_code);
        $this->assertNotNull($sale->promotion_id);

        $usage = PromotionUsage::where('pos_sale_id', $sale->id)->sole();
        $this->assertSame('2000.00', $usage->discount_applied);
        $this->assertSame($this->customer->id, $usage->customer_id);

        $this->assertSame(1, Promotion::where('code', 'UAT10')->value('used_count'));
    }

    public function test_a_coupon_lowercase_code_is_accepted(): void
    {
        $this->price($this->store);

        $sale = $this->sell('1', 'uat10');

        $this->assertSame('1000.00', $sale->discount);
        $this->assertSame('UAT10', $sale->coupon_code);
    }

    public function test_an_unknown_coupon_is_refused_and_nothing_is_discounted(): void
    {
        $this->expectException(InventoryException::class);

        $this->sell('1', 'NOPE');
    }

    public function test_an_expired_coupon_is_refused(): void
    {
        $this->price($this->store, ['expires_at' => now()->subDay()]);

        $this->expectException(InventoryException::class);

        $this->sell('1', 'UAT10');
    }

    public function test_a_coupon_below_its_minimum_order_is_refused(): void
    {
        $this->price($this->store, ['min_order_amount' => '25000']);

        $this->expectException(InventoryException::class);

        $this->sell('1', 'UAT10');
    }

    public function test_a_coupon_over_its_total_usage_limit_is_refused(): void
    {
        $this->price($this->store, ['total_uses_limit' => 1, 'used_count' => 1]);

        $this->expectException(InventoryException::class);

        $this->sell('1', 'UAT10');
    }

    public function test_a_coupon_over_its_per_customer_limit_is_refused(): void
    {
        $promotion = $this->price($this->store, ['per_customer_limit' => 1]);

        PromotionUsage::create([
            'promotion_id' => $promotion->id,
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'discount_applied' => '1000.00',
        ]);

        $this->expectException(InventoryException::class);

        $this->sell('1', 'UAT10');
    }

    public function test_a_bogo_coupon_gives_every_second_unit_free(): void
    {
        $this->price($this->store, ['type' => 'bogo', 'value' => 1]);

        // 3 units at 10,000 → one is free.
        $sale = $this->sell('3', 'UAT10');

        $this->assertSame('30000.00', $sale->subtotal);
        $this->assertSame('10000.00', $sale->discount);
        $this->assertSame('20000.00', $sale->total);
    }

    public function test_a_bogo_coupon_frees_two_units_out_of_four(): void
    {
        $this->price($this->store, ['type' => 'bogo', 'value' => 1]);

        $sale = $this->sell('4', 'UAT10');

        $this->assertSame('40000.00', $sale->subtotal);
        $this->assertSame('20000.00', $sale->discount);
        $this->assertSame('20000.00', $sale->total);
    }

    public function test_a_bogo_coupon_on_a_single_unit_discounts_nothing_but_is_still_valid(): void
    {
        $this->price($this->store, ['type' => 'bogo', 'value' => 1]);

        $sale = $this->sell('1', 'UAT10');

        $this->assertSame('0.00', $sale->discount);
        $this->assertSame('10000.00', $sale->total);
        $this->assertSame('UAT10', $sale->coupon_code);
    }

    public function test_coupon_and_manual_discount_together_never_exceed_the_bill(): void
    {
        $this->price($this->store, ['type' => 'flat_off', 'value' => '6000']);

        $sale = $this->sales->post(
            store: $this->store->fresh()->load('setting'),
            lines: [[
                'product_id' => $this->product->id,
                'product_variant_id' => null,
                'quantity' => '1',
            ]],
            // A fully discounted bill still needs a payment row; cash over-tender
            // becomes change (kept = 0 against a 0 total).
            payments: [['method' => 'cash', 'amount' => '100']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Counter',
                'opening_cash' => '0',
            ], $this->cashier),
            customerId: $this->customer->id,
            explicitDiscount: '9000',
            couponCode: 'UAT10',
        );

        // 6,000 coupon + 9,000 manual against a 10,000 bill → capped at 10,000.
        $this->assertSame('10000.00', $sale->discount);
        $this->assertSame('0.00', $sale->total);
    }

    public function test_the_coupon_stays_on_the_cart_until_it_is_cleared(): void
    {
        $this->price($this->store);
        $store = $this->store->fresh()->load('setting');

        $this->sales->setCoupon($store, 'uat10');
        $totals = $this->sales->cartTotals($store);

        $this->assertSame('UAT10', $totals['coupon_code']);

        $this->sales->clearCoupon($store);
        $this->assertNull($this->sales->cartTotals($this->store->fresh()->load('setting'))['coupon_code']);
    }

    public function test_a_product_scoped_coupon_discounts_only_that_product(): void
    {
        // "10% off the glass" must not become "10% off the basket".
        $this->price($this->store, ['product_id' => $this->product->id]);

        $other = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Fast Charger',
            'slug' => 'charger-' . Str::random(5),
            'sku' => 'CP2-' . Str::random(5),
            'retail_price' => 25000,
            'wholesale_price' => 20000,
        ]);

        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $other->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '10',
            'unit_cost' => 12000,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed2:' . Str::uuid(),
        ]);

        $sale = $this->sales->post(
            store: $this->store->fresh()->load('setting'),
            lines: [
                ['product_id' => $this->product->id, 'product_variant_id' => null, 'quantity' => '1'],
                ['product_id' => $other->id, 'product_variant_id' => null, 'quantity' => '1'],
            ],
            payments: [['method' => 'cash', 'amount' => '35000']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Counter',
                'opening_cash' => '0',
            ], $this->cashier),
            customerId: $this->customer->id,
            couponCode: 'UAT10',
        );

        // 35,000 bill, 10,000 of it the scoped product → 1,000 off, not 3,500.
        $this->assertSame('35000.00', $sale->subtotal);
        $this->assertSame('1000.00', $sale->discount);
        $this->assertSame('34000.00', $sale->total);
    }

    public function test_a_scoped_coupon_is_refused_when_nothing_in_the_cart_matches(): void
    {
        $other = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Phone Case',
            'slug' => 'case-' . Str::random(5),
            'sku' => 'CP3-' . Str::random(5),
            'retail_price' => 6000,
            'wholesale_price' => 5000,
        ]);

        app(InventoryService::class)->postMovement([
            'store_id' => $this->store->id,
            'product_id' => $other->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => '5',
            'unit_cost' => 2000,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed3:' . Str::uuid(),
        ]);

        // Coupon scoped to the glass; the cart holds only the case.
        $this->price($this->store, ['product_id' => $this->product->id]);

        $this->expectException(InventoryException::class);

        $this->sales->post(
            store: $this->store->fresh()->load('setting'),
            lines: [['product_id' => $other->id, 'product_variant_id' => null, 'quantity' => '1']],
            payments: [['method' => 'cash', 'amount' => '6000']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Counter',
                'opening_cash' => '0',
            ], $this->cashier),
            customerId: $this->customer->id,
            couponCode: 'UAT10',
        );
    }

    public function test_preview_coupon_prices_a_scoped_promo_against_the_cart(): void
    {
        $this->price($this->store, ['product_id' => $this->product->id]);

        // Put the scoped product in the session cart the way the POS does.
        $this->sales->addToCart($this->store, $this->product->id, null, '2');

        $check = $this->sales->previewCoupon($this->store->fresh()->load('setting'), 'uat10', null);

        $this->assertTrue($check['valid']);
        $this->assertSame('2000.00', $check['discount']); // 10% of 20,000
    }

    public function test_preview_coupon_refuses_a_scoped_promo_with_an_empty_cart(): void
    {
        $this->price($this->store, ['product_id' => $this->product->id]);

        $check = $this->sales->previewCoupon($this->store->fresh()->load('setting'), 'uat10', null);

        $this->assertFalse($check['valid']);
        $this->assertSame('coupon_not_applicable', $check['reason']);
    }

    // ── Loyalty earning ───────────────────────────────────────────────────────

    public function test_a_sale_credits_points_and_spending(): void
    {
        $this->setLoyaltyRate('1000'); // 1 point per 1,000 Ks

        $sale = $this->sell('3'); // 30,000 → 30 points

        $this->assertSame('30000.00', $sale->total);

        $transaction = LoyaltyPointTransaction::where('pos_sale_id', $sale->id)->sole();
        $this->assertSame(30, $transaction->points);
        $this->assertSame(30, $transaction->balance_after);
        $this->assertSame(MembershipLoyaltyService::TYPE_EARNED, $transaction->type);

        // Query the pivot directly: the model relation is cached from the
        // pricing lookups the sale itself performed.
        $pivot = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->first();
        $this->assertSame(30, (int) $pivot->loyalty_points);
        $this->assertSame(30000.0, (float) $pivot->total_spent);
    }

    public function test_no_points_when_the_store_has_no_rate_configured(): void
    {
        $this->setLoyaltyRate('0');

        $sale = $this->sell('3');

        $this->assertSame(0, LoyaltyPointTransaction::where('pos_sale_id', $sale->id)->count());

        // Query the pivot directly: the model relation is cached from the
        // pricing lookups the sale itself performed.
        $pivot = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->first();
        $this->assertSame(0, (int) $pivot->loyalty_points);
    }

    public function test_no_points_without_a_customer_on_the_sale(): void
    {
        $this->setLoyaltyRate('1000');

        $sale = $this->sell('3', withCustomer: false);

        $this->assertNull($sale->customer_id);
        $this->assertSame(0, LoyaltyPointTransaction::count());
    }

    public function test_points_use_the_customers_tier_multiplier(): void
    {
        $this->setLoyaltyRate('1000');

        $tier = MembershipTier::where('store_id', $this->store->id)->orderByDesc('min_spending')->first();
        $this->assertSame(2.0, (float) $tier->point_multiplier);

        DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->update(['membership_tier_id' => $tier->id]);

        $sale = $this->sell('2'); // 20,000 → 20 × 2.0 = 40

        $this->assertSame(40, (int) LoyaltyPointTransaction::where('pos_sale_id', $sale->id)->value('points'));
    }

    public function test_a_sale_can_upgrade_the_customer_tier(): void
    {
        $this->setLoyaltyRate('1000');

        $silver = MembershipTier::where('store_id', $this->store->id)->where('code', 'SILVER')->first();
        $this->assertNotNull($silver, 'the default tiers must include a Silver tier');

        // Spend past Silver's threshold in one sale.
        $this->sell('30'); // 300,000

        $tierId = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->value('membership_tier_id');

        $this->assertSame($silver->id, (int) $tierId);
    }

    public function test_earning_is_idempotent_per_sale(): void
    {
        $this->setLoyaltyRate('1000');

        $sale = $this->sell('1');

        // A retry of the same sale must not credit again.
        $this->assertNull(app(MembershipLoyaltyService::class)->accrueForSale($sale));

        $this->assertSame(1, LoyaltyPointTransaction::where('pos_sale_id', $sale->id)->count());
    }

    public function test_a_refund_takes_back_the_points_earned_on_that_sale(): void
    {
        $this->setLoyaltyRate('1000');

        $sale = $this->sell('2'); // 20,000 → 20 points
        $item = $sale->items->first();

        $refund = app(PosReturnService::class)->post(
            store: $this->store->fresh()->load('setting'),
            sale: $sale->fresh(),
            items: [[
                'pos_sale_item_id' => $item->id,
                'quantity' => '1',
            ]],
            refunds: [['method' => 'cash', 'amount' => '10000']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Return counter',
                'opening_cash' => '0',
            ], $this->cashier),
        );

        $this->assertSame('10000.00', $refund->total);

        // Half the sale came back → half the points go back (10 of 20).
        $reversal = LoyaltyPointTransaction::where('pos_sale_id', $sale->id)
            ->where('type', MembershipLoyaltyService::TYPE_ADJUSTED)
            ->sole();

        $this->assertSame(-10, $reversal->points);

        // Query the pivot directly: the model relation is cached from the
        // pricing lookups the sale itself performed.
        $pivot = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->first();
        $this->assertSame(10, (int) $pivot->loyalty_points);
        $this->assertSame(10000.0, (float) $pivot->total_spent);
    }

    public function test_a_full_refund_returns_the_balance_to_zero(): void
    {
        $this->setLoyaltyRate('1000');

        $sale = $this->sell('1'); // 10,000 → 10 points
        $item = $sale->items->first();

        app(PosReturnService::class)->post(
            store: $this->store->fresh()->load('setting'),
            sale: $sale->fresh(),
            items: [[
                'pos_sale_item_id' => $item->id,
                'quantity' => '1',
            ]],
            refunds: [['method' => 'cash', 'amount' => '10000']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Return counter',
                'opening_cash' => '0',
            ], $this->cashier),
        );

        // Query the pivot directly: the model relation is cached from the
        // pricing lookups the sale itself performed.
        $pivot = DB::table('store_user')
            ->where('store_id', $this->store->id)
            ->where('user_id', $this->customer->id)
            ->first();
        $this->assertSame(0, (int) $pivot->loyalty_points);
        $this->assertSame(0.0, (float) $pivot->total_spent);
    }

    public function test_points_are_floored_so_a_partial_amount_never_over_credits(): void
    {
        $this->setLoyaltyRate('1000');

        // 5,500 Ks at 1 point per 1,000 → 5 points, not 6.
        $sale = $this->sales->post(
            store: $this->store->fresh()->load('setting'),
            lines: [[
                'product_id' => $this->product->id,
                'product_variant_id' => null,
                'quantity' => '1',
                'unit_price' => '5500',
            ]],
            payments: [['method' => 'cash', 'amount' => '5500']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Counter',
                'opening_cash' => '0',
            ], $this->cashier),
            customerId: $this->customer->id,
        );

        $this->assertSame(5, (int) LoyaltyPointTransaction::where('pos_sale_id', $sale->id)->value('points'));
    }

    // ── What the checkout posts back ──────────────────────────────────────────

    /**
     * The checkout modal posts the manual discount back and the server adds the
     * coupon and the spent points on top. When the posted value already carried
     * that coupon and those points, the sale was charged twice for both: a bill
     * the cashier saw as −500 was stored as −1,000, so the receipt and the daily
     * total disagreed with the screen the customer was shown.
     */
    public function test_the_posted_manual_discount_charges_the_coupon_and_the_points_exactly_once(): void
    {
        $this->price($this->store);
        $this->setPointValue('10');
        $this->givePoints(50);

        $store = $this->store->fresh()->load('setting');
        $this->sales->attachCartCustomer($store, $this->customer);
        $this->sales->setCoupon($store, 'UAT10');
        $this->sales->setDiscount($store, '1000');
        $this->sales->setPoints($store, 50);
        $this->sales->addToCart($store, $this->product->id, null, '1');

        $totals = $this->sales->cartTotals($store);

        $sale = $this->sales->post(
            store: $store->fresh()->load('setting'),
            lines: [['product_id' => $this->product->id, 'product_variant_id' => null, 'quantity' => '1']],
            payments: [['method' => 'cash', 'amount' => '8400']],
            actor: $this->cashier,
            shift: app(CashierShiftService::class)->openShift($this->store, [
                'register_name' => 'Counter',
                'opening_cash' => '0',
            ], $this->cashier),
            customerId: $this->customer->id,
            // Exactly what the payment modal submits, and what the cashier was
            // looking at when they hit "post".
            explicitDiscount: $totals['manual_discount'],
            couponCode: $totals['coupon_code'],
        );

        // 10,000 − 1,000 manual − 1,000 coupon − 500 points.
        $this->assertSame('1000', $totals['manual_discount']);
        $this->assertSame('2500.00', $totals['discount']);
        $this->assertSame('7500.00', $totals['total']);

        $this->assertSame($totals['discount'], $sale->discount);
        $this->assertSame($totals['total'], $sale->total);

        // The points left the wallet once, and only the redemption row is a spend.
        $spent = LoyaltyPointTransaction::where('pos_sale_id', $sale->id)
            ->where('type', MembershipLoyaltyService::TYPE_REDEEMED)
            ->sum('points');

        $this->assertSame(-50, (int) $spent);
    }

    public function test_the_cart_totals_separate_the_manual_discount_from_the_coupon_and_points(): void
    {
        $this->price($this->store);
        $this->setPointValue('10');
        $this->givePoints(50);

        $store = $this->store->fresh()->load('setting');
        $this->sales->attachCartCustomer($store, $this->customer);
        $this->sales->setCoupon($store, 'UAT10');
        $this->sales->setDiscount($store, '1000');
        $this->sales->setPoints($store, 50);
        $this->sales->addToCart($store, $this->product->id, null, '1');

        $totals = $this->sales->cartTotals($store);

        // manual 1,000 + coupon 1,000 + points 500 = 2,500 off the bill…
        $this->assertSame('1000', $totals['manual_discount']);
        $this->assertSame('2500.00', $totals['discount']);
        // …and the coupon and the points stay visible on their own, so the cart
        // can show what each one took off.
        $this->assertSame('1000.00', $totals['coupon_discount']);
        $this->assertSame(50, $totals['points_redeemed']);
        $this->assertSame('500.00', $totals['points_value']);
    }

    /**
     * The two files that turn a cart into a posted order both have to send the
     * manual discount back — a regression here re-introduces the double charge,
     * and nothing else in the suite can see it because the template is only
     * exercised in a browser.
     */
    public function test_the_checkout_ui_sends_the_manual_discount_back(): void
    {
        $paymentModal = file_get_contents(resource_path('views/pos/partials/modal-payment.blade.php'));

        $this->assertStringContainsString(':value="cart.totals.manual_discount', $paymentModal);
        $this->assertStringNotContainsString(':value="cart.totals.discount', $paymentModal);

        $js = file_get_contents(resource_path('js/app-admin.js'));

        $this->assertStringContainsString('this.cart.totals.manual_discount', $js);
        $this->assertStringNotContainsString("this.discountValue = Number(this.cart.totals.discount", $js);
    }
}
