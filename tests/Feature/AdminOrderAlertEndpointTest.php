<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\WholesaleApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderAlertEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Store $otherStore;
    protected User $manager;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'DataPOS Alerts', 'slug' => 'datapos-alerts']);
        $this->otherStore = Store::create(['name' => 'Other Store', 'slug' => 'other-store']);

        $this->manager = User::factory()->create(['phone' => '09111110001']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->customer = User::factory()->create(['phone' => '09111110002', 'role' => 'customer']);
    }

    private function makeOrder(Store $store, string $status = 'pending_contact'): Order
    {
        return Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-ALERT-' . strtoupper(Str::random(6)),
            'customer_name' => 'Alert Buyer',
            'customer_phone' => '09123456789',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 15000,
            'status' => $status,
        ]);
    }

    /** The poll endpoint reports pending counts and the latest arrivals. */
    public function test_manager_can_poll_alert_endpoint(): void
    {
        // Confirmed first so the pending order is the latest (highest id).
        $this->makeOrder($this->store, 'confirmed');
        $pending = $this->makeOrder($this->store);

        $app = WholesaleApplication::create([
            'store_id' => $this->store->id,
            'user_id' => $this->manager->id,
            'business_name' => 'Phone Repair Shop',
            'phone' => '09111112222',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/alerts/check");

        $response->assertOk();
        $response->assertJson([
            'pending_orders' => 1,
            'pending_wholesale' => 1,
            'today_orders' => 2,
            'max_order_id' => $pending->id,
            'max_app_id' => $app->id,
        ]);
        $response->assertJsonPath('latest_order.order_number', $pending->order_number);
        $response->assertJsonPath('latest_order.customer_name', 'Alert Buyer');
        $response->assertJsonPath('latest_app.business_name', 'Phone Repair Shop');
    }

    /** Only the current store's data is exposed. */
    public function test_alert_endpoint_ignores_other_stores(): void
    {
        $this->makeOrder($this->store);
        $this->makeOrder($this->otherStore);

        WholesaleApplication::create([
            'store_id' => $this->otherStore->id,
            'user_id' => $this->manager->id,
            'business_name' => 'Other Shop',
            'phone' => '09111113333',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson("/store/{$this->store->slug}/admin/alerts/check");

        $response->assertOk();
        $response->assertJson([
            'pending_orders' => 1,
            'pending_wholesale' => 0,
        ]);
    }

    /** A manager of another store cannot poll this store's alerts. */
    public function test_cross_store_alert_poll_is_forbidden(): void
    {
        $this->actingAs($this->manager)
            ->getJson("/store/{$this->otherStore->slug}/admin/alerts/check")
            ->assertForbidden();
    }

    /** A customer without a store role is blocked from the alert endpoint. */
    public function test_customer_without_store_role_is_blocked(): void
    {
        $this->actingAs($this->customer)
            ->getJson("/store/{$this->store->slug}/admin/alerts/check")
            ->assertForbidden();
    }

    /**
     * Order stats revenue = confirmed + delivered (revenue must NOT drop when
     * an order moves confirmed → delivered), and pendingRevenue sums only
     * pending_contact orders. Regression guard for the Revenue stat card.
     */
    public function test_order_stats_revenue_counts_confirmed_and_delivered_but_not_pending(): void
    {
        Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-REV-CONFIRMED',
            'customer_name' => 'Rev Buyer',
            'customer_phone' => '09123456789',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 10000,
            'status' => 'confirmed',
        ]);
        Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-REV-DELIVERED',
            'customer_name' => 'Rev Buyer',
            'customer_phone' => '09123456789',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 20000,
            'status' => 'delivered',
        ]);
        Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-REV-PENDING',
            'customer_name' => 'Rev Buyer',
            'customer_phone' => '09123456789',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 15000,
            'status' => 'pending_contact',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/orders");

        $response->assertOk();

        // Revenue card = confirmed (10k) + delivered (20k) = 30,000
        $response->assertSee('Ks 30,000');
        // Pending revenue line inside the Revenue card = 15,000 (pending only).
        // Locale-independent: the label resolves via the app locale.
        $response->assertSee(__('messages.pending_revenue') . ': Ks 15,000');
        // Tooltip wording explaining the semantics (translated per-locale, so
        // resolve the key through the app locale the test runs under).
        $response->assertSee(e(__('messages.revenue_confirmed_only')));
    }

    /** The admin layout wires up the polling attributes for store-scoped pages. */
    public function test_admin_layout_renders_alert_poll_attributes(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/dashboard");

        $response->assertOk();
        $response->assertSee('data-admin-alerts-url', false);
        $response->assertSee("/store/{$this->store->slug}/admin/alerts/check", false);
        $response->assertSee('data-admin-alerts-interval', false);
        $response->assertSee('data-pending-orders-stat', false);
        $response->assertSee('data-pending-wholesale-stat', false);
    }
}
