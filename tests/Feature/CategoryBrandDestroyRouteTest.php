<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryBrandDestroyRouteTest extends TestCase
{
    use RefreshDatabase;

    private function managerFor(Store $store): User
    {
        $manager = User::create([
            'name' => "Manager {$store->slug}",
            'phone' => '09' . str_pad((string) $store->id, 9, '1'),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $manager->stores()->attach($store->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);

        return $manager;
    }

    public function test_manager_can_delete_unused_category_with_store_slug_route_parameter(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Unused Category',
            'slug' => 'unused-category',
        ]);

        $response = $this->actingAs($manager)
            ->delete("/store/{$store->slug}/admin/categories/{$category->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_manager_can_delete_unused_brand_with_store_slug_route_parameter(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => 'Unused Brand',
            'slug' => 'unused-brand',
        ]);

        $response = $this->actingAs($manager)
            ->delete("/store/{$store->slug}/admin/brands/{$brand->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_referenced_category_deletion_is_blocked_and_preserves_product_reference(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Referenced Category',
            'slug' => 'referenced-category',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'sku' => 'REF-CAT-001',
            'name' => 'Referenced Category Product',
            'slug' => 'referenced-category-product',
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'in_stock',
        ]);

        // Safe default: a category assigned to products must NOT be deleted
        // (deleting it would silently null every product's category_id).
        $response = $this->actingAs($manager)
            ->delete("/store/{$store->slug}/admin/categories/{$category->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertSame($category->id, $product->refresh()->category_id);
    }

    public function test_parent_category_deletion_is_blocked_and_preserves_children(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $parent = Category::create([
            'store_id' => $store->id,
            'name' => 'Parent With Children',
            'slug' => 'parent-with-children',
        ]);
        $child = Category::create([
            'store_id' => $store->id,
            'parent_id' => $parent->id,
            'name' => 'Child Category',
            'slug' => 'child-category',
        ]);

        // Safe default: a parent with Sub-categories must not be deleted —
        // children must never be silently promoted to top-level.
        $response = $this->actingAs($manager)
            ->delete("/store/{$store->slug}/admin/categories/{$parent->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
        $this->assertSame($parent->id, $child->refresh()->parent_id);
    }

    public function test_referenced_brand_deletion_is_blocked_and_preserves_product_reference(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => 'Referenced Brand',
            'slug' => 'referenced-brand',
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'brand_id' => $brand->id,
            'sku' => 'REF-BRAND-001',
            'name' => 'Referenced Brand Product',
            'slug' => 'referenced-brand-product',
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'in_stock',
        ]);

        // Safe default: a brand still assigned to products must NOT be deleted
        // (deleting it would silently null every product's brand_id).
        $response = $this->actingAs($manager)
            ->delete("/store/{$store->slug}/admin/brands/{$brand->id}");

        $response->assertRedirect();
        $response->assertSessionHasErrors('brand');
        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
        $this->assertSame($brand->id, $product->refresh()->brand_id);
    }

    public function test_cross_store_category_and_brand_delete_returns_403(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $managerA = $this->managerFor($storeA);
        $categoryB = Category::create([
            'store_id' => $storeB->id,
            'name' => 'Store B Category',
            'slug' => 'store-b-category',
        ]);
        $brandB = Brand::create([
            'store_id' => $storeB->id,
            'name' => 'Store B Brand',
            'slug' => 'store-b-brand',
        ]);

        $this->actingAs($managerA)
            ->delete("/store/{$storeA->slug}/admin/categories/{$categoryB->id}")
            ->assertStatus(403);

        $this->actingAs($managerA)
            ->delete("/store/{$storeA->slug}/admin/brands/{$brandB->id}")
            ->assertStatus(403);
    }
}
