<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8SecurityMaintenanceDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $ownerA;
    protected User $managerA;
    protected User $staffA;
    protected User $managerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store Alpha', 'slug' => 'store-alpha', 'is_active' => true]);
        $this->storeB = Store::create(['name' => 'Store Beta', 'slug' => 'store-beta', 'is_active' => true]);

        $this->ownerA = User::create(['name' => 'Owner Alpha', 'phone' => '09111111111', 'password' => bcrypt('password')]);
        $this->managerA = User::create(['name' => 'Manager Alpha', 'phone' => '09222222222', 'password' => bcrypt('password')]);
        $this->staffA = User::create(['name' => 'Cashier Alpha', 'phone' => '09333333333', 'password' => bcrypt('password')]);
        $this->managerB = User::create(['name' => 'Manager Beta', 'phone' => '09444444444', 'password' => bcrypt('password')]);

        $this->storeA->users()->attach($this->ownerA->id, ['role' => 'store_owner', 'status' => 'active']);
        $this->storeA->users()->attach($this->managerA->id, ['role' => 'store_manager', 'status' => 'active']);
        $this->storeA->users()->attach($this->staffA->id, ['role' => 'staff', 'status' => 'active']);
        $this->storeB->users()->attach($this->managerB->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_system_alert_center_data_isolation(): void
    {
        // Low stock product in Store A
        Product::create([
            'store_id' => $this->storeA->id,
            'name' => 'Alpha Low Stock Medicine',
            'slug' => 'alpha-low-stock-medicine',
            'sku' => 'MED-ALPHA-01',
            'cost_price' => 4000,
            'retail_price' => 5000,
            'wholesale_price' => 4500,
            'stock_status' => 'out_of_stock',
        ]);

        // Low stock product in Store B
        Product::create([
            'store_id' => $this->storeB->id,
            'name' => 'Beta Low Stock Gadget',
            'slug' => 'beta-low-stock-gadget',
            'sku' => 'GADGET-BETA-01',
            'cost_price' => 80000,
            'retail_price' => 99000,
            'wholesale_price' => 90000,
            'stock_status' => 'out_of_stock',
        ]);

        // Pending order in Store A
        Order::create([
            'store_id' => $this->storeA->id,
            'order_number' => 'ORD-ALPHA-1',
            'customer_name' => 'Alpha Shopper',
            'customer_phone' => '09123456789',
            'contact_channel' => 'phone',
            'status' => 'pending_contact',
            'pricing_type' => 'retail',
            'total_amount' => 5000,
        ]);

        // Pending order in Store B
        Order::create([
            'store_id' => $this->storeB->id,
            'order_number' => 'ORD-BETA-1',
            'customer_name' => 'Beta Shopper',
            'customer_phone' => '09987654321',
            'contact_channel' => 'phone',
            'status' => 'pending_contact',
            'pricing_type' => 'retail',
            'total_amount' => 99000,
        ]);

        // Access Store A System Alerts
        $response = $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/alerts");

        $response->assertOk();
        $response->assertSee('Alpha Low Stock Medicine');
        $response->assertSee('ORD-ALPHA-1');
        $response->assertDontSee('Beta Low Stock Gadget');
        $response->assertDontSee('ORD-BETA-1');
    }

    public function test_audit_trail_logs_data_isolation(): void
    {
        // Create Audit Logs for Store A and Store B
        $logA = AuditLog::create([
            'store_id' => $this->storeA->id,
            'action' => 'product_created',
            'entity_type' => 'product',
            'entity_id' => 101,
            'actor_id' => $this->managerA->id,
            'ip_address' => '127.0.0.1',
            'metadata' => ['name' => 'Alpha Product Created'],
        ]);

        $logB = AuditLog::create([
            'store_id' => $this->storeB->id,
            'action' => 'bulk_price_updated',
            'entity_type' => 'pricing',
            'entity_id' => 202,
            'actor_id' => $this->managerB->id,
            'ip_address' => '127.0.0.1',
            'metadata' => ['name' => 'Beta Prices Updated'],
        ]);

        // 1. Manager A visiting Store A audit logs sees Log A but NOT Log B
        $response = $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/security/audit-logs");

        $response->assertOk();
        $response->assertSee('product_created');
        $response->assertDontSee('bulk_price_updated');

        // 2. Manager A attempting to directly view Log B returns 403 Forbidden
        $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/security/audit-logs/{$logB->id}")
            ->assertForbidden();
    }

    public function test_database_tools_access_control(): void
    {
        // 1. Regular staff cannot access database maintenance tools
        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/database")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->post("/store/{$this->storeA->slug}/admin/database/clear-cache")
            ->assertForbidden();

        // 2. Store manager can access database maintenance tools
        $response = $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/database");

        $response->assertOk();
    }

    public function test_user_management_owner_role_protection(): void
    {
        // 1. Manager and Staff cannot access Store Users management
        $this->actingAs($this->managerA)
            ->get("/store/{$this->storeA->slug}/admin/users")
            ->assertForbidden();

        $this->actingAs($this->staffA)
            ->get("/store/{$this->storeA->slug}/admin/users")
            ->assertForbidden();

        // 2. Store Owner can access Store Users management
        $response = $this->actingAs($this->ownerA)
            ->get("/store/{$this->storeA->slug}/admin/users");

        $response->assertOk();
    }
}
