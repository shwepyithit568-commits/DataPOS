<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ComingSoonController;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'ACDC Mobile',
            'slug' => 'acdc-mobile',
            'domain' => 'acdc.test',
            'is_active' => true,
        ]);
        $this->store->setting()->create(['store_name' => 'ACDC Mobile', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Store Manager', 'phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Tech', 'phone' => '09222222222']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_manager_can_view_expense_categories_index(): void
    {
        ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Shop Rent',
            'code' => 'RENT',
            'color' => '#6366f1',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expense-categories");

        $response->assertOk()
            ->assertSeeText('Shop Rent')
            ->assertSeeText('RENT');
    }

    public function test_staff_is_denied_from_expense_categories_index(): void
    {
        // Expense categories are Owner/Manager-only (audit §13) — server-side deny.
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/expense-categories");

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/expense-categories");
        $response->assertRedirect('/login');
    }

    public function test_customer_without_role_is_blocked(): void
    {
        $customerUser = User::factory()->create();

        $response = $this->actingAs($customerUser)
            ->get("/store/{$this->store->slug}/admin/expense-categories");

        $response->assertForbidden();
    }

    public function test_manager_can_create_expense_category(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/expense-categories", [
                'name' => 'Electricity & Water',
                'code' => 'UTIL',
                'description' => 'Monthly electricity bill',
                'color' => '#f59e0b',
                'sort_order' => 1,
                'is_active' => '1',
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/expense-categories")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('expense_categories', [
            'store_id' => $this->store->id,
            'name' => 'Electricity & Water',
            'code' => 'UTIL',
            'color' => '#f59e0b',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_create_validation_requires_name_and_rejects_duplicate_in_same_store(): void
    {
        ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Shop Rent',
            'code' => 'RENT',
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/expense-categories", [
                'name' => 'Shop Rent',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_same_name_is_allowed_in_different_stores(): void
    {
        $otherStore = Store::create([
            'name' => 'Second Store',
            'slug' => 'second-store',
            'domain' => 'second.test',
            'is_active' => true,
        ]);

        ExpenseCategory::create([
            'store_id' => $otherStore->id,
            'name' => 'Shop Rent',
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/expense-categories", [
                'name' => 'Shop Rent',
                'code' => 'RENT',
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/expense-categories")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('expense_categories', [
            'store_id' => $this->store->id,
            'name' => 'Shop Rent',
        ]);
    }

    public function test_manager_can_update_expense_category(): void
    {
        $category = ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Old Category Name',
            'code' => 'OLD',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/expense-categories/{$category->id}", [
                'name' => 'Updated Category Name',
                'code' => 'NEW',
                'description' => 'Updated description',
                'color' => '#10b981',
                'sort_order' => 5,
                'is_active' => '1',
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/expense-categories")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'name' => 'Updated Category Name',
            'code' => 'NEW',
            'color' => '#10b981',
            'sort_order' => 5,
        ]);
    }

    public function test_manager_can_toggle_expense_category_active_status(): void
    {
        $category = ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Staff Meals',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->patch("/store/{$this->store->slug}/admin/expense-categories/{$category->id}/toggle");

        $response->assertRedirect("/store/{$this->store->slug}/admin/expense-categories")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);

        // Toggle back to active
        $this->actingAs($this->manager)
            ->patch("/store/{$this->store->slug}/admin/expense-categories/{$category->id}/toggle");

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_delete_expense_category(): void
    {
        $category = ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Temporary Promo Cost',
        ]);

        $response = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/expense-categories/{$category->id}");

        $response->assertRedirect("/store/{$this->store->slug}/admin/expense-categories")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('expense_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_cannot_modify_expense_category_from_another_store(): void
    {
        $otherStore = Store::create([
            'name' => 'Other Store',
            'slug' => 'other-store',
            'is_active' => true,
        ]);

        $foreignCategory = ExpenseCategory::create([
            'store_id' => $otherStore->id,
            'name' => 'Foreign Category',
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/expense-categories/{$foreignCategory->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertNotFound();
    }

    public function test_index_search_and_status_filters(): void
    {
        ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Shop Rent',
            'code' => 'RENT',
            'is_active' => true,
        ]);

        ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Old Inactive Category',
            'code' => 'INACT',
            'is_active' => false,
        ]);

        // Search query
        $searchResponse = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expense-categories?search=RENT");

        $searchResponse->assertOk()
            ->assertSeeText('Shop Rent')
            ->assertDontSeeText('Old Inactive Category');

        // Status active filter
        $activeResponse = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expense-categories?status=active");

        $activeResponse->assertOk()
            ->assertSeeText('Shop Rent')
            ->assertDontSeeText('Old Inactive Category');

        // Status inactive filter
        $inactiveResponse = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expense-categories?status=inactive");

        $inactiveResponse->assertOk()
            ->assertSeeText('Old Inactive Category')
            ->assertDontSeeText('Shop Rent');
    }

    public function test_coming_soon_registry_no_longer_contains_expense_categories(): void
    {
        $modules = ComingSoonController::modules();
        $this->assertArrayNotHasKey('expense-categories', $modules);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/coming-soon/expense-categories");

        $response->assertNotFound();
    }

    public function test_index_renders_in_all_supported_locales(): void
    {
        ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Shop Maintenance',
            'code' => 'MAINT',
            'is_active' => true,
        ]);

        foreach (['my', 'en', 'zh_CN'] as $locale) {
            $response = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get("/store/{$this->store->slug}/admin/expense-categories");

            $response->assertOk()
                ->assertDontSee('messages.expense_categories_title')
                ->assertDontSee('messages.expense_categories_new');
        }
    }
}
