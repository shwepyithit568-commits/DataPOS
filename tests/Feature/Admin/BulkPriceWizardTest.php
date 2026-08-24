<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\BulkPriceWizardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkPriceWizardTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected Category $category;
    protected Brand $brand;
    protected Product $product1;
    protected Product $product2;
    protected Product $product3;
    protected BulkPriceWizardService $wizardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wizardService = app(BulkPriceWizardService::class);

        $this->store = Store::create([
            'name' => 'DataPOS Electronics & Mobile',
            'slug' => 'datapos-electronics',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Ko Aung Myo',
            'role' => 'store_manager',
        ]);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->category = Category::create([
            'store_id' => $this->store->id,
            'name' => 'Smartphones',
            'slug' => 'smartphones',
        ]);

        $this->brand = Brand::create([
            'store_id' => $this->store->id,
            'name' => 'Samsung',
            'slug' => 'samsung',
        ]);

        // Product 1: Normal with cost
        $this->product1 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Galaxy S24 Ultra 256GB',
            'slug' => 'galaxy-s24-ultra',
            'sku' => 'SAM-S24U-256',
            'purchase_cost' => 3000000,
            'retail_price' => 3600000,
            'wholesale_price' => 3300000,
            'stock_status' => 'in_stock',
        ]);

        // Create default variant for product 1
        ProductVariant::create([
            'product_id' => $this->product1->id,
            'name' => 'Titanium Black',
            'sku' => 'SAM-S24U-256-BLK',
            'retail_price' => 3600000,
            'wholesale_price' => 3300000,
            'is_default' => true,
            'stock_status' => 'in_stock',
        ]);

        // Product 2: Lower margin with cost
        $this->product2 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Galaxy A55 5G',
            'slug' => 'galaxy-a55-5g',
            'sku' => 'SAM-A55-128',
            'purchase_cost' => 1200000,
            'retail_price' => 1350000,
            'wholesale_price' => 1280000,
            'stock_status' => 'in_stock',
        ]);

        // Product 3: Zero / missing cost
        $this->product3 = Product::create([
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'name' => 'Universal Type-C Fast Cable',
            'slug' => 'universal-type-c-cable',
            'sku' => 'ACC-TC-01',
            'purchase_cost' => 0,
            'retail_price' => 15000,
            'wholesale_price' => 12000,
            'stock_status' => 'in_stock',
        ]);
    }

    public function test_price_wizard_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('store.admin.price_wizard.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee('Bulk Price Wizard');
        $response->assertSee('Galaxy S24 Ultra');
        $response->assertSee('Galaxy A55 5G');
        $response->assertSee('Universal Type-C Fast Cable');
    }

    public function test_price_wizard_shows_correct_statistics(): void
    {
        $stats = $this->wizardService->getStatistics($this->store);

        $this->assertEquals(3, $stats['total_products']);
        $this->assertEquals(2, $stats['with_cost_count']);
        $this->assertEquals(1, $stats['zero_cost_count']);
        $this->assertGreaterThan(0, $stats['avg_margin']);
        $this->assertEquals(0, $stats['below_cost_count']);
    }

    public function test_filter_products_by_search(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('store.admin.price_wizard.index', [
                'store_slug' => $this->store->slug,
                'search' => 'S24 Ultra',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Galaxy S24 Ultra');
        $response->assertDontSee('Galaxy A55 5G');
    }

    public function test_calculate_markup_on_cost(): void
    {
        // Cost: 3,000,000 + 20% Markup = 3,600,000
        $result = $this->wizardService->calculateNewPrice(3000000, 3200000, 'markup_on_cost', 20, 'none');
        $this->assertEquals(3600000, $result);

        // Cost: 1,200,000 + 25% Markup = 1,500,000
        $result2 = $this->wizardService->calculateNewPrice(1200000, 1350000, 'markup_on_cost', 25, 'none');
        $this->assertEquals(1500000, $result2);
    }

    public function test_calculate_target_profit_margin(): void
    {
        // Cost: 8,000, Target Margin: 20% -> Cost / (1 - 0.20) = 10,000
        $result = $this->wizardService->calculateNewPrice(8000, 9000, 'margin_on_cost', 20, 'none');
        $this->assertEquals(10000, $result);

        // Cost: 3,000,000, Target Margin: 25% -> 3,000,000 / 0.75 = 4,000,000
        $result2 = $this->wizardService->calculateNewPrice(3000000, 3600000, 'margin_on_cost', 25, 'none');
        $this->assertEquals(4000000, $result2);
    }

    public function test_calculate_percentage_and_fixed_amount(): void
    {
        // Current: 15,000 + 10% = 16,500
        $result1 = $this->wizardService->calculateNewPrice(0, 15000, 'percentage_on_current', 10, 'none');
        $this->assertEquals(16500, $result1);

        // Current: 15,000 + 3,000 MMK = 18,000
        $result2 = $this->wizardService->calculateNewPrice(0, 15000, 'fixed_amount_on_current', 3000, 'none');
        $this->assertEquals(18000, $result2);

        // Fixed Price: 25,000
        $result3 = $this->wizardService->calculateNewPrice(0, 15000, 'fixed_price', 25000, 'none');
        $this->assertEquals(25000, $result3);
    }

    public function test_rounding_rules(): void
    {
        // Raw: 12,345
        $this->assertEquals(12350, $this->wizardService->applyRounding(12345, 'round_10'));
        $this->assertEquals(12350, $this->wizardService->applyRounding(12345, 'round_50'));
        $this->assertEquals(12300, $this->wizardService->applyRounding(12345, 'round_100'));
        $this->assertEquals(12500, $this->wizardService->applyRounding(12345, 'round_500'));
        $this->assertEquals(12000, $this->wizardService->applyRounding(12345, 'round_1000'));
        $this->assertEquals(12900, $this->wizardService->applyRounding(12345, 'charm_900'));
        $this->assertEquals(12990, $this->wizardService->applyRounding(12345, 'charm_990'));
    }

    public function test_apply_bulk_price_update_persists_changes_and_syncs_variants(): void
    {
        $payload = [
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'retail_price' => 3800000,
                    'wholesale_price' => 3500000,
                ],
                [
                    'product_id' => $this->product2->id,
                    'retail_price' => 1400000,
                ],
            ],
            'sync_variants' => 1,
            'set_old_price' => 0,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('store.admin.price_wizard.apply', ['store_slug' => $this->store->slug]), $payload);

        $response->assertRedirect(route('store.admin.price_wizard.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        // Check products updated
        $this->product1->refresh();
        $this->assertEquals('3800000.00', $this->product1->retail_price);
        $this->assertEquals('3500000.00', $this->product1->wholesale_price);

        $this->product2->refresh();
        $this->assertEquals('1400000.00', $this->product2->retail_price);

        // Check variant synchronized
        $variant = ProductVariant::where('product_id', $this->product1->id)->where('is_default', true)->first();
        $this->assertNotNull($variant);
        $this->assertEquals('3800000.00', $variant->retail_price);
        $this->assertEquals('3500000.00', $variant->wholesale_price);
    }

    public function test_apply_bulk_update_records_audit_log(): void
    {
        $payload = [
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'retail_price' => 3750000,
                ],
            ],
            'sync_variants' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('store.admin.price_wizard.apply', ['store_slug' => $this->store->slug]), $payload);

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $this->store->id,
            'action' => 'bulk_price_update',
            'entity_type' => 'products',
            'actor_id' => $this->admin->id,
        ]);
    }

    public function test_export_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('store.admin.price_wizard.export', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->contains('content-type', 'text/csv; charset=UTF-8'));
    }

    public function test_calculate_ajax_endpoint(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('store.admin.price_wizard.calculate', ['store_slug' => $this->store->slug]), [
                'cost' => 2000000,
                'current_price' => 2400000,
                'mode' => 'markup_on_cost',
                'value' => 30,
                'rounding' => 'round_1000',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'new_price' => 2600000,
            'is_below_cost' => false,
        ]);
    }
}
