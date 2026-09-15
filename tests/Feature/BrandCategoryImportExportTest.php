<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BrandCategoryImportExportTest extends TestCase
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

    /**
     * @param array<int, array<int, string|int|float|null>> $rows
     */
    private function makeXlsx(array $rows, string $filename = 'import.xlsx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import_test_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function previewAndConfirm(User $manager, string $storeSlug, string $resource, UploadedFile $file, string $strategy = 'skip'): \Illuminate\Testing\TestResponse
    {
        $previewResponse = $this->actingAs($manager)
            ->post("/store/{$storeSlug}/admin/{$resource}/import", [
                'file' => $file,
                'duplicate_strategy' => $strategy,
            ]);

        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('import_preview');

        $preview = $previewResponse->getSession()->get('import_preview');

        return $this->actingAs($manager)
            ->post("/store/{$storeSlug}/admin/{$resource}/import/confirm", [
                'token' => $preview['token'],
                'duplicate_strategy' => $strategy,
            ]);
    }

    // ─────────────────────────── Export ───────────────────────────

    /**
     * Parse a streamed export into header + rows so assertions describe the column
     * contract instead of matching raw CSV text (which hides column-order drift).
     *
     * @return array{header: array<int, string>, rows: array<int, array<int, ?string>>}
     */
    private function parseExportedCsv(string $content): array
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'export must keep its UTF-8 BOM');

        $lines = array_values(array_filter(
            preg_split('/\r\n|\n/', substr($content, 3)) ?: [],
            fn ($line) => trim((string) $line) !== ''
        ));

        return [
            'header' => str_getcsv((string) array_shift($lines)),
            'rows' => array_map(fn ($line) => str_getcsv((string) $line), $lines),
        ];
    }

    public function test_brand_export_streams_csv_with_bom_and_formula_protection(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'code' => 'XM', 'slug' => 'xiaomi']);
        Brand::create(['store_id' => $store->id, 'name' => '=SUM(A1)', 'slug' => 'formula']);

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/brands/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->parseExportedCsv($response->streamedContent());

        // The Auto-SKU short code sits between Name and Slug; SpreadsheetImportReader
        // normalises that label back to "short_code_for_auto_sku" on import.
        $this->assertSame(['Name', 'Short Code (for Auto-SKU)', 'Slug'], $csv['header']);
        $this->assertCount(2, $csv['rows']);
        $this->assertContains(['Xiaomi', 'XM', 'xiaomi'], $csv['rows']);
        // Formula injection protected: a cell starting with = gets a leading apostrophe.
        $this->assertContains(["'=SUM(A1)", '', 'formula'], $csv['rows']);
    }

    public function test_category_export_streams_csv_with_parent_column(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $main = Category::create(['store_id' => $store->id, 'name' => 'Mobile Phones', 'slug' => 'mobile-phones']);
        Category::create(['store_id' => $store->id, 'name' => 'iPhone Cases', 'code' => 'IPC', 'slug' => 'iphone-cases', 'parent_id' => $main->id]);

        $response = $this->actingAs($manager)->get("/store/{$store->slug}/admin/categories/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $this->parseExportedCsv($response->streamedContent());

        $this->assertSame(
            ['Name', 'Short Code (for Auto-SKU)', 'Parent', 'Slug', 'Description', 'Icon'],
            $csv['header']
        );
        $this->assertCount(2, $csv['rows']);
        $this->assertContains(['Mobile Phones', '', '', 'mobile-phones', '', ''], $csv['rows']);
        $this->assertContains(['iPhone Cases', 'IPC', 'Mobile Phones', 'iphone-cases', '', ''], $csv['rows']);
    }

    /**
     * An export/import drift guard: every column the export writes must be understood
     * by the importer, otherwise a plain "export -> edit in Excel -> import" trip
     * silently drops data (that is exactly how the short-code column went unnoticed).
     */
    public function test_brand_export_round_trips_losslessly_through_import(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'code' => 'XM', 'slug' => 'xiaomi']);

        $exported = $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/brands/export")
            ->streamedContent();

        // Re-import into a store that no longer has the brand.
        $brand->forceDelete();

        $response = $this->previewAndConfirm($manager, $store->slug, 'brands', $this->makeCsv($exported, 'brands-export.csv'));
        $result = $response->getSession()->get('import_result');

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(0, $result['failed'], 'exported columns must all be importable');
        $this->assertEquals(1, $result['imported']);

        $restored = Brand::where('store_id', $store->id)->where('slug', 'xiaomi')->first();
        $this->assertNotNull($restored);
        $this->assertSame('Xiaomi', $restored->name);
        $this->assertSame('XM', $restored->code, 'the Auto-SKU short code must survive the round trip');
    }

    public function test_category_export_round_trips_losslessly_through_import(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $main = Category::create(['store_id' => $store->id, 'name' => 'Mobile Phones', 'code' => 'MP', 'slug' => 'mobile-phones']);
        Category::create(['store_id' => $store->id, 'name' => 'iPhone Cases', 'code' => 'IPC', 'slug' => 'iphone-cases', 'parent_id' => $main->id]);

        $exported = $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/categories/export")
            ->streamedContent();

        Category::where('store_id', $store->id)->delete();

        $response = $this->previewAndConfirm($manager, $store->slug, 'categories', $this->makeCsv($exported, 'categories-export.csv'));
        $result = $response->getSession()->get('import_result');

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(0, $result['failed'], 'exported columns must all be importable');
        $this->assertEquals(2, $result['imported']);

        $restoredMain = Category::where('store_id', $store->id)->where('slug', 'mobile-phones')->first();
        $restoredChild = Category::where('store_id', $store->id)->where('slug', 'iphone-cases')->first();

        $this->assertNotNull($restoredMain);
        $this->assertNotNull($restoredChild);
        $this->assertSame('MP', $restoredMain->code);
        $this->assertSame('IPC', $restoredChild->code);
        $this->assertSame($restoredMain->id, $restoredChild->parent_id, 'the parent column must rebuild the hierarchy');
    }

    public function test_import_templates_download_for_brands_and_categories(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $brandTemplate = $this->actingAs($manager)->get("/store/{$store->slug}/admin/brands/import-template");
        $brandTemplate->assertOk();
        $this->assertStringContainsString('brand-import-template.xlsx', $brandTemplate->headers->get('content-disposition'));

        $categoryTemplate = $this->actingAs($manager)->get("/store/{$store->slug}/admin/categories/import-template");
        $categoryTemplate->assertOk();
        $this->assertStringContainsString('category-import-template.xlsx', $categoryTemplate->headers->get('content-disposition'));
    }

    // ─────────────────────────── Brand import ───────────────────────────

    public function test_brand_import_creates_skips_and_updates(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        Brand::create(['store_id' => $store->id, 'name' => 'Samsung', 'slug' => 'samsung']);

        $csv = "name,slug\n"
            . "Xiaomi,xiaomi\n"
            . "Samsung,samsung\n"
            . "Oppo,oppo\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'brands', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicate']);
        $this->assertEquals(0, $result['failed']);

        $this->assertDatabaseHas('brands', ['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);
        $this->assertDatabaseHas('brands', ['store_id' => $store->id, 'name' => 'Oppo', 'slug' => 'oppo']);
        $this->assertDatabaseHas('import_histories', [
            'store_id' => $store->id,
            'type' => 'brands',
            'total_rows' => 3,
            'success_rows' => 2,
            'failed_rows' => 0,
        ]);
    }

    public function test_brand_import_update_strategy_updates_existing_brand(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $brand = Brand::create(['store_id' => $store->id, 'name' => 'Samsung', 'slug' => 'samsung']);

        $csv = "name,slug\nSamsung,galaxy-samsung\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'brands', $this->makeCsv($csv), 'update');

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(0, $result['imported']);

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'Samsung', 'slug' => 'galaxy-samsung']);
    }

    public function test_brand_import_without_slug_auto_generates_unique_slug(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        Brand::create(['store_id' => $store->id, 'name' => 'Xiaomi', 'slug' => 'xiaomi']);

        $csv = "name\nXiaomi Redmi\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'brands', $this->makeCsv($csv));

        $this->assertDatabaseHas('brands', ['store_id' => $store->id, 'name' => 'Xiaomi Redmi', 'slug' => 'xiaomi-redmi']);
    }

    public function test_brand_import_missing_name_column_fails_cleanly(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "slug\nxiaomi\n";

        $response = $this->actingAs($manager)
            ->post("/store/{$store->slug}/admin/brands/import", [
                'file' => $this->makeCsv($csv),
                'duplicate_strategy' => 'skip',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('brands', 0);
    }

    public function test_brand_import_records_failed_rows_in_error_file(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,slug\n,,bad\nValid Brand,valid-brand\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'brands', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['failed']);

        $history = \App\Models\ImportHistory::where('store_id', $store->id)->where('type', 'brands')->first();
        $this->assertNotNull($history);
        $this->assertNotNull($history->error_file_path);
        $this->assertTrue(Storage::disk('local')->exists($history->error_file_path));
    }

    // ─────────────────────────── Category import ───────────────────────────

    public function test_category_import_creates_main_and_sub_with_same_file_parent(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,slug,parent\n"
            . "Mobile Phones,mobile-phones,\n"
            . "iPhone Cases,iphone-cases,Mobile Phones\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'categories', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        $main = Category::where('store_id', $store->id)->where('slug', 'mobile-phones')->first();
        $this->assertNotNull($main);
        $this->assertNull($main->parent_id);

        $child = Category::where('store_id', $store->id)->where('slug', 'iphone-cases')->first();
        $this->assertNotNull($child);
        $this->assertEquals($main->id, $child->parent_id);
    }

    public function test_category_import_creates_sub_before_parent_in_file(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        // Sub-category listed BEFORE its parent — two-pass processing must still link it.
        $csv = "name,slug,parent\n"
            . "iPhone Cases,iphone-cases,Mobile Phones\n"
            . "Mobile Phones,mobile-phones,\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'categories', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        $child = Category::where('store_id', $store->id)->where('slug', 'iphone-cases')->first();
        $main = Category::where('store_id', $store->id)->where('slug', 'mobile-phones')->first();
        $this->assertEquals($main->id, $child->parent_id);
    }

    public function test_category_import_missing_parent_fails_that_row_only(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $csv = "name,slug,parent\n"
            . "Orphan Child,orphan-child,No Such Parent\n"
            . "Valid Main,valid-main,\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'categories', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['failed']);

        $this->assertDatabaseHas('categories', ['store_id' => $store->id, 'slug' => 'valid-main']);
        $this->assertDatabaseMissing('categories', ['store_id' => $store->id, 'slug' => 'orphan-child']);
    }

    public function test_category_import_skips_duplicates_by_name(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        Category::create(['store_id' => $store->id, 'name' => 'Screens', 'slug' => 'screens']);

        $csv = "name\nScreens\nBatteries\n";

        $response = $this->previewAndConfirm($manager, $store->slug, 'categories', $this->makeCsv($csv));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicate']);

        $this->assertDatabaseHas('categories', ['store_id' => $store->id, 'slug' => 'batteries']);
    }

    public function test_brand_and_category_import_are_store_isolated(): void
    {
        Storage::fake('local');
        [$storeA, $managerA] = $this->makeManagerAndStore();
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b', 'is_active' => true]);

        $csv = "name\nOnlyA\n";

        $this->previewAndConfirm($managerA, $storeA->slug, 'brands', $this->makeCsv($csv));

        $this->assertDatabaseHas('brands', ['store_id' => $storeA->id, 'name' => 'OnlyA']);
        $this->assertDatabaseMissing('brands', ['store_id' => $storeB->id, 'name' => 'OnlyA']);
        $this->assertDatabaseCount('brands', 1);
    }

    public function test_xlsx_import_works_for_categories(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $response = $this->previewAndConfirm($manager, $store->slug, 'categories', $this->makeXlsx([
            ['name', 'slug', 'parent'],
            ['CCTV Cameras', 'cctv-cameras', ''],
            ['Dome Cameras', 'dome-cameras', 'CCTV Cameras'],
        ]));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        $this->assertDatabaseHas('categories', ['store_id' => $store->id, 'slug' => 'cctv-cameras']);
        $this->assertDatabaseHas('categories', ['store_id' => $store->id, 'slug' => 'dome-cameras']);
    }

    public function test_xlsx_import_works_for_brands(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $response = $this->previewAndConfirm($manager, $store->slug, 'brands', $this->makeXlsx([
            ['name', 'slug'],
            ['OnePlus', 'oneplus'],
            ['Vivo', 'vivo'],
        ]));

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        $this->assertDatabaseHas('brands', ['store_id' => $store->id, 'slug' => 'oneplus']);
        $this->assertDatabaseHas('brands', ['store_id' => $store->id, 'slug' => 'vivo']);
    }
}
