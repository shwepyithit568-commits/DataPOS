<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $cashier;
    protected Store $otherStore;
    protected User $otherManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Analytics Store 1', 'slug' => 'analytics-store-1']);
        $this->store->setting()->create(['store_name' => 'Analytics Store 1', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager User', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->cashier = User::factory()->create(['name' => 'Cashier Aye Aye', 'phone' => '09111444555']);
        $this->cashier->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->otherStore = Store::create(['name' => 'Analytics Store 2', 'slug' => 'analytics-store-2']);
        $this->otherStore->setting()->create(['store_name' => 'Analytics Store 2', 'default_language' => 'en']);

        $this->otherManager = User::factory()->create(['name' => 'Other Manager', 'phone' => '09999888777']);
        $this->otherManager->stores()->attach($this->otherStore->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_manager_can_access_sales_analytics_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/sales-analytics");

        $response->assertOk();
        $response->assertSee(__('messages.sales_analytics_title'));
    }

    public function test_sales_analytics_aggregates_pos_sales_and_online_orders(): void
    {
        $category = Category::create(['store_id' => $this->store->id, 'name' => 'Smartphones', 'slug' => 'smartphones']);
        $brand = Brand::create(['store_id' => $this->store->id, 'name' => 'Samsung', 'slug' => 'samsung']);

        $product1 = Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $category->id,
            'brand_id'        => $brand->id,
            'name'            => 'Galaxy A54',
            'slug'            => 'galaxy-a54',
            'sku'             => 'SM-A546B',
            'retail_price'    => 850000.00,
            'wholesale_price' => 800000.00,
            'purchase_cost'   => 700000.00,
            'stock_status'    => 'in_stock',
        ]);

        // 1. Create a POS Sale
        $posSale = PosSale::create([
            'store_id'        => $this->store->id,
            'cashier_id'      => $this->cashier->id,
            'receipt_number'  => 'REC-001',
            'status'          => 'posted',
            'subtotal'        => 850000.00,
            'discount'        => 50000.00,
            'tax'             => 0.00,
            'total'           => 800000.00,
            'posted_at'       => now(),
        ]);

        PosSaleItem::create([
            'pos_sale_id'   => $posSale->id,
            'product_id'    => $product1->id,
            'product_name'  => $product1->name,
            'sku'           => $product1->sku,
            'unit_price'    => 850000.00,
            'quantity'      => 1,
            'unit_cost'     => 700000.00,
            'line_total'    => 850000.00,
        ]);

        PosPayment::create([
            'pos_sale_id' => $posSale->id,
            'method'      => 'kpay',
            'amount'      => 800000.00,
        ]);

        // 2. Create an Online Order
        $order = Order::create([
            'store_id'        => $this->store->id,
            'order_number'    => 'ORD-2026-001',
            'customer_name'   => 'Ko Kyaw',
            'customer_phone'  => '09555666777',
            'status'          => 'delivered',
            'total_amount'    => 850000.00,
            'agreed_amount'   => 850000.00,
            'contact_channel' => 'viber',
            'payment_status'  => 'paid',
            'created_at'      => now(),
        ]);

        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product1->id,
            'product_name' => $product1->name,
            'unit_price'   => 850000.00,
            'quantity'     => 1,
            'subtotal'     => 850000.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/sales-analytics?preset=today");

        $response->assertOk();
        // Total Net Sales = 800,000 + 850,000 = 1,650,000
        $response->assertSee('1,650,000');
        // Total Invoices/Orders = 2
        $response->assertSee('Galaxy A54');
        $response->assertSee('Smartphones');
        $response->assertSee('Samsung');
    }

    public function test_sales_analytics_channel_filtering(): void
    {
        $product = Product::create([
            'store_id'        => $this->store->id,
            'name'            => 'Test Cable',
            'slug'            => 'test-cable',
            'sku'             => 'CBL-001',
            'retail_price'    => 10000.00,
            'wholesale_price' => 8000.00,
            'purchase_cost'   => 5000.00,
            'stock_status'    => 'in_stock',
        ]);

        // POS Sale = 10,000
        $posSale = PosSale::create([
            'store_id'        => $this->store->id,
            'cashier_id'      => $this->cashier->id,
            'receipt_number'  => 'REC-002',
            'status'          => 'posted',
            'subtotal'        => 10000.00,
            'discount'        => 0.00,
            'tax'             => 0.00,
            'total'           => 10000.00,
            'posted_at'       => now(),
        ]);
        PosSaleItem::create([
            'pos_sale_id'   => $posSale->id,
            'product_id'    => $product->id,
            'product_name'  => $product->name,
            'sku'           => $product->sku,
            'unit_price'    => 10000.00,
            'quantity'      => 1,
            'unit_cost'     => 5000.00,
            'line_total'    => 10000.00,
        ]);

        // Online Order = 25,000
        $order = Order::create([
            'store_id'        => $this->store->id,
            'order_number'    => 'ORD-2026-002',
            'customer_name'   => 'Ko Hla',
            'customer_phone'  => '09123456789',
            'status'          => 'delivered',
            'total_amount'    => 25000.00,
            'agreed_amount'   => 25000.00,
            'payment_status'  => 'paid',
            'created_at'      => now(),
        ]);
        OrderItem::create([
            'order_id'     => $order->id,
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'unit_price'   => 25000.00,
            'quantity'     => 1,
            'subtotal'     => 25000.00,
        ]);

        // 1. Filter POS Only
        $posResponse = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/sales-analytics?preset=today&channel=pos");
        $posResponse->assertOk();
        $posResponse->assertSee('10,000');

        // 2. Filter Online Only
        $onlineResponse = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/sales-analytics?preset=today&channel=online");
        $onlineResponse->assertOk();
        $onlineResponse->assertSee('25,000');
    }

    public function test_sales_analytics_cashier_performance_leaderboard(): void
    {
        $posSale = PosSale::create([
            'store_id'        => $this->store->id,
            'cashier_id'      => $this->cashier->id,
            'receipt_number'  => 'REC-003',
            'status'          => 'posted',
            'subtotal'        => 50000.00,
            'discount'        => 5000.00,
            'tax'             => 0.00,
            'total'           => 45000.00,
            'posted_at'       => now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/sales-analytics?preset=today");

        $response->assertOk();
        $response->assertSee('Cashier Aye Aye');
        $response->assertSee('45,000');
        $response->assertSee('5,000');
    }

    public function test_sales_analytics_csv_export(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/sales-analytics/export?preset=today");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_sales_analytics_store_isolation(): void
    {
        // Sale in Store 2
        $otherSale = PosSale::create([
            'store_id'        => $this->otherStore->id,
            'cashier_id'      => $this->otherManager->id,
            'receipt_number'  => 'REC-STORE2',
            'status'          => 'posted',
            'subtotal'        => 999999.00,
            'discount'        => 0.00,
            'tax'             => 0.00,
            'total'           => 999999.00,
            'posted_at'       => now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/reports/sales-analytics?preset=today");

        $response->assertOk();
        // Should NOT see Store 2's numbers
        $response->assertDontSee('999,999');
    }
}
