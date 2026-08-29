<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PilotImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeManagerAndStore(): array
    {
        $store = Store::create([
            'name' => 'Test Store',
            'slug' => 'test-store',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Manager',
            'phone' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $user->stores()->attach($store->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);

        return [$store, $user];
    }

    private function makeCsv(string $content, string $filename = 'import.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, $filename, 'text/csv', null, true);
    }

    private function previewAndConfirm(User $manager, string $storeSlug, string $tab, UploadedFile $file, string $strategy = 'skip'): \Illuminate\Testing\TestResponse
    {
        $previewResponse = $this->actingAs($manager)
            ->post("/store/{$storeSlug}/admin/pilot-import/{$tab}", [
                'file' => $file,
                'duplicate_strategy' => $strategy,
            ]);

        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('import_preview');

        $preview = $previewResponse->getSession()->get('import_preview');

        return $this->actingAs($manager)
            ->post("/store/{$storeSlug}/admin/pilot-import/{$tab}/confirm", [
                'token' => $preview['token'],
                'duplicate_strategy' => $strategy,
            ]);
    }

    // ─────────────────────────── Hub page ───────────────────────────

    public function test_pilot_import_hub_renders_all_tabs(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        foreach (['products', 'customers', 'suppliers'] as $tab) {
            $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/pilot-import/{$tab}");
            $response->assertOk();
            $response->assertSee($store->name, false);
        }
    }

    public function test_pilot_import_requires_store_access(): void
    {
        Storage::fake('local');
        $store = Store::create(['name' => 'Other Store', 'slug' => 'other-store', 'is_active' => true]);
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->get("/store/{$store->slug}/admin/pilot-import/customers")
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post("/store/{$store->slug}/admin/pilot-import/customers", ['file' => $this->makeCsv('name,phone' . "\n" . 'A,0991111111')])
            ->assertForbidden();
    }

    public function test_unknown_tab_404s(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $this->actingAs($manager)->get("/store/{$store->slug}/admin/pilot-import/inventory")->assertNotFound();
    }

    // ─────────────────────────── Customers ───────────────────────────

    public function test_customer_import_creates_users_and_memberships(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,phone,email,role\n"
            . "Ma Su,09 123 456 789,masu@example.com,retail_customer\n"
            . "Daw Aye,09987654321,,wholesale_customer\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'customers', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        $masu = User::where('phone', '9123456789')->first();
        $this->assertNotNull($masu);
        $this->assertSame('Ma Su', $masu->name);
        $this->assertSame('customer', $masu->role);
        $this->assertTrue($masu->stores()->where('store_id', $store->id)->wherePivot('role', 'retail_customer')->wherePivot('status', 'active')->exists());

        $aye = User::where('phone', '9987654321')->first();
        $this->assertTrue($aye->stores()->where('store_id', $store->id)->wherePivot('role', 'wholesale_customer')->exists());

        $this->assertDatabaseHas('import_histories', [
            'store_id' => $store->id,
            'type' => 'customers',
            'total_rows' => 2,
            'success_rows' => 2,
            'failed_rows' => 0,
        ]);
    }

    public function test_customer_preview_is_dry_run_and_writes_nothing(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,phone\n" . "Ma Su,09123456780\n" . "Daw Aye,09987654320\n";

        $previewResponse = $this->actingAs($manager)
            ->post("/store/{$store->slug}/admin/pilot-import/customers", [
                'file' => $this->makeCsv($csv),
            ]);

        $previewResponse->assertRedirect();
        $preview = $previewResponse->getSession()->get('import_preview');

        $this->assertEquals(2, $preview['total']);
        $this->assertEquals(2, $preview['creatable']);
        $this->assertCount(2, $preview['preview_rows']);
        $this->assertSame('create', $preview['preview_rows'][0]['action']);

        // Dry-run: no imported users, no new memberships, no history rows.
        $this->assertSame(0, User::whereIn('phone', ['9123456780', '9987654320'])->count());
        $this->assertSame(1, $store->users()->count()); // only the manager's own membership
        $this->assertSame(0, ImportHistory::count());
    }

    public function test_customer_duplicate_phone_skipped_by_default(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        User::create([
            'name' => 'Existing',
            'phone' => '9123456780',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ])->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $csv = "name,phone\n" . "Ma Su,09123456780\n" . "Daw Aye,09987654320\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'customers', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicate']);
        $this->assertEquals(0, $result['failed']);
    }

    public function test_customer_duplicate_phone_updated_with_strategy(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $existing = User::create([
            'name' => 'Old Name',
            'phone' => '9123456780',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $existing->stores()->attach($store->id, ['role' => 'retail_customer', 'status' => 'active']);

        $csv = "name,phone,role\n" . "Ma Su New,09123456780,wholesale_customer\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'customers', $this->makeCsv($csv), 'update');

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(0, $result['failed']);

        $existing->refresh();
        $this->assertSame('Ma Su New', $existing->name);
        $this->assertSame('wholesale_customer', $existing->getStoreRole($store->id));
    }

    public function test_customer_import_attaches_user_from_another_store(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b', 'is_active' => true]);

        $shared = User::create([
            'name' => 'Shared Customer',
            'phone' => '9123456780',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $shared->stores()->attach($storeB->id, ['role' => 'retail_customer', 'status' => 'active']);

        $csv = "name,phone\n" . "Shared Customer,09123456780\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'customers', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['attached']);
        $this->assertEquals(0, $result['imported']);

        // One user, two store memberships.
        $this->assertSame(1, User::where('phone', '9123456780')->count());
        $this->assertTrue($shared->stores()->where('store_id', $store->id)->wherePivot('status', 'active')->exists());
        $this->assertTrue($shared->stores()->where('store_id', $storeB->id)->exists());
    }

    public function test_customer_import_intra_file_duplicate_skipped(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,phone\n" . "Ma Su,09123456780\n" . "Ma Su Copy,09 123 456 780\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'customers', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicate']);
    }

    public function test_customer_import_invalid_rows_fail_with_error_file(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,phone,email,role\n"
            . ",09123456780,,retail_customer\n"          // missing name
            . "Bad Phone,123,,retail_customer\n"          // phone too short
            . "Bad Email,09123456781,not-an-email,retail_customer\n"
            . "Bad Role,09123456782,,superuser\n"
            . "Good,09123456783,,retail_customer\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'customers', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(5, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(4, $result['failed']);

        $history = ImportHistory::where('store_id', $store->id)->where('type', 'customers')->first();
        $this->assertNotNull($history);
        $this->assertEquals(4, $history->failed_rows);
        $this->assertNotNull($history->error_file_path);
        Storage::disk('local')->assertExists($history->error_file_path);
    }

    public function test_customer_import_store_isolation(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b', 'is_active' => true]);
        $managerB = User::create([
            'name' => 'Manager B',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        // Same phone imported into two stores → two memberships, one user.
        $csv = "name,phone\n" . "Ma Su,09123456780\n";

        $this->previewAndConfirm($manager, $store->slug, 'customers', $this->makeCsv($csv));
        $this->previewAndConfirm($managerB, $storeB->slug, 'customers', $this->makeCsv($csv));

        $user = User::where('phone', '9123456780')->first();
        $this->assertSame(1, User::where('phone', '9123456780')->count());
        $this->assertTrue($user->stores()->where('store_id', $store->id)->exists());
        $this->assertTrue($user->stores()->where('store_id', $storeB->id)->exists());
        $this->assertSame(2, ImportHistory::count());
    }

    // ─────────────────────────── Suppliers ───────────────────────────

    public function test_supplier_import_creates_skips_and_updates(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        Supplier::create([
            'store_id' => $store->id,
            'name' => 'ACDC Mobile',
            'phone' => '9987654321',
        ]);

        $csv = "name,phone,email,contact_person,address,notes\n"
            . "New Supplier,09912345678,new@example.com,U Mya,Yangon,\n"
            . "ACDC Mobile,09 987 654 321,,U Aung,\n"
            . "Third Supplier,,,,\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'suppliers', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicate']);
        $this->assertEquals(0, $result['failed']);

        $this->assertDatabaseHas('suppliers', ['store_id' => $store->id, 'name' => 'New Supplier', 'phone' => '9912345678', 'contact_person' => 'U Mya']);
        $this->assertDatabaseHas('import_histories', [
            'store_id' => $store->id,
            'type' => 'suppliers',
            'total_rows' => 3,
            'success_rows' => 2,
            'failed_rows' => 0,
        ]);
    }

    public function test_supplier_import_update_strategy_updates_existing(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        Supplier::create([
            'store_id' => $store->id,
            'name' => 'Old Name',
            'phone' => '9912345678',
        ]);

        $csv = "name,phone,contact_person\n" . "New Name,09912345678,U Aung\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'suppliers', $this->makeCsv($csv), 'update');

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['updated']);
        $this->assertDatabaseHas('suppliers', ['store_id' => $store->id, 'name' => 'New Name', 'phone' => '9912345678', 'contact_person' => 'U Aung']);
        $this->assertSame(1, Supplier::where('store_id', $store->id)->count());
    }

    public function test_supplier_import_matches_by_name_when_no_phone(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        Supplier::create(['store_id' => $store->id, 'name' => 'ACDC Mobile']);

        $csv = "name,phone\n" . "acdc mobile,09912345678\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'suppliers', $this->makeCsv($csv), 'update');

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['updated']);
        $this->assertSame(1, Supplier::where('store_id', $store->id)->count());
    }

    public function test_supplier_import_validation_and_isolation(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b', 'is_active' => true]);

        $csv = "name,phone\n"
            . ",09123456780\n"          // missing name
            . "Bad,12\n"                // phone too short
            . "Same Name,09912345678\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'suppliers', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(2, $result['failed']);

        // Same name in another store is a separate supplier (no leak).
        $managerB = User::create([
            'name' => 'Manager B',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerB->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->previewAndConfirm($managerB, $storeB->slug, 'suppliers', $this->makeCsv("name,phone\n" . 'Same Name,09987654321' . "\n"));

        $this->assertSame(2, Supplier::where('name', 'Same Name')->count());
    }

    // ─────────────────────────── Templates + products tab ───────────────────────────

    public function test_customer_and_supplier_templates_download(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $customerTemplate = $this->actingAs($manager)->get("/store/{$store->slug}/admin/pilot-import/customers/template");
        $customerTemplate->assertOk();
        $this->assertStringContainsString('customers-import-template.csv', $customerTemplate->headers->get('content-disposition'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $customerTemplate->streamedContent());
        $this->assertStringContainsString('name,phone,email,role', $customerTemplate->streamedContent());

        $supplierTemplate = $this->actingAs($manager)->get("/store/{$store->slug}/admin/pilot-import/suppliers/template");
        $supplierTemplate->assertOk();
        $this->assertStringContainsString('suppliers-import-template.csv', $supplierTemplate->headers->get('content-disposition'));
        $this->assertStringContainsString('name,phone,email,contact_person,address,notes', $supplierTemplate->streamedContent());
    }

    public function test_products_tab_delegates_to_product_import(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,sku,retail_price,wholesale_price,stock_status,brand\n"
            . "Tempered Glass,TG-001,15000,12000,in_stock,ACDC\n"
            . "Phone Case,PC-001,8000,6500,in_stock,\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'products', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        $this->assertDatabaseHas('products', ['store_id' => $store->id, 'sku' => 'TG-001', 'name' => 'Tempered Glass']);
        $this->assertDatabaseHas('brands', ['store_id' => $store->id, 'name' => 'ACDC']);
        $this->assertDatabaseHas('import_histories', ['store_id' => $store->id, 'type' => 'products']);
    }

    public function test_products_template_route_redirects_to_existing_template(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/pilot-import/products/template");
        $response->assertRedirect(route('store.admin.products.import.template', ['store_slug' => $store->slug]));
    }
}
