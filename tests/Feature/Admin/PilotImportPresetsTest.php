<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\HomeBanner;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PilotImportPresetsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->store = Store::create([
            'name' => 'Diamond Stone Agri Test',
            'slug' => 'diamond-stone-agri-test',
            'business_type' => 'agriculture_inputs',
            'is_active' => true,
        ]);
        $this->store->setting()->create([
            'store_name' => 'Diamond Stone Agri Test',
            'default_language' => 'my',
        ]);

        $this->manager = User::factory()->create(['name' => 'Manager U Ba', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Ko Lay', 'phone' => '09444555666']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_manager_can_access_scenarios_tab(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/pilot-import/scenarios");

        $response->assertOk();
        $response->assertSee('Diamond Stone');
    }

    public function test_manager_can_seed_sample_data_into_store(): void
    {
        config(['app.show_quick_login' => true]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/pilot-import/seed-store", [
                'scenario' => 'diamond-stone-agri',
                'clean_old' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $products = Product::where('store_id', $this->store->id)->get();
        $this->assertCount(100, $products);
        $this->assertGreaterThanOrEqual(15, $products->where('is_featured', true)->count());
        $this->assertGreaterThanOrEqual(20, $products->whereNotNull('old_price')->count());
        $this->assertTrue($products->contains(fn (Product $product) => $product->sale_starts_at?->isFuture()));
        $this->assertTrue($products->contains(fn (Product $product) => $product->sale_starts_at?->isPast()));

        $this->assertTrue($products->every(fn (Product $product) => str_starts_with((string) $product->image_path, "demo-stores/{$this->store->id}/diamond-stone-agri/products/")));
        $this->assertTrue($products->every(fn (Product $product) => Storage::disk('public')->exists($product->image_path)));
        $this->assertTrue(Category::where('store_id', $this->store->id)->get()->every(
            fn (Category $category) => $category->image_path && Storage::disk('public')->exists($category->image_path)
        ));
        $this->assertSame(3, HomeBanner::where('store_id', $this->store->id)->where('page', 'home')->count());
        $this->assertTrue(HomeBanner::where('store_id', $this->store->id)->get()->every(
            fn (HomeBanner $banner) => Storage::disk('public')->exists($banner->image_path)
        ));
    }

    public function test_reseeding_preserves_customer_uploaded_product_image(): void
    {
        config(['app.show_quick_login' => true]);

        $this->actingAs($this->manager)->post("/store/{$this->store->slug}/admin/pilot-import/seed-store", [
            'scenario' => 'general-retail',
        ])->assertRedirect();

        $product = Product::where('store_id', $this->store->id)->firstOrFail();
        Storage::disk('public')->put('products/customer-photo.webp', 'customer image');
        $product->update(['image_path' => 'products/customer-photo.webp']);

        $this->actingAs($this->manager)->post("/store/{$this->store->slug}/admin/pilot-import/seed-store", [
            'scenario' => 'general-retail',
        ])->assertRedirect();

        $this->assertSame('products/customer-photo.webp', $product->fresh()->image_path);
        $this->assertTrue(Storage::disk('public')->exists('products/customer-photo.webp'));
    }

    public function test_manager_can_apply_demo_store_identity_explicitly(): void
    {
        config(['app.show_quick_login' => true]);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/pilot-import/seed-store", [
                'scenario' => 'general-retail',
                'clean_old' => '1',
                'apply_store_identity' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->store->refresh();
        $this->assertSame('ရွှေမြန်မာ မီနီမတ်', $this->store->name);
        $this->assertSame('general_retail', $this->store->business_type);
        $this->assertSame('ရွှေမြန်မာ မီနီမတ်', $this->store->setting->store_name);
        $this->assertCount(100, Product::where('store_id', $this->store->id)->get());
    }

    public function test_seed_does_not_change_store_identity_without_explicit_option(): void
    {
        config(['app.show_quick_login' => true]);

        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/pilot-import/seed-store", [
                'scenario' => 'general-retail',
            ])
            ->assertRedirect();

        $this->store->refresh();
        $this->assertSame('Diamond Stone Agri Test', $this->store->name);
        $this->assertSame('Diamond Stone Agri Test', $this->store->setting->store_name);
    }

    public function test_manager_can_clean_store_test_data(): void
    {
        config(['app.show_quick_login' => true]);

        // Seed first
        $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/pilot-import/seed-store", [
                'scenario' => 'diamond-stone-agri',
            ]);

        $this->assertTrue(Product::where('store_id', $this->store->id)->count() > 0);

        // Create sample order
        $order = \App\Models\Order::create([
            'store_id' => $this->store->id,
            'user_id' => $this->staff->id,
            'order_number' => 'ORD-TEST-001',
            'customer_name' => 'Ko Test',
            'customer_phone' => '09999999999',
            'customer_address' => 'Yangon',
            'pricing_type' => 'retail',
            'total_amount' => 50000,
            'agreed_amount' => 50000,
            'status' => 'pending_contact',
        ]);
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Sample Crop Seed',
            'unit_price' => 50000,
            'quantity' => 1,
            'subtotal' => 50000,
        ]);

        $this->assertSame(1, \App\Models\Order::where('store_id', $this->store->id)->count());

        // Now clean
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/pilot-import/clean-store-data");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(0, Product::where('store_id', $this->store->id)->count());
        $this->assertSame(0, \App\Models\Order::where('store_id', $this->store->id)->count());
        $this->assertSame(0, \App\Models\OrderItem::where('order_id', $order->id)->count());
        $this->assertSame(0, HomeBanner::where('store_id', $this->store->id)->where('image_path', 'like', "demo-stores/{$this->store->id}/%")->count());
        $this->assertFalse(Storage::disk('public')->exists("demo-stores/{$this->store->id}"));
    }

    public function test_manager_can_create_kl_fashion_demo_store_with_master_data(): void
    {
        config(['app.show_quick_login' => true]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/pilot-import/demo-scenarios/kl-fashion");

        $response->assertRedirect('/store/kl-fashion/admin/products');
        $response->assertSessionHas('success');

        $klStore = Store::where('slug', 'kl-fashion')->first();
        $this->assertNotNull($klStore);
        $this->assertSame('KL Fashion & Tailoring', $klStore->name);
        $this->assertSame('fashion', $klStore->business_type);

        // Check products
        $products = Product::where('store_id', $klStore->id)->get();
        $this->assertCount(100, $products);

        // Check tailoring services, fabrics, garments, notions
        $this->assertTrue($products->contains(fn ($p) => str_starts_with($p->sku, 'KL-SRV-')));
        $this->assertTrue($products->contains(fn ($p) => str_starts_with($p->sku, 'KL-FAB-')));
        $this->assertTrue($products->contains(fn ($p) => str_starts_with($p->sku, 'KL-CLO-')));
        $this->assertTrue($products->contains(fn ($p) => str_starts_with($p->sku, 'KL-SEW-')));

        // Check automatic Master Data presets seeding
        $presetsCount = \App\Models\ProductMasterPreset::where('store_id', $klStore->id)->count();
        $this->assertGreaterThan(50, $presetsCount);
        $this->assertSame(7, \App\Models\VariantPreset::where('store_id', $klStore->id)->count());
    }

    public function test_manager_can_seed_kl_fashion_scenario_into_current_store(): void
    {
        config(['app.show_quick_login' => true]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/pilot-import/seed-store", [
                'scenario' => 'kl-fashion',
                'clean_old' => '1',
                'apply_store_identity' => '1',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->store->refresh();
        $this->assertSame('KL Fashion & Tailoring', $this->store->name);
        $this->assertSame('fashion', $this->store->business_type);

        $products = Product::where('store_id', $this->store->id)->get();
        $this->assertCount(100, $products);
    }
}
