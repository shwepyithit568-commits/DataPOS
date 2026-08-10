<?php

namespace Tests\Feature;

use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ImportHistoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;
    private Store $storeB;
    private User $managerA;
    private User $staffA;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $this->storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $this->storeA->setting()->create(['store_name' => 'Store A', 'default_language' => 'en']);
        $this->storeB->setting()->create(['store_name' => 'Store B', 'default_language' => 'en']);

        $this->managerA = User::factory()->create(['phone' => '09111111001']);
        $this->managerA->stores()->attach($this->storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staffA = User::factory()->create(['phone' => '09111111002']);
        $this->staffA->stores()->attach($this->storeA->id, ['role' => 'staff', 'status' => 'active']);

        $this->customer = User::factory()->create(['phone' => '09111111003', 'role' => 'customer']);
    }

    public function test_import_history_page_access_and_summary(): void
    {
        ImportHistory::create([
            'store_id' => $this->storeA->id,
            'user_id' => $this->managerA->id,
            'type' => 'products',
            'filename' => 'products.xlsx',
            'total_rows' => 10,
            'success_rows' => 8,
            'failed_rows' => 2,
        ]);

        ImportHistory::create([
            'store_id' => $this->storeA->id,
            'user_id' => $this->staffA->id,
            'type' => 'glass_finder',
            'filename' => 'glass.xlsx',
            'total_rows' => 3,
            'success_rows' => 3,
            'failed_rows' => 0,
        ]);

        $response = $this->actingAs($this->managerA)
            ->get('/store/store-a/admin/import-history');

        $response->assertStatus(200);
        $response->assertSeeText('Import History');
        $response->assertSeeText('Product');
        $response->assertSeeText('Glass Finder');
        $response->assertSeeText('products.xlsx');
        $response->assertSeeText('Total Imports');
        $response->assertSeeText('Successful Rows');
        $response->assertSeeText('Failed Rows');
        $response->assertSeeText('Completed with errors');
    }

    public function test_import_history_store_isolation(): void
    {
        $historyB = ImportHistory::create([
            'store_id' => $this->storeB->id,
            'user_id' => null,
            'type' => 'products',
            'filename' => 'store-b-products.xlsx',
            'total_rows' => 1,
            'success_rows' => 1,
            'failed_rows' => 0,
        ]);

        $listResponse = $this->actingAs($this->managerA)
            ->get('/store/store-a/admin/import-history');

        $listResponse->assertStatus(200);
        $listResponse->assertDontSeeText('store-b-products.xlsx');

        $showResponse = $this->actingAs($this->managerA)
            ->get("/store/store-a/admin/import-history/{$historyB->id}");

        $showResponse->assertStatus(403);

        $crossStoreResponse = $this->actingAs($this->managerA)
            ->get('/store/store-b/admin/import-history');

        $crossStoreResponse->assertStatus(403);
    }

    public function test_template_downloads_are_available_to_staff_admins(): void
    {
        $productResponse = $this->actingAs($this->staffA)
            ->get('/store/store-a/admin/products/import/template');

        $productResponse->assertStatus(200);
        $productResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('product-import-template.xlsx', $productResponse->headers->get('content-disposition'));

        $glassResponse = $this->actingAs($this->staffA)
            ->get('/store/store-a/admin/glass-finder/import/template');

        $glassResponse->assertStatus(200);
        $glassResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('glass-finder-import-template.xlsx', $glassResponse->headers->get('content-disposition'));
    }

    public function test_product_template_contains_featured_column_and_instructions(): void
    {
        $response = $this->actingAs($this->staffA)
            ->get('/store/store-a/admin/products/import/template');

        $response->assertStatus(200);

        $path = tempnam(sys_get_temp_dir(), 'product_template_') . '.xlsx';
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);
        $productRows = $spreadsheet->getSheetByName('Products')->toArray(null, false, false, false);
        $instructionRows = $spreadsheet->getSheetByName('Instructions')->toArray(null, false, false, false);
        $spreadsheet->disconnectWorksheets();

        $this->assertContains('featured', $productRows[0]);
        $this->assertStringContainsString('1, true, yes, Y', collect($instructionRows)->flatten()->join(' '));
    }

    public function test_glass_finder_template_excludes_notes_column(): void
    {
        $response = $this->actingAs($this->staffA)
            ->get('/store/store-a/admin/glass-finder/import/template');

        $response->assertStatus(200);

        $path = tempnam(sys_get_temp_dir(), 'glass_template_') . '.xlsx';
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getSheetByName('Glass Finder')->toArray(null, false, false, false);
        $spreadsheet->disconnectWorksheets();

        $this->assertSame(['brand', 'phone_model', 'glass_code', 'stock_status'], $rows[0]);
        $this->assertNotContains('notes', $rows[0]);
    }

    public function test_failed_csv_download_is_store_scoped(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('import-errors/' . $this->storeA->id . '/failed.csv', "row_number,field,error_message,original_data\n2,sku,Missing SKU,{}\n");
        Storage::disk('local')->put('import-errors/' . $this->storeB->id . '/failed.csv', "row_number,field,error_message,original_data\n2,sku,Other store,{}\n");

        $historyA = ImportHistory::create([
            'store_id' => $this->storeA->id,
            'user_id' => $this->managerA->id,
            'type' => 'products',
            'filename' => 'bad-products.xlsx',
            'total_rows' => 1,
            'success_rows' => 0,
            'failed_rows' => 1,
            'error_file_path' => 'import-errors/' . $this->storeA->id . '/failed.csv',
        ]);

        $historyB = ImportHistory::create([
            'store_id' => $this->storeB->id,
            'user_id' => null,
            'type' => 'products',
            'filename' => 'other-products.xlsx',
            'total_rows' => 1,
            'success_rows' => 0,
            'failed_rows' => 1,
            'error_file_path' => 'import-errors/' . $this->storeB->id . '/failed.csv',
        ]);

        $downloadResponse = $this->actingAs($this->managerA)
            ->get("/store/store-a/admin/import-history/{$historyA->id}/errors");

        $downloadResponse->assertStatus(200);
        $this->assertStringContainsString('bad-products-failed-rows.csv', $downloadResponse->headers->get('content-disposition'));
        $this->assertStringContainsString('row_number,field,error_message,original_data', $downloadResponse->streamedContent());

        $crossStoreDownload = $this->actingAs($this->managerA)
            ->get("/store/store-a/admin/import-history/{$historyB->id}/errors");

        $crossStoreDownload->assertStatus(403);
    }

    public function test_import_history_sidebar_visibility_and_customer_block(): void
    {
        $managerResponse = $this->actingAs($this->managerA)
            ->get('/store/store-a/admin/dashboard');

        $managerResponse->assertStatus(200);
        $managerResponse->assertSeeText('Import History');
        $managerResponse->assertSee('data-route-name="store.admin.import-history.index"', false);

        $staffResponse = $this->actingAs($this->staffA)
            ->get('/store/store-a/admin/dashboard');

        $staffResponse->assertStatus(200);
        $staffResponse->assertSeeText('Import History');

        $customerResponse = $this->actingAs($this->customer)
            ->get('/store/store-a/admin/import-history');

        $customerResponse->assertStatus(403);
    }

    public function test_import_history_mobile_layout_classes_are_present(): void
    {
        $response = $this->actingAs($this->managerA)
            ->get('/store/store-a/admin/import-history');

        $response->assertStatus(200);
        // Shared clean header pattern (matches Products / Brands / Categories).
        $response->assertSee('admin-page-header', false);
        $response->assertSee('admin-page-title', false);
        $response->assertSee('admin-page-sub', false);
        // Responsive table stays locally scrollable without page-level overflow.
        $response->assertSee('overflow-x-auto', false);
        $response->assertSee('min-w-[920px]', false);
        // Header action buttons use the shared min-h-11 touch-target pattern.
        $response->assertSee('min-h-11', false);
        $response->assertDontSee('href="#"', false);
    }
}
