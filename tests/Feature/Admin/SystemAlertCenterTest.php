<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAlertCenterTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Alerts Test Store', 'slug' => 'alerts-test-store']);
        $this->store->setting()->create(['store_name' => 'Alerts Test Store', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager Daw Aye', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Ko Gyi', 'phone' => '09444555666']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_manager_can_access_alerts_dashboard(): void
    {
        $product = Product::create([
            'store_id'        => $this->store->id,
            'name'            => 'Low Stock Charger',
            'sku'             => 'CHG-LOW-01',
            'slug'            => 'low-stock-charger',
            'retail_price'    => 15000,
            'wholesale_price' => 12000,
            'stock_status'    => 'out_of_stock',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/alerts");

        $response->assertOk();
        $response->assertSee('System Alert Center');
        $response->assertSee('Low Stock Charger');
    }

    public function test_staff_can_view_alerts_dashboard(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/alerts");

        $response->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/alerts");
        $response->assertRedirect('/login');
    }

    public function test_manager_can_dispatch_daily_summary(): void
    {
        Order::create([
            'store_id'       => $this->store->id,
            'order_number'   => 'ORD-2026-001',
            'customer_name'  => 'U Mya',
            'customer_phone' => '09999888777',
            'status'         => 'confirmed',
            'total_amount'   => 50000,
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/alerts/daily-summary");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
