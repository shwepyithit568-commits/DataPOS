<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBrandTest extends TestCase
{
    use RefreshDatabase;

    private function userFor(Store $store, string $role): User
    {
        $user = User::create([
            'name' => "User {$role} {$store->slug}",
            'phone' => '09' . str_pad((string) $store->id . strlen($role), 9, '9'),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $user->stores()->attach($store->id, [
            'role' => $role,
            'status' => 'active',
        ]);

        return $user;
    }

    private function managerFor(Store $store): User
    {
        return $this->userFor($store, 'store_manager');
    }

    private function createBrand(Store $store, string $name, ?string $slug = null): Brand
    {
        return Brand::create([
            'store_id' => $store->id,
            'name' => $name,
            'slug' => $slug ?? \Illuminate\Support\Str::slug($name),
        ]);
    }

    private function createProduct(Store $store, Brand $brand): Product
    {
        return Product::create([
            'store_id' => $store->id,
            'brand_id' => $brand->id,
            'sku' => 'BR-' . $brand->id . '-' . \Illuminate\Support\Str::random(4),
            'name' => 'Product for ' . $brand->name,
            'slug' => 'product-' . $brand->slug . '-' . $brand->id,
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'in_stock',
        ]);
    }

    private function url(Store $store, string $path = ''): string
    {
        return '/store/' . $store->slug . '/admin/brands' . $path;
    }

    // ---------- Authorization & store isolation ----------

    public function test_manager_can_view_brands_list(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createBrand($store, 'Samsung');
        $this->createBrand($store, 'Apple');

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSeeText('Samsung');
        $response->assertSeeText('Apple');
    }

    public function test_authorized_staff_can_view_brands_list(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createBrand($store, 'Samsung');

        $response = $this->actingAs($this->userFor($store, 'staff'))
            ->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSeeText('Samsung');
    }

    public function test_unauthorized_role_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $customer = User::create([
            'name' => 'Plain Customer',
            'phone' => '09111111119',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($customer)
            ->get($this->url($store))
            ->assertStatus(403);
    }

    public function test_cross_store_brand_access_is_rejected(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $brandB = $this->createBrand($storeB, 'Store B Brand');
        $managerA = $this->managerFor($storeA);

        // Edit page, update and delete of another store's brand are all blocked.
        $this->actingAs($managerA)->get($this->url($storeA, "/{$brandB->id}/edit"))->assertStatus(403);
        $this->actingAs($managerA)->put($this->url($storeA, "/{$brandB->id}"), ['name' => 'Hijack'])->assertStatus(403);
        $this->actingAs($managerA)->delete($this->url($storeA, "/{$brandB->id}"))->assertStatus(403);

        $this->assertDatabaseHas('brands', ['id' => $brandB->id, 'name' => 'Store B Brand']);
    }

    // ---------- Create ----------

    public function test_brand_can_be_created_without_logo(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => '  Xiaomi  ']);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('brands', [
            'store_id' => $store->id,
            'name' => 'Xiaomi', // trimmed, original display preserved
            'slug' => 'xiaomi',
        ]);
    }

    public function test_brand_can_be_created_with_valid_logo(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), [
                'name' => 'Sony',
                'logo' => UploadedFile::fake()->image('logo.png', 120, 120),
            ]);

        $response->assertRedirect();
        $brand = Brand::where('store_id', $store->id)->firstOrFail();
        $this->assertNotNull($brand->logo_path);
        $this->assertStringStartsWith('brands/', $brand->logo_path);
        $this->assertTrue(Storage::disk('public')->exists($brand->logo_path));
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), [
                'name' => 'Sony',
                'logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('logo');
        $this->assertDatabaseMissing('brands', ['store_id' => $store->id, 'name' => 'Sony']);
    }

    public function test_oversized_image_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        // 11 MB — above the 10 MB backend limit.
        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), [
                'name' => 'Sony',
                'logo' => UploadedFile::fake()->create('logo.png', 11264, 'image/png'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('logo');
        $this->assertDatabaseMissing('brands', ['store_id' => $store->id, 'name' => 'Sony']);
    }

    public function test_empty_name_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => '']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('brands', ['store_id' => $store->id]);
    }

    public function test_whitespace_only_name_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => '   ']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('brands', ['store_id' => $store->id]);
    }

    // ---------- Uniqueness ----------

    public function test_duplicate_normalized_name_in_same_store_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $this->createBrand($store, 'Samsung');

        // Case-only variant must be rejected (Samsung / SAMSUNG / samsung).
        foreach (['SAMSUNG', 'samsung', '  Samsung  '] as $variant) {
            $response = $this->actingAs($manager)
                ->post($this->url($store), ['name' => $variant]);

            $response->assertSessionHasErrors('name');
        }

        $this->assertSame(1, Brand::where('store_id', $store->id)->count());
    }

    public function test_same_brand_name_in_different_store_is_allowed(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $this->createBrand($storeA, 'Apple');

        $response = $this->actingAs($this->managerFor($storeB))
            ->post($this->url($storeB), ['name' => 'apple']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('brands', ['store_id' => $storeB->id, 'name' => 'apple']);
    }

    public function test_update_ignores_itself_when_checking_uniqueness(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $brand = $this->createBrand($store, 'Samsung');

        // Renaming to the same normalized name (case-only change) must pass.
        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$brand->id}"), ['name' => 'samsung']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'samsung']);
    }

    public function test_slug_collision_gets_a_unique_suffix(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createBrand($store, 'Hello World'); // slug: hello-world

        // Different name, same derived slug → must be suffixed, not fail.
        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => 'Hello-World']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('brands', ['store_id' => $store->id, 'slug' => 'hello-world-2']);
    }

    // ---------- Update / logo lifecycle ----------

    public function test_brand_can_be_renamed(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $brand = $this->createBrand($store, 'Sony');

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$brand->id}"), ['name' => 'Sony Ericsson']);

        $response->assertRedirect();
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'Sony Ericsson', 'slug' => 'sony-ericsson']);
    }

    public function test_brand_logo_can_be_replaced_and_old_file_deleted(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $first = UploadedFile::fake()->image('one.png', 100, 100);
        $oldPath = $first->store('brands', 'public');
        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => 'Sony',
            'slug' => 'sony',
            'logo_path' => $oldPath,
        ]);

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$brand->id}"), [
                'name' => 'Sony',
                'logo' => UploadedFile::fake()->image('two.png', 120, 120),
            ]);

        $response->assertRedirect();
        $brand->refresh();
        $this->assertNotNull($brand->logo_path);
        $this->assertNotSame($oldPath, $brand->logo_path);
        $this->assertTrue(Storage::disk('public')->exists($brand->logo_path));
        $this->assertFalse(Storage::disk('public')->exists($oldPath), 'Replaced logo file must be deleted.');
    }

    public function test_brand_logo_can_be_removed_and_file_deleted(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('logo.png', 100, 100)->store('brands', 'public');
        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => 'Sony',
            'slug' => 'sony',
            'logo_path' => $path,
        ]);

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$brand->id}"), ['name' => 'Sony', 'remove_logo' => '1']);

        $response->assertRedirect();
        $this->assertNull($brand->refresh()->logo_path);
        $this->assertFalse(Storage::disk('public')->exists($path), 'Removed logo file must be deleted.');
    }

    public function test_failed_validation_does_not_delete_current_logo(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('logo.png', 100, 100)->store('brands', 'public');
        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => 'Sony',
            'slug' => 'sony',
            'logo_path' => $path,
        ]);

        // Invalid name + bad file type → validation fails before any file ops.
        $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$brand->id}"), ['name' => '', 'logo' => UploadedFile::fake()->create('x.txt', 5, 'text/plain')])
            ->assertSessionHasErrors(['name', 'logo']);

        $this->assertSame($path, $brand->refresh()->logo_path);
        $this->assertTrue(Storage::disk('public')->exists($path), 'Current logo must survive a failed update.');
    }

    // ---------- Deletion safety ----------

    public function test_unused_brand_can_be_deleted(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $brand = $this->createBrand($store, 'Unused');

        $response = $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$brand->id}"));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_used_brand_cannot_be_deleted(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $brand = $this->createBrand($store, 'Used Brand');
        $product = $this->createProduct($store, $brand);

        $response = $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$brand->id}"));

        $response->assertRedirect();
        $response->assertSessionHasErrors('brand');
        $this->assertDatabaseHas('brands', ['id' => $brand->id]);
        $this->assertSame($brand->id, $product->refresh()->brand_id, 'Product brand assignment must be preserved.');
    }

    public function test_blocked_deletion_preserves_logo_file(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('logo.png', 100, 100)->store('brands', 'public');
        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => 'Used Brand',
            'slug' => 'used-brand',
            'logo_path' => $path,
        ]);
        $this->createProduct($store, $brand);

        $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$brand->id}"))
            ->assertSessionHasErrors('brand');

        $this->assertTrue(Storage::disk('public')->exists($path), 'Logo must survive a blocked deletion.');
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'logo_path' => $path]);
    }

    public function test_deleting_unused_brand_deletes_its_logo(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('logo.png', 100, 100)->store('brands', 'public');
        $brand = Brand::create([
            'store_id' => $store->id,
            'name' => 'Unused',
            'slug' => 'unused',
            'logo_path' => $path,
        ]);

        $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$brand->id}"));

        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
        $this->assertFalse(Storage::disk('public')->exists($path), 'Logo must be deleted with an unused brand.');
    }

    // ---------- Search / filter / sort / pagination ----------

    public function test_search_by_name(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createBrand($store, 'Samsung Galaxy');
        $this->createBrand($store, 'Apple iPhone');

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store, '?search=Samsung'));

        $response->assertStatus(200);
        $response->assertSeeText('Samsung Galaxy');
        $response->assertDontSeeText('Apple iPhone');
    }

    public function test_search_by_slug(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createBrand($store, 'Samsung Galaxy', 'samsung-galaxy');
        $this->createBrand($store, 'Apple', 'apple');

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store, '?search=galaxy'));

        $response->assertStatus(200);
        $response->assertSeeText('Samsung Galaxy');
        $response->assertDontSeeText('Apple');
    }

    public function test_logo_filter_with_and_without(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        Brand::create([
            'store_id' => $store->id,
            'name' => 'With Logo',
            'slug' => 'with-logo',
            'logo_path' => UploadedFile::fake()->image('l.png', 100, 100)->store('brands', 'public'),
        ]);
        $this->createBrand($store, 'No Logo');

        $with = $this->actingAs($manager)->get($this->url($store, '?has_logo=with'));
        $with->assertStatus(200);
        $with->assertSeeText('With Logo');
        $with->assertDontSeeText('No Logo');

        $without = $this->actingAs($manager)->get($this->url($store, '?has_logo=without'));
        $without->assertStatus(200);
        $without->assertSeeText('No Logo');
        $without->assertDontSeeText('With Logo');
    }

    public function test_sorting_modes(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $this->createBrand($store, 'Banana');
        $this->createBrand($store, 'Apple');
        $this->createBrand($store, 'Cherry');

        $asc = $this->actingAs($manager)->get($this->url($store, '?sort=name_asc'));
        $asc->assertSeeInOrder(['Apple', 'Banana', 'Cherry']);

        $desc = $this->actingAs($manager)->get($this->url($store, '?sort=name_desc'));
        $desc->assertSeeInOrder(['Cherry', 'Banana', 'Apple']);
    }

    public function test_pagination_and_safe_per_page_limits(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        for ($i = 1; $i <= 26; $i++) {
            $this->createBrand($store, 'Brand ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        // name_desc gives deterministic ordering (Brand 26 → Brand 01).
        // Default 25 per page → page 1 shows Brand 26 … Brand 02, not Brand 01.
        $page1 = $this->actingAs($manager)->get($this->url($store, '?sort=name_desc'));
        $page1->assertStatus(200);
        $page1->assertSeeText('Brand 26');
        $page1->assertDontSeeText('Brand 01');

        // per_page=all must NOT fetch everything — it is capped back to 25.
        $safe = $this->actingAs($manager)->get($this->url($store, '?per_page=all&sort=name_desc'));
        $safe->assertStatus(200);
        $safe->assertSeeText('Brand 26');
        $safe->assertDontSeeText('Brand 01');

        // Explicit whitelisted value works — everything fits on one page.
        $per50 = $this->actingAs($manager)->get($this->url($store, '?per_page=50&sort=name_desc'));
        $per50->assertSeeText('Brand 01');
    }

    public function test_query_parameters_persist_in_pagination_links(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        for ($i = 1; $i <= 30; $i++) {
            $this->createBrand($store, "Brand {$i}");
        }

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store, '?search=Brand&sort=name_asc&per_page=25'));

        $response->assertStatus(200);
        $response->assertSee('sort=name_asc', false);
        $response->assertSee('per_page=25', false);
        $response->assertSee('search=Brand', false);
    }

    public function test_products_filter_link_renders_for_used_brand(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $brand = $this->createBrand($store, 'Used Brand');
        $this->createProduct($store, $brand);

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSee('admin/products?brand_id=' . $brand->id, false);
        // No delete affordance for a used brand — only the products link.
        $response->assertDontSee('data-id="' . $brand->id . '"', false);
    }

    public function test_delete_button_renders_only_for_unused_brands(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $unused = $this->createBrand($store, 'Unused Brand');
        $used = $this->createBrand($store, 'Used Brand');
        $this->createProduct($store, $used);

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSee('data-id="' . $unused->id . '"', false);
        $response->assertDontSee('data-id="' . $used->id . '"', false);
    }
}
