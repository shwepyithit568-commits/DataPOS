<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderFinanceAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'DataPOS Finance', 'slug' => 'datapos-finance']);
        StorefrontSetting::create([
            'store_id'          => $this->store->id,
            'store_name'        => 'DataPOS Finance',
            'currency_settings' => ['symbol_position' => 'before_space'],
        ]);
        $this->admin = User::factory()->create(['phone' => '09123456789']);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);
    }

    private function makeOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(10)),
            'customer_name' => 'Kyaw Gyi',
            'customer_phone' => '09779463714',
            'customer_address' => 'Yangon',
            'contact_channel' => 'viber',
            'contact_identifier' => '09779463714',
            'pricing_type' => 'retail',
            'total_amount' => 0,
            'status' => 'pending_contact',
        ], $overrides));
    }

    /** Admin records the final agreed price (glass orders have no price). */
    public function test_admin_can_record_agreed_amount_and_payment_status(): void
    {
        $order = $this->makeOrder();

        $response = $this->actingAs($this->admin)
            ->patch("/store/{$this->store->slug}/admin/orders/{$order->id}/finances", [
                'agreed_amount' => 45000,
                'payment_status' => 'paid',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(45000, (float) $order->agreed_amount);
        $this->assertEquals('paid', $order->payment_status);
    }

    /** Clearing the agreed amount input returns it to null. */
    public function test_admin_can_clear_agreed_amount_back_to_null(): void
    {
        $order = $this->makeOrder(['agreed_amount' => 30000, 'payment_status' => 'paid']);

        $this->actingAs($this->admin)
            ->patch("/store/{$this->store->slug}/admin/orders/{$order->id}/finances", [
                'agreed_amount' => '',
                'payment_status' => 'unpaid',
            ]);

        $order->refresh();
        $this->assertNull($order->agreed_amount);
        $this->assertEquals('unpaid', $order->payment_status);
    }

    /** Revenue uses agreed_amount where set and total_amount elsewhere. */
    public function test_revenue_uses_agreed_amount_when_set(): void
    {
        $glassOrder = $this->makeOrder(['status' => 'confirmed']);
        $this->actingAs($this->admin)
            ->patch("/store/{$this->store->slug}/admin/orders/{$glassOrder->id}/finances", [
                'agreed_amount' => 50000,
                'payment_status' => 'paid',
            ]);

        $this->makeOrder(['total_amount' => 20000, 'status' => 'confirmed']);

        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/orders");

        $response->assertOk();
        $response->assertSee('Ks 70,000'); // 50000 (agreed) + 20000 (total)
    }

    /** Negative agreed amounts are rejected. */
    public function test_agreed_amount_validation_rejects_negative(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->admin)
            ->patch("/store/{$this->store->slug}/admin/orders/{$order->id}/finances", [
                'agreed_amount' => -5,
                'payment_status' => 'paid',
            ])
            ->assertSessionHasErrors('agreed_amount');
    }

    /** Another store's order cannot be edited from this store. */
    public function test_cross_store_finance_update_is_forbidden(): void
    {
        $otherStore = Store::create(['name' => 'Other', 'slug' => 'other-store']);
        $otherOrder = Order::create([
            'store_id' => $otherStore->id,
            'order_number' => 'ORD-OTHER-1',
            'customer_name' => 'Aung',
            'customer_phone' => '09333333333',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => 1000,
            'status' => 'pending_contact',
        ]);

        $this->actingAs($this->admin)
            ->patch("/store/{$this->store->slug}/admin/orders/{$otherOrder->id}/finances", [
                'agreed_amount' => 100,
                'payment_status' => 'paid',
            ])
            ->assertForbidden();
    }

    /** CSV export streams the filtered orders with all expected columns. */
    public function test_orders_csv_export_contains_orders_and_items(): void
    {
        $order = $this->makeOrder([
            'customer_name' => 'CSV Person',
            'total_amount' => 15000,
            'status' => 'confirmed',
            'agreed_amount' => 18000,
            'payment_status' => 'paid',
        ]);
        $order->items()->create([
            'product_name' => 'Glass: POCO C3 (Code: W022)',
            'unit_price' => 0,
            'quantity' => 2,
            'subtotal' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/orders/export");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString($order->order_number, $content);
        $this->assertStringContainsString('CSV Person', $content);
        $this->assertStringContainsString('Glass: POCO C3 (Code: W022) ×2', $content);
        $this->assertStringContainsString('18,000', $content);
        $this->assertTrue(str_contains($content, 'paid') || str_contains($content, 'ငွေချေပြီး'));
    }

    /** The order detail page exposes the payment & price form. */
    public function test_order_detail_shows_payment_form(): void
    {
        $order = $this->makeOrder(['agreed_amount' => 25000, 'payment_status' => 'unpaid']);

        $response = $this->actingAs($this->admin)
            ->get("/store/{$this->store->slug}/admin/orders/{$order->id}");

        $response->assertOk();
        $response->assertSee('agreed_amount', false);
        $response->assertSee('name="agreed_amount"', false);
        $response->assertSee('name="payment_status"', false);
        $response->assertSee('25000', false);
    }
}
