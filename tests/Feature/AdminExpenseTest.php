<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ComingSoonController;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\Expense;
use App\POS\Models\ExpenseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected ExpenseCategory $category;

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

        $this->category = ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Shop Rent',
            'code' => 'RENT',
            'color' => '#6366f1',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_view_expenses_index(): void
    {
        Expense::create([
            'store_id' => $this->store->id,
            'expense_category_id' => $this->category->id,
            'expense_number' => 'EXP-20260823-0001',
            'title' => 'August Shop Rent',
            'amount' => 450000.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'paid_to' => 'U Ba (Landlord)',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expenses");

        $response->assertOk()
            ->assertSeeText('EXP-20260823-0001')
            ->assertSeeText('August Shop Rent')
            ->assertSeeText('450,000');
    }

    public function test_staff_can_view_expenses_index(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/expenses");

        $response->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get("/store/{$this->store->slug}/admin/expenses");
        $response->assertRedirect('/login');
    }

    public function test_customer_without_role_is_blocked(): void
    {
        $customerUser = User::factory()->create();

        $response = $this->actingAs($customerUser)
            ->get("/store/{$this->store->slug}/admin/expenses");

        $response->assertForbidden();
    }

    public function test_manager_can_create_expense(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/expenses", [
                'title' => 'Monthly Electricity Bill',
                'amount' => '125000',
                'expense_date' => now()->toDateString(),
                'expense_category_id' => $this->category->id,
                'payment_method' => 'kpay',
                'paid_to' => 'YESC',
                'reference_no' => 'BILL-883921',
                'notes' => 'Paid via KPay',
                'attachment' => $file,
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/expenses")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'store_id' => $this->store->id,
            'title' => 'Monthly Electricity Bill',
            'amount' => 125000.00,
            'payment_method' => 'kpay',
            'paid_to' => 'YESC',
            'reference_no' => 'BILL-883921',
        ]);

        $createdExpense = Expense::where('store_id', $this->store->id)->first();
        $this->assertNotNull($createdExpense->expense_number);
        $this->assertNotNull($createdExpense->attachment_path);
        Storage::disk('public')->assertExists($createdExpense->attachment_path);
    }

    public function test_create_validation_requires_title_amount_and_date(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/expenses", [
                'title' => '',
                'amount' => '-10',
                'expense_date' => '',
            ]);

        $response->assertSessionHasErrors(['title', 'amount', 'expense_date']);
    }

    public function test_manager_can_update_expense(): void
    {
        $expense = Expense::create([
            'store_id' => $this->store->id,
            'expense_number' => 'EXP-20260823-0002',
            'title' => 'Original Title',
            'amount' => 50000.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/expenses/{$expense->id}", [
                'title' => 'Updated Expense Title',
                'amount' => '65000',
                'expense_date' => now()->toDateString(),
                'expense_category_id' => $this->category->id,
                'payment_method' => 'wave',
                'paid_to' => 'Delivery Guy',
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/expenses")
            ->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Updated Expense Title',
            'amount' => 65000.00,
            'payment_method' => 'wave',
            'paid_to' => 'Delivery Guy',
        ]);
    }

    public function test_manager_can_delete_expense(): void
    {
        $expense = Expense::create([
            'store_id' => $this->store->id,
            'expense_number' => 'EXP-20260823-0003',
            'title' => 'Water Bottle Delivery',
            'amount' => 15000.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/expenses/{$expense->id}");

        $response->assertRedirect("/store/{$this->store->slug}/admin/expenses")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    }

    public function test_cannot_modify_expense_from_another_store(): void
    {
        $otherStore = Store::create([
            'name' => 'Other Store',
            'slug' => 'other-store',
            'is_active' => true,
        ]);

        $foreignExpense = Expense::create([
            'store_id' => $otherStore->id,
            'expense_number' => 'EXP-FOREIGN-01',
            'title' => 'Foreign Expense',
            'amount' => 9999.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/expenses/{$foreignExpense->id}", [
                'title' => 'Hacked Name',
                'amount' => '100',
                'expense_date' => now()->toDateString(),
                'payment_method' => 'cash',
            ]);

        $response->assertNotFound();
    }

    public function test_index_filters_by_search_category_payment_and_date(): void
    {
        $cat2 = ExpenseCategory::create([
            'store_id' => $this->store->id,
            'name' => 'Staff Meals',
            'code' => 'MEALS',
        ]);

        $exp1 = Expense::create([
            'store_id' => $this->store->id,
            'expense_category_id' => $this->category->id,
            'expense_number' => 'EXP-20260820-0001',
            'title' => 'Shop Rent August',
            'amount' => 400000.00,
            'expense_date' => '2026-08-20',
            'payment_method' => 'bank_transfer',
            'paid_to' => 'Landlord',
        ]);

        $exp2 = Expense::create([
            'store_id' => $this->store->id,
            'expense_category_id' => $cat2->id,
            'expense_number' => 'EXP-20260823-0002',
            'title' => 'Staff Biryani Lunch',
            'amount' => 35000.00,
            'expense_date' => '2026-08-23',
            'payment_method' => 'kpay',
            'paid_to' => 'Aung Biryani',
        ]);

        // Search query
        $searchRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expenses?search=Biryani");
        $searchRes->assertOk()->assertSeeText('Staff Biryani Lunch')->assertDontSeeText('Shop Rent August');

        // Category filter
        $catRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expenses?category_id={$cat2->id}");
        $catRes->assertOk()->assertSeeText('Staff Biryani Lunch')->assertDontSeeText('Shop Rent August');

        // Payment Method filter
        $payRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expenses?payment_method=bank_transfer");
        $payRes->assertOk()->assertSeeText('Shop Rent August')->assertDontSeeText('Staff Biryani Lunch');

        // Date Range filter
        $dateRes = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expenses?date_from=2026-08-22&date_to=2026-08-23");
        $dateRes->assertOk()->assertSeeText('Staff Biryani Lunch')->assertDontSeeText('Shop Rent August');
    }

    public function test_manager_can_export_expenses_csv(): void
    {
        Expense::create([
            'store_id' => $this->store->id,
            'expense_category_id' => $this->category->id,
            'expense_number' => 'EXP-20260823-0001',
            'title' => 'Printer Paper & Toner',
            'amount' => 85000.00,
            'expense_date' => '2026-08-23',
            'payment_method' => 'cash',
            'paid_to' => 'City Mart Stationery',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/expenses/export");

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_coming_soon_registry_no_longer_contains_expenses(): void
    {
        $modules = ComingSoonController::modules();
        $this->assertArrayNotHasKey('expenses', $modules);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/coming-soon/expenses");

        $response->assertNotFound();
    }

    public function test_index_renders_in_all_supported_locales(): void
    {
        Expense::create([
            'store_id' => $this->store->id,
            'expense_number' => 'EXP-20260823-0001',
            'title' => 'Maintenance',
            'amount' => 10000.00,
            'expense_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        foreach (['my', 'en', 'zh_CN'] as $locale) {
            $response = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get("/store/{$this->store->slug}/admin/expenses");

            $response->assertOk()
                ->assertDontSee('messages.expenses_title')
                ->assertDontSee('messages.expenses_new');
        }
    }
}
