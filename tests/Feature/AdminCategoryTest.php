<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
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

    private function createCategory(Store $store, string $name, ?int $parentId = null): Category
    {
        return Category::create([
            'store_id' => $store->id,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(3),
        ]);
    }

    private function createProduct(Store $store, Category $category): Product
    {
        return Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'sku' => 'CAT-' . $category->id . '-' . Str::random(4),
            'name' => 'Product for ' . $category->name,
            'slug' => 'product-' . Str::slug($category->name) . '-' . $category->id,
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'stock_status' => 'in_stock',
        ]);
    }

    private function url(Store $store, string $path = ''): string
    {
        return '/store/' . $store->slug . '/admin/categories' . $path;
    }

    // ---------- Authorization & store isolation ----------

    public function test_manager_can_view_categories_list(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createCategory($store, 'Batteries');
        $this->createCategory($store, 'Chargers');

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSeeText('Batteries');
        $response->assertSeeText('Chargers');
    }

    public function test_authorized_staff_can_view_categories_list(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createCategory($store, 'Screens');

        $response = $this->actingAs($this->userFor($store, 'staff'))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSeeText('Screens');
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

        $this->actingAs($customer)->get($this->url($store))->assertStatus(403);
    }

    public function test_cross_store_category_access_is_rejected(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $categoryB = $this->createCategory($storeB, 'Store B Category');
        $managerA = $this->managerFor($storeA);

        $this->actingAs($managerA)->get($this->url($storeA, "/{$categoryB->id}/edit"))->assertStatus(403);
        $this->actingAs($managerA)->put($this->url($storeA, "/{$categoryB->id}"), ['name' => 'Hijack'])->assertStatus(403);
        $this->actingAs($managerA)->delete($this->url($storeA, "/{$categoryB->id}"))->assertStatus(403);

        $this->assertDatabaseHas('categories', ['id' => $categoryB->id, 'name' => 'Store B Category']);
    }

    // ---------- Create ----------

    public function test_main_category_can_be_created(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => '  Spare Part  ']);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', [
            'store_id' => $store->id,
            'name' => 'Spare Part', // trimmed, display name preserved
            'parent_id' => null,
        ]);
    }

    public function test_sub_category_can_be_created_under_valid_parent(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Spare Part');

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => 'TouchLCD', 'parent_id' => (string) $parent->id]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'store_id' => $store->id,
            'name' => 'TouchLCD',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_cross_store_parent_assignment_is_rejected(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $parentB = $this->createCategory($storeB, 'Store B Parent');

        $response = $this->actingAs($this->managerFor($storeA))
            ->post($this->url($storeA), ['name' => 'Sneaky Sub', 'parent_id' => (string) $parentB->id]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('categories', ['store_id' => $storeA->id, 'name' => 'Sneaky Sub']);
    }

    public function test_parent_must_be_a_main_category(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Main');
        $sub = $this->createCategory($store, 'Sub', $parent->id);

        // A Sub-category can never be a parent — the tree is two levels.
        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => 'Deep Nest', 'parent_id' => (string) $sub->id]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('categories', ['store_id' => $store->id, 'name' => 'Deep Nest']);
    }

    // ---------- Update / the Main Edit fix ----------

    public function test_main_category_can_be_edited_without_parent(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = $this->createCategory($store, 'Old Name');

        // The hidden parent input submits an empty string when type = parent.
        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$category->id}"), [
                'name' => 'New Name',
                'parent_id' => '',
                'description' => 'Updated description',
            ]);

        $response->assertRedirect($this->url($store));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'parent_id' => null,
        ]);
    }

    public function test_main_category_edit_view_renders_parent_select_only_required_for_sub(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = $this->createCategory($store, 'Main Category');

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store, "/{$category->id}/edit"));

        $response->assertStatus(200);
        // The critical fix: the parent select must only be required/enabled
        // for sub-categories so a hidden required select can never block a
        // Main Category edit.
        $response->assertSee(":required=\"type === 'sub'\"", false);
        $response->assertSee(":disabled=\"type !== 'sub'\"", false);
    }

    public function test_sub_category_can_be_edited(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Main');
        $sub = $this->createCategory($store, 'Old Sub', $parent->id);

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$sub->id}"), ['name' => 'New Sub', 'parent_id' => (string) $parent->id]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['id' => $sub->id, 'name' => 'New Sub', 'parent_id' => $parent->id]);
    }

    public function test_sub_category_can_become_parent(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Main');
        $sub = $this->createCategory($store, 'Sub', $parent->id);

        // Promote the sub to top-level (parent_id becomes null).
        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$sub->id}"), ['name' => 'Sub', 'parent_id' => '']);

        $response->assertSessionHasNoErrors();
        $this->assertNull($sub->refresh()->parent_id);
    }

    public function test_empty_parent_can_become_sub(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $mainA = $this->createCategory($store, 'Main A');
        $mainB = $this->createCategory($store, 'Main B');

        // Main A has no children → may become a sub of Main B.
        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$mainA->id}"), ['name' => 'Main A', 'parent_id' => (string) $mainB->id]);

        $response->assertSessionHasNoErrors();
        $this->assertSame($mainB->id, $mainA->refresh()->parent_id);
    }

    public function test_parent_with_children_cannot_become_sub(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $mainA = $this->createCategory($store, 'Main A');
        $child = $this->createCategory($store, 'Child', $mainA->id);
        $mainB = $this->createCategory($store, 'Main B');

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$mainA->id}"), ['name' => 'Main A', 'parent_id' => (string) $mainB->id]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($mainA->refresh()->parent_id);
        $this->assertSame($mainA->id, $child->refresh()->parent_id, 'Child relationships must be preserved.');
    }

    public function test_edit_view_disables_sub_option_for_parent_with_children(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $main = $this->createCategory($store, 'Main With Children');
        $this->createCategory($store, 'Child', $main->id);

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store, "/{$main->id}/edit"));

        $response->assertStatus(200);
        $response->assertSee('<input type="radio" x-model="type" value="sub"', false);
        $response->assertSee('disabled', false);
        $response->assertSee(__('messages.category_convert_blocked'));
    }

    public function test_self_parent_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = $this->createCategory($store, 'Solo');

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$category->id}"), ['name' => 'Solo', 'parent_id' => (string) $category->id]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($category->refresh()->parent_id);
    }

    public function test_ancestor_cycle_via_sub_parent_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $main = $this->createCategory($store, 'Main');
        $sub = $this->createCategory($store, 'Sub', $main->id);

        // Making Main a child of its own Sub would create a cycle (and a
        // third level) — rejected because a Sub can never be a parent.
        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$main->id}"), ['name' => 'Main', 'parent_id' => (string) $sub->id]);

        $response->assertSessionHasErrors('parent_id');
        $this->assertNull($main->refresh()->parent_id);
    }

    // ---------- Uniqueness ----------

    public function test_whitespace_only_name_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))->post($this->url($store), ['name' => '   ']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseMissing('categories', ['store_id' => $store->id]);
    }

    public function test_duplicate_normalized_name_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $this->createCategory($store, 'Accessories');

        foreach (['ACCESSORIES', 'accessories', '  Accessories  '] as $variant) {
            $response = $this->actingAs($manager)->post($this->url($store), ['name' => $variant]);
            $response->assertSessionHasErrors('name');
        }

        $this->assertSame(1, Category::where('store_id', $store->id)->count());
    }

    public function test_duplicate_across_main_and_sub_is_rejected(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $main = $this->createCategory($store, 'Cases');

        // Same display name under a parent must also be rejected (global per store).
        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => 'cases', 'parent_id' => (string) $main->id]);

        $response->assertSessionHasErrors('name');
    }

    public function test_same_name_in_different_store_is_allowed(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $this->createCategory($storeA, 'Chargers');

        $response = $this->actingAs($this->managerFor($storeB))
            ->post($this->url($storeB), ['name' => 'chargers']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['store_id' => $storeB->id, 'name' => 'chargers']);
    }

    public function test_update_ignores_itself_when_checking_uniqueness(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = $this->createCategory($store, 'Screens');

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$category->id}"), ['name' => 'screens']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'screens']);
    }

    public function test_slug_collision_gets_a_unique_suffix(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        // First record claims the 'hello-world' slug.
        Category::create(['store_id' => $store->id, 'name' => 'Hello World', 'slug' => 'hello-world']);

        // A different normalized display name ('hello-world' != 'hello world')
        // that derives the same slug must be suffixed, not rejected.
        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), ['name' => 'Hello-World']);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', [
            'store_id' => $store->id,
            'name' => 'Hello-World',
            'slug' => 'hello-world-2',
        ]);
    }

    // ---------- Image lifecycle ----------

    public function test_valid_image_upload_is_optimized_and_stored(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))
            ->post($this->url($store), [
                'name' => 'With Image',
                'image' => UploadedFile::fake()->image('category.png', 300, 300),
            ]);

        $response->assertRedirect();
        $category = Category::where('store_id', $store->id)->firstOrFail();
        $this->assertNotNull($category->image_path);
        $this->assertStringStartsWith('categories/', $category->image_path);
        $this->assertTrue(Storage::disk('public')->exists($category->image_path));
    }

    public function test_image_can_be_replaced_and_old_file_deleted(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $oldPath = UploadedFile::fake()->image('one.png', 100, 100)->store('categories', 'public');
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Replace Me',
            'slug' => 'replace-me-' . Str::random(3),
            'image_path' => $oldPath,
        ]);

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$category->id}"), [
                'name' => 'Replace Me',
                'image' => UploadedFile::fake()->image('two.png', 120, 120),
            ]);

        $response->assertRedirect();
        $category->refresh();
        $this->assertNotNull($category->image_path);
        $this->assertNotSame($oldPath, $category->image_path);
        $this->assertTrue(Storage::disk('public')->exists($category->image_path));
        $this->assertFalse(Storage::disk('public')->exists($oldPath), 'Replaced image file must be deleted.');
    }

    public function test_image_can_be_removed_and_file_deleted(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('img.png', 100, 100)->store('categories', 'public');
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Remove Me',
            'slug' => 'remove-me-' . Str::random(3),
            'image_path' => $path,
        ]);

        $response = $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$category->id}"), ['name' => 'Remove Me', 'remove_image' => '1']);

        $response->assertRedirect();
        $this->assertNull($category->refresh()->image_path);
        $this->assertFalse(Storage::disk('public')->exists($path), 'Removed image file must be deleted.');
    }

    public function test_invalid_and_oversized_image_are_rejected(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);

        $this->actingAs($manager)
            ->post($this->url($store), ['name' => 'Bad Type', 'image' => UploadedFile::fake()->create('x.txt', 5, 'text/plain')])
            ->assertSessionHasErrors('image');

        // 11 MB — above the 10 MB backend limit.
        $this->actingAs($manager)
            ->post($this->url($store), ['name' => 'Too Big', 'image' => UploadedFile::fake()->create('big.png', 11264, 'image/png')])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('categories', ['store_id' => $store->id, 'name' => 'Bad Type']);
        $this->assertDatabaseMissing('categories', ['store_id' => $store->id, 'name' => 'Too Big']);
    }

    public function test_failed_validation_preserves_current_image(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('img.png', 100, 100)->store('categories', 'public');
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Keep Image',
            'slug' => 'keep-image-' . Str::random(3),
            'image_path' => $path,
        ]);

        $this->actingAs($this->managerFor($store))
            ->put($this->url($store, "/{$category->id}"), ['name' => '', 'image' => UploadedFile::fake()->create('x.txt', 5, 'text/plain')])
            ->assertSessionHasErrors(['name', 'image']);

        $this->assertSame($path, $category->refresh()->image_path);
        $this->assertTrue(Storage::disk('public')->exists($path), 'Current image must survive a failed update.');
    }

    // ---------- Deletion safety ----------

    public function test_unused_leaf_category_can_be_deleted(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = $this->createCategory($store, 'Unused Leaf');

        $response = $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$category->id}"));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = $this->createCategory($store, 'Used Category');
        $product = $this->createProduct($store, $category);

        $response = $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$category->id}"));

        $response->assertRedirect();
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertSame($category->id, $product->refresh()->category_id, 'Product category assignment must be preserved.');
    }

    public function test_parent_with_children_cannot_be_deleted(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Parent');
        $child = $this->createCategory($store, 'Child', $parent->id);

        $response = $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$parent->id}"));

        $response->assertRedirect();
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
        $this->assertSame($parent->id, $child->refresh()->parent_id, 'Children must never be silently promoted.');
    }

    public function test_blocked_deletion_preserves_image_file(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('img.png', 100, 100)->store('categories', 'public');
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Used With Image',
            'slug' => 'used-image-' . Str::random(3),
            'image_path' => $path,
        ]);
        $this->createProduct($store, $category);

        $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$category->id}"))
            ->assertSessionHasErrors('category');

        $this->assertTrue(Storage::disk('public')->exists($path), 'Image must survive a blocked deletion.');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'image_path' => $path]);
    }

    public function test_deleting_unused_category_deletes_its_image(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $path = UploadedFile::fake()->image('img.png', 100, 100)->store('categories', 'public');
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Unused With Image',
            'slug' => 'unused-image-' . Str::random(3),
            'image_path' => $path,
        ]);

        $this->actingAs($this->managerFor($store))
            ->delete($this->url($store, "/{$category->id}"));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertFalse(Storage::disk('public')->exists($path), 'Image must be deleted with an unused category.');
    }

    // ---------- Search / filter correctness ----------

    public function test_search_by_parent_returns_accurate_child_counts(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Spare Part');
        $childA = $this->createCategory($store, 'TouchLCD', $parent->id);
        $childB = $this->createCategory($store, 'OLED', $parent->id);
        $this->createProduct($store, $childA);
        $this->createProduct($store, $childB);

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store, '?search=Spare Part'));

        $response->assertStatus(200);
        // Both children are included with the matching parent — no false zeros.
        $response->assertSeeText('TouchLCD');
        $response->assertSeeText('OLED');
        $response->assertSeeText(__('messages.category_sub_count_items', ['count' => 2, 'items' => 2]));
    }

    public function test_search_by_sub_includes_its_parent(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Spare Part');
        $touch = $this->createCategory($store, 'TouchLCD', $parent->id);
        $this->createCategory($store, 'OLED', $parent->id);

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store, '?search=TouchLCD'));

        $response->assertStatus(200);
        // The parent container is included so the hierarchy reads correctly.
        $response->assertSeeText('Spare Part');
        $response->assertSeeText('TouchLCD');
        $response->assertDontSeeText('OLED');
    }

    public function test_image_filter_returns_accurate_hierarchy(): void
    {
        Storage::fake('public');
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Imaged Parent');
        $child = $this->createCategory($store, 'Plain Child', $parent->id);
        $path = UploadedFile::fake()->image('img.png', 100, 100)->store('categories', 'public');
        Category::where('id', $parent->id)->update(['image_path' => $path]);

        $response = $this->actingAs($this->managerFor($store))
            ->get($this->url($store, '?has_image=with'));

        $response->assertStatus(200);
        $response->assertSeeText('Imaged Parent');
        // Matched parent brings its children so the section shows true counts.
        $response->assertSeeText('Plain Child');
    }

    public function test_clear_filters_restores_complete_tree(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $manager = $this->managerFor($store);
        $this->createCategory($store, 'Alpha');
        $this->createCategory($store, 'Beta');

        $filtered = $this->actingAs($manager)->get($this->url($store, '?search=Alpha'));
        $filtered->assertDontSeeText('Beta');

        $cleared = $this->actingAs($manager)->get($this->url($store));
        $cleared->assertSeeText('Alpha');
        $cleared->assertSeeText('Beta');
    }

    // ---------- Toolbar / Add Sub UX ----------

    public function test_empty_sort_control_is_not_rendered(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createCategory($store, 'Alpha');

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertDontSee('<select name="sort"', false);
    }

    public function test_add_sub_form_renders_immediately_under_parent_header(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Spare Part');

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSee('x-ref="addSubName"', false);
        $response->assertSee('value="' . $parent->id . '"', false); // hidden parent_id
        $response->assertSee('addSubFor === ' . $parent->id, false); // one-at-a-time binding
    }

    public function test_validation_errors_reopen_the_correct_add_sub_form(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $parent = $this->createCategory($store, 'Spare Part');
        $manager = $this->managerFor($store);

        // Failed sub-category create (icon too long) keeps old input in the
        // session; the Referer makes back() return to the Categories page.
        $res = $this->actingAs($manager)->post($this->url($store), [
            'name' => 'My Sub',
            'parent_id' => (string) $parent->id,
            'icon' => str_repeat('a', 12),
        ], ['Referer' => $this->url($store)]);
        $res->assertSessionHasErrors('icon');

        $page = $this->actingAs($manager)->get($res->headers->get('Location') ?? $this->url($store));
        $page->assertStatus(200);
        // The Add Sub form for the right parent reopens with its input preserved.
        $page->assertSee('addSubFor: ' . $parent->id, false);
        $page->assertSee('value="My Sub"', false);
    }

    public function test_main_category_add_tab_opens_by_default_when_store_is_empty(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSee("tab: 'add'", false);
    }

    public function test_main_category_add_tab_collapses_by_default_when_categories_exist(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->createCategory($store, 'Existing');

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSee("tab: 'list'", false);
    }

    public function test_products_filter_link_renders_for_used_category(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $category = $this->createCategory($store, 'Used Category');
        $this->createProduct($store, $category);

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSee('admin/products?category_id=' . $category->id, false);
        $response->assertDontSee('data-id="' . $category->id . '"', false);
    }

    public function test_delete_button_renders_only_for_unused_leaf_categories(): void
    {
        $store = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $unused = $this->createCategory($store, 'Unused Leaf');
        $used = $this->createCategory($store, 'Used');
        $this->createProduct($store, $used);
        $parent = $this->createCategory($store, 'Parent With Child');
        $this->createCategory($store, 'Child', $parent->id);

        $response = $this->actingAs($this->managerFor($store))->get($this->url($store));

        $response->assertStatus(200);
        $response->assertSee('data-id="' . $unused->id . '"', false);
        $response->assertDontSee('data-id="' . $used->id . '"', false);
        $response->assertDontSee('data-id="' . $parent->id . '"', false);
    }
}
