<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportHistory;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeManagerAndStore(): array
    {
        $store = Store::create(['name' => 'Test Store', 'slug' => 'test-store']);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        return [$store, $manager];
    }

    private function makeCsv(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, 'products.csv', 'text/csv', null, true);
    }

    /**
     * @param array<int, array<int, string|int|float|null>> $rows
     */
    private function makeXlsx(array $rows, string $filename = 'products.xlsx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function previewAndConfirm(User $manager, string $storeSlug, UploadedFile $file, string $strategy = 'skip')
    {
        $previewResponse = $this->actingAs($manager)
            ->post("/store/{$storeSlug}/admin/products/import", [
                'file' => $file,
                'duplicate_strategy' => $strategy,
            ]);

        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('import_preview');

        $preview = $previewResponse->getSession()->get('import_preview');

        $confirmResponse = $this->actingAs($manager)
            ->post("/store/{$storeSlug}/admin/products/import/confirm", [
                'token' => $preview['token'],
                'duplicate_strategy' => $strategy,
            ]);

        $confirmResponse->assertRedirect();
        $confirmResponse->assertSessionHas('import_result');

        return $confirmResponse;
    }

    public function test_valid_csv_import_creates_products(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status,warranty,return_policy,description,image_url\n"
            . "Screen A,SKU-001,Apple,Screens,45000,38000,in_stock,7 days,No return,Clear glass,\n"
            . "Battery B,SKU-002,Samsung,Batteries,12000,9000,out_of_stock,,,,\n";

        $response = $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped_duplicate']);
        $this->assertEquals(0, $result['failed']);

        $this->assertDatabaseHas('products', ['store_id' => $store->id, 'sku' => 'SKU-001']);
        $this->assertDatabaseHas('products', ['store_id' => $store->id, 'sku' => 'SKU-002']);
        $this->assertDatabaseHas('import_histories', [
            'store_id' => $store->id,
            'type' => 'products',
            'total_rows' => 2,
            'success_rows' => 2,
            'failed_rows' => 0,
        ]);
    }

    public function test_valid_xlsx_import_creates_products(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $file = $this->makeXlsx([
            ['name', 'sku', 'brand', 'category', 'retail_price', 'wholesale_price', 'stock_status', 'warranty', 'return_policy', 'description', 'image_url'],
            ['Screen XLSX', 'SKU-XLSX-001', 'Apple', 'Screens', 45000, 38000, 'in_stock', '7 days', 'No return', 'Excel row', null],
        ]);

        $response = $this->previewAndConfirm($manager, 'test-store', $file);
        $result = $response->getSession()->get('import_result');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['failed']);
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'SKU-XLSX-001',
            'name' => 'Screen XLSX',
        ]);
    }

    public function test_import_maps_featured_column_to_is_featured(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status,featured\n"
            . "Featured Product,SKU-FEATURED,Apple,Screens,45000,38000,in_stock,yes\n"
            . "Normal Product,SKU-NORMAL,Apple,Screens,45000,38000,in_stock,no\n";

        $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'SKU-FEATURED',
            'is_featured' => true,
        ]);
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'SKU-NORMAL',
            'is_featured' => false,
        ]);
    }

    public function test_auto_brand_and_category_creation(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status\n"
            . "Screen A,SKU-AUTO,New Brand,New Category,45000,38000,in_stock\n";

        $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        $brand = Brand::where('store_id', $store->id)->where('name', 'New Brand')->first();
        $category = Category::where('store_id', $store->id)->where('name', 'New Category')->first();

        $this->assertNotNull($brand);
        $this->assertNotNull($category);
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'SKU-AUTO',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_import_creates_variants_from_variants_column(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $variantsJson = '[{"name":"128GB","sku":"SKU-VAR-128","retail_price":18000,"wholesale_price":15000,"stock_status":"in_stock"},'
            . '{"name":"256GB","attributes":[{"label":"Mobile Storage","value":"256GB"}],"sku":"SKU-VAR-256","retail_price":19500,"wholesale_price":16200,"stock_status":"out_of_stock"}]';
        // CSV-escape the JSON (it contains commas + double quotes)
        $variantsCsv = '"' . str_replace('"', '""', $variantsJson) . '"';

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status,variants\n"
            . "Variant Phone,SKU-VAR,Apple,Screens,18000,15000,in_stock,{$variantsCsv}\n";

        $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        $product = Product::where('store_id', $store->id)->where('sku', 'SKU-VAR')->firstOrFail();
        $this->assertCount(2, $product->variants);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'SKU-VAR-256',
            'name' => '256GB',
            'stock_status' => 'out_of_stock',
        ]);
        $this->assertEquals(
            [['label' => 'Mobile Storage', 'value' => '256GB']],
            $product->variants()->where('sku', 'SKU-VAR-256')->firstOrFail()->attributes
        );
    }

    public function test_import_rejects_invalid_variants_json(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status,variants\n"
            . "Variant Phone,SKU-BADVAR,Apple,Screens,18000,15000,in_stock,NOT_JSON\n";

        $response = $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['failed']);
        $this->assertDatabaseMissing('products', ['store_id' => $store->id, 'sku' => 'SKU-BADVAR']);
    }

    public function test_duplicate_sku_is_skipped_by_default(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        Product::create([
            'store_id' => $store->id,
            'name' => 'Existing',
            'sku' => 'SKU-DUP',
            'slug' => 'existing',
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'stock_status' => 'in_stock',
        ]);

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status\n"
            . "Updated,SKU-DUP,Apple,Screens,45000,38000,out_of_stock\n";

        $response = $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));
        $result = $response->getSession()->get('import_result');

        $this->assertEquals(1, $result['skipped_duplicate']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals('Existing', Product::where('store_id', $store->id)->where('sku', 'SKU-DUP')->value('name'));
    }

    public function test_duplicate_sku_can_update_only_when_selected(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        Product::create([
            'store_id' => $store->id,
            'name' => 'Existing',
            'sku' => 'SKU-UPD',
            'slug' => 'existing-update',
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'stock_status' => 'in_stock',
        ]);

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status\n"
            . "Updated,SKU-UPD,Apple,Screens,45000,38000,out_of_stock\n";

        $response = $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv), 'update');
        $result = $response->getSession()->get('import_result');

        $this->assertEquals(1, $result['updated']);
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'SKU-UPD',
            'name' => 'Updated',
            'stock_status' => 'out_of_stock',
        ]);
    }

    public function test_xlsx_duplicate_sku_is_skipped_by_default(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        Product::create([
            'store_id' => $store->id,
            'name' => 'Existing XLSX',
            'sku' => 'SKU-XLSX-DUP',
            'slug' => 'existing-xlsx',
            'retail_price' => 1000,
            'wholesale_price' => 800,
            'stock_status' => 'in_stock',
        ]);

        $file = $this->makeXlsx([
            ['name', 'sku', 'brand', 'category', 'retail_price', 'wholesale_price', 'stock_status'],
            ['Updated XLSX', 'SKU-XLSX-DUP', 'Apple', 'Screens', 45000, 38000, 'out_of_stock'],
        ]);

        $response = $this->previewAndConfirm($manager, 'test-store', $file);
        $result = $response->getSession()->get('import_result');

        $this->assertEquals(1, $result['skipped_duplicate']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals('Existing XLSX', Product::where('store_id', $store->id)->where('sku', 'SKU-XLSX-DUP')->value('name'));
    }

    public function test_csv_with_invalid_price_records_failed_row_without_creating_product(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status\n"
            . "Screen X,SKU-BAD,Apple,Screens,NOT_A_PRICE,38000,in_stock\n";

        $response = $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['failed']);
        $this->assertDatabaseMissing('products', ['store_id' => $store->id, 'sku' => 'SKU-BAD']);
        $this->assertNotNull(ImportHistory::where('store_id', $store->id)->where('type', 'products')->value('error_file_path'));
    }

    public function test_csv_missing_required_header_is_rejected_at_preview(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,brand,category,retail_price,wholesale_price,stock_status\n"
            . "Screen A,Apple,Screens,45000,38000,in_stock\n";

        $response = $this->actingAs($manager)
            ->post('/store/test-store/admin/products/import', ['file' => $this->makeCsv($csv)]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('products', ['store_id' => $store->id]);
    }

    public function test_non_csv_or_xlsx_file_is_rejected(): void
    {
        [$store, $manager] = $this->makeManagerAndStore();

        $fakeExe = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $response = $this->actingAs($manager)
            ->post('/store/test-store/admin/products/import', ['file' => $fakeExe]);

        $response->assertSessionHasErrors('file');
    }

    public function test_invalid_xlsx_file_is_rejected(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $path = tempnam(sys_get_temp_dir(), 'invalid_import_') . '.xlsx';
        file_put_contents($path, 'not a valid xlsx workbook');
        $file = new UploadedFile($path, 'invalid.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($manager)
            ->post('/store/test-store/admin/products/import', ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('products', ['store_id' => $store->id]);
    }

    public function test_customer_cannot_access_import_route(): void
    {
        Store::create(['name' => 'Test Store', 'slug' => 'test-store']);
        $customer = User::create([
            'name' => 'Customer',
            'phone' => '09222222222',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status\n"
            . "Screen A,SKU-001,Apple,Screens,45000,38000,in_stock\n";

        $response = $this->actingAs($customer)
            ->post('/store/test-store/admin/products/import', ['file' => $this->makeCsv($csv)]);

        $response->assertStatus(403);
    }

    public function test_store_isolation_prevents_cross_store_import(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $managerA = User::create([
            'name' => 'Manager A',
            'phone' => '09333333333',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $managerA->stores()->attach($storeA->id, ['role' => 'store_manager', 'status' => 'active']);

        $csv = "name,sku,brand,category,retail_price,wholesale_price,stock_status\n"
            . "Screen A,SKU-001,Apple,Screens,45000,38000,in_stock\n";

        $response = $this->actingAs($managerA)
            ->post('/store/store-b/admin/products/import', ['file' => $this->makeCsv($csv)]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['store_id' => $storeB->id]);
    }

    public function test_legacy_pos_csv_headers_are_mapped_and_stock_derived(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "Item_Type,Product_ID,Product_Name,Category,Brand,Description,Requires_Serial,Original_Cost,Opening_Stock,Current_Stock,Average_Cost,Sale_Price,Wholesale_Price,Reorder_Level,Warranty_Period,Shelf_Location,Status,Images,Stock_View\n"
            . "Spare Part,RM-001,RM Screen,TOUCH,Xiaomi,,,25000,3,2,25000,29000,28000,2,1 Month Warranty,Box1,Active (ရောင်းမည်),,🛒\n"
            . "Accessories,ACC-002,USB Cable,Cable,OTHER,,,500,0,0,500,0,0,1,No Warranty,,Active (ရောင်းမည်),,🆕\n"
            . "Accessories,ACC-003,Old Stock,HOLDER,,,,-1,0,0,1000,1500,1200,1,,,,Active (ရောင်းမည်),,✅\n"
            . "Accessories,ACC-004,No Wholesale,Cable,X,,,200,1,1,200,3000,,1,,,Active (ရောင်းမည်),,🆕\n";

        $response = $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(4, $result['total']);
        $this->assertEquals(4, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        // Positive current stock + valid price -> in_stock, warranty mapped.
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'RM-001',
            'retail_price' => 29000,
            'wholesale_price' => 28000,
            'stock_status' => 'in_stock',
            'warranty' => '1 Month Warranty',
        ]);

        // Zero sale price forces out_of_stock; zero wholesale stays 0 and the
        // storefront falls back to the retail price for wholesale-approved buyers.
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'ACC-002',
            'retail_price' => 0,
            'wholesale_price' => 0,
            'stock_status' => 'out_of_stock',
        ]);

        // Zero current stock -> out_of_stock; empty brand -> no brand.
        $product = Product::where('store_id', $store->id)->where('sku', 'ACC-003')->first();
        $this->assertNotNull($product);
        $this->assertEquals('out_of_stock', $product->stock_status);
        $this->assertNull($product->brand_id);

        // Empty wholesale cell becomes 0 (no wholesale tier); positive stock stays in_stock.
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'ACC-004',
            'retail_price' => 3000,
            'wholesale_price' => 0,
            'stock_status' => 'in_stock',
        ]);

        // Item_Type column becomes the parent category: TOUCH under Spare Part,
        // Cable under Accessories. Re-import keeps the same parent (no duplicates).
        $touch = Category::where('store_id', $store->id)->where('name', 'TOUCH')->first();
        $this->assertNotNull($touch);
        $this->assertNotNull($touch->parent);
        $this->assertEquals('Spare Part', $touch->parent->name);

        $cable = Category::where('store_id', $store->id)->where('name', 'Cable')->first();
        $this->assertNotNull($cable);
        $this->assertNotNull($cable->parent);
        $this->assertEquals('Accessories', $cable->parent->name);

        $this->assertEquals(
            1,
            Category::where('store_id', $store->id)->where('name', 'Cable')->count(),
            'Cable must not be duplicated across two import rows.'
        );
    }

    public function test_import_maps_sale_meta_and_parent_category_columns(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,sku,brand,category,parent_category,retail_price,wholesale_price,old_price,sale_starts_at,sale_ends_at,stock_status,description,meta_description\n"
            . "Sale Phone,SP-001,Xiaomi,TOUCH,Spare Part,29000,28000,32000,2026-08-01 00:00,2026-08-31 23:59,in_stock,Sale desc,Meta for search\n"
            . "Bad Window,BW-002,,Cable,Accessories,1000,900,,2026-09-01 00:00,2026-08-01 00:00,in_stock,,\n";

        $this->previewAndConfirm($manager, 'test-store', $this->makeCsv($csv));

        // parent_category becomes the Main Category: TOUCH under Spare Part.
        $this->assertDatabaseHas('products', [
            'store_id' => $store->id,
            'sku' => 'SP-001',
            'old_price' => 32000,
            'sale_starts_at' => '2026-08-01 00:00:00',
            'sale_ends_at' => '2026-08-31 23:59:00',
            'meta_description' => 'Meta for search',
        ]);
        $touch = Category::where('store_id', $store->id)->where('name', 'TOUCH')->first();
        $this->assertNotNull($touch);
        $this->assertNotNull($touch->parent);
        $this->assertEquals('Spare Part', $touch->parent->name);

        // A sale window with end before start is rejected.
        $this->assertDatabaseMissing('products', ['store_id' => $store->id, 'sku' => 'BW-002']);
    }

    public function test_export_csv_round_trips_through_import_into_fresh_store(): void
    {
        Storage::fake('local');
        [$storeA, $manager] = $this->makeManagerAndStore();

        // Source product: sub-category + sale + meta — everything the form has.
        $category = Category::create([
            'store_id' => $storeA->id,
            'name' => 'TOUCH',
            'slug' => 'touch',
            'parent_id' => Category::create(['store_id' => $storeA->id, 'name' => 'Spare Part', 'slug' => 'spare-part'])->id,
        ]);
        $brand = Brand::create(['store_id' => $storeA->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        Product::create([
            'store_id' => $storeA->id,
            'name' => 'Sale Phone',
            'sku' => 'SP-001',
            'slug' => 'sale-phone',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'retail_price' => 29000,
            'wholesale_price' => 28000,
            'old_price' => 32000,
            'sale_starts_at' => '2026-08-01 00:00:00',
            'sale_ends_at' => '2026-08-31 23:59:00',
            'stock_status' => 'in_stock',
            'meta_description' => 'Meta for search',
        ]);

        // Export store A's catalog.
        $export = $this->actingAs($manager)->get("/store/{$storeA->slug}/admin/products/export");
        $export->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('Parent Category', $csv);
        $this->assertStringContainsString('Discount Price (Ks)', $csv);
        $this->assertStringContainsString('Sale Starts At', $csv);
        $this->assertStringContainsString('Meta Description', $csv);
        $this->assertStringContainsString('Spare Part', $csv);

        // Fresh store B: re-importing the export recreates the Main/Sub tree.
        $storeB = Store::create(['name' => 'Fresh Store', 'slug' => 'fresh-store']);
        $manager->stores()->attach($storeB->id, ['role' => 'store_manager', 'status' => 'active']);

        $preview = $this->actingAs($manager)
            ->post("/store/{$storeB->slug}/admin/products/import", [
                'file' => $this->makeCsv($csv),
                'duplicate_strategy' => 'skip',
            ])
            ->assertRedirect()
            ->getSession()->get('import_preview');

        $this->assertEquals(1, $preview['total']);
        $this->assertEquals(1, $preview['creatable']);
        $this->assertEquals(0, $preview['failed']);

        $this->actingAs($manager)
            ->post("/store/{$storeB->slug}/admin/products/import/confirm", [
                'token' => $preview['token'],
                'duplicate_strategy' => 'skip',
            ])
            ->assertRedirect()
            ->assertSessionHas('import_result');

        $product = Product::where('store_id', $storeB->id)->where('sku', 'SP-001')->first();
        $this->assertNotNull($product);
        $this->assertEquals(32000, (float) $product->old_price);
        $this->assertEquals('2026-08-01 00:00:00', $product->sale_starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('Meta for search', $product->meta_description);
        $this->assertNotNull($product->category);
        $this->assertNotNull($product->category->parent);
        $this->assertEquals('Spare Part', $product->category->parent->name);
    }

    public function test_manager_can_download_import_template(): void
    {
        $store = Store::create(['name' => 'Test Store', 'slug' => 'test-store']);
        $manager = User::create([
            'name' => 'Manager',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/products/import/template");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertNotEmpty($response->streamedContent());
    }
}

