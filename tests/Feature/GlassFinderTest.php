<?php

namespace Tests\Feature;

use App\Models\GlassFavorite;
use App\Models\GlassFinderItem;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use App\Services\GlassCodeNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class GlassFinderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<int, array<int, string|int|float|null>> $rows
     */
    private function makeXlsx(array $rows, string $filename = 'glass.xlsx'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'glass_import_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function makeProductAndGlassWorkbook(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'glass_import_') . '.xlsx';
        $spreadsheet = new Spreadsheet();

        $productSheet = $spreadsheet->getActiveSheet();
        $productSheet->setTitle('Products');
        $productSheet->fromArray([
            ['name', 'sku', 'brand', 'category', 'retail_price', 'wholesale_price', 'stock_status'],
            ['Phone', 'SKU-001', 'Apple', 'Mobile Phone', 1000, 900, 'in_stock'],
        ]);

        $glassSheet = $spreadsheet->createSheet();
        $glassSheet->setTitle('Glass Finder');
        $glassSheet->fromArray([
            ['brand', 'phone_model', 'glass_code', 'stock_status'],
            ['Apple', 'iPhone 15 Pro Max', 'IP15PM-TG01', 'in_stock'],
        ]);

        $spreadsheet->setActiveSheetIndex(0);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile($path, 'multi-sheet.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function previewAndConfirm(User $staff, string $storeSlug, UploadedFile $file)
    {
        $previewResponse = $this->actingAs($staff)
            ->post("/store/{$storeSlug}/admin/glass-finder/import", ['file' => $file]);

        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('import_preview');

        $preview = $previewResponse->getSession()->get('import_preview');

        $confirmResponse = $this->actingAs($staff)
            ->post("/store/{$storeSlug}/admin/glass-finder/import/confirm", [
                'token' => $preview['token'],
            ]);

        $confirmResponse->assertRedirect();
        $confirmResponse->assertSessionHas('import_result');

        return $confirmResponse;
    }

    public function test_glass_code_normalizer_formatting(): void
    {
        $this->assertEquals('glass001', GlassCodeNormalizer::normalize(' GLASS-001 '));
        $this->assertEquals('glass001', GlassCodeNormalizer::normalize('glass 001'));
        $this->assertEquals('glass001', GlassCodeNormalizer::normalize('Glass_001'));
    }

    public function test_admin_can_crud_glass_finder_item_with_store_isolation(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);

        $staffA = User::create([
            'name' => 'Staff A',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staffA->stores()->attach($storeA->id, ['role' => 'staff', 'status' => 'active']);

        // Create Item in Store A
        $response = $this->actingAs($staffA)->post('/store/store-a/admin/glass-finder', [
            'brand' => 'Apple',
            'phone_model' => 'iPhone XR',
            'glass_code' => 'GX-001',
            'stock_status' => 'in_stock',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('glass_finder_items', [
            'store_id' => $storeA->id,
            'phone_model' => 'iPhone XR',
            'normalized_glass_code' => 'gx001',
        ]);

        // Unauthorized Store B Access
        $responseUnauth = $this->actingAs($staffA)->post('/store/store-b/admin/glass-finder', [
            'brand' => 'Hacked',
            'phone_model' => 'Hacked Model',
            'glass_code' => 'HACK',
            'stock_status' => 'in_stock',
        ]);
        $responseUnauth->assertStatus(403);
    }

    public function test_csv_import_normalization_and_compatibility_search(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $staff = User::create([
            'name' => 'Staff Main',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone XR', 'glass_code' => 'GX-001', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 11', 'glass_code' => 'GX-001', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone SE 2020', 'glass_code' => 'GX-001', 'stock_status' => 'out_of_stock']);

        // Search Compatibility for iPhone XR
        $responseSearch = $this->get('/glass-finder?store_slug=main-store&phone_model=iPhone+XR');
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('iPhone XR');
        $responseSearch->assertSee('iPhone 11');
        $responseSearch->assertSee('iPhone SE 2020');
    }

    public function test_model_search_returns_all_matching_glass_code_groups_separately(): void
    {
        $store = Store::create(['name' => 'Multi Code Store', 'slug' => 'multi-code-store']);

        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'G-I15PM-B', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'IP15PM-TG01', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro', 'glass_code' => 'IP15PM-TG01', 'stock_status' => 'out_of_stock']);

        $response = $this->get('/glass-finder?store_slug=multi-code-store&phone_model=iPhone+15+Pro+Max');

        $response->assertStatus(200);
        $response->assertSee('data-normalized-code="gi15pmb"', false);
        $response->assertSee('data-normalized-code="ip15pmtg01"', false);
        $response->assertSeeText('G-I15PM-B');
        $response->assertSeeText('IP15PM-TG01');
        $response->assertSeeText('iPhone 15 Pro Max');
        $response->assertSeeText('iPhone 15 Pro');
    }

    public function test_exact_code_search_returns_only_that_normalized_group(): void
    {
        $store = Store::create(['name' => 'Exact Code Store', 'slug' => 'exact-code-store']);

        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'G-I15PM-B', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'IP15PM-TG01', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro', 'glass_code' => 'IP15PM-TG01', 'stock_status' => 'in_stock']);

        $response = $this->get('/glass-finder?store_slug=exact-code-store&glass_code=IP15PM-TG01');

        $response->assertStatus(200);
        $response->assertSee('data-normalized-code="ip15pmtg01"', false);
        $response->assertDontSee('data-normalized-code="gi15pmb"', false);
        $response->assertSeeText('iPhone 15 Pro');
    }

    public function test_exact_code_search_supports_legacy_non_normalized_rows_during_transition(): void
    {
        $store = Store::create(['name' => 'Legacy Code Store', 'slug' => 'legacy-code-store']);

        DB::table('glass_finder_items')->insert([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone 15 Pro Max',
            'glass_code' => 'G-I15PM-B',
            'normalized_glass_code' => 'G-I15PM-B',
            'stock_status' => 'in_stock',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/glass-finder?store_slug=legacy-code-store&glass_code=G-I15PM-B');

        $response->assertStatus(200);
        $response->assertSee('data-normalized-code="G-I15PM-B"', false);
        $response->assertSeeText('iPhone 15 Pro Max');
    }

    public function test_brand_filter_combines_with_model_and_code_search(): void
    {
        $store = Store::create(['name' => 'Brand Filter Store', 'slug' => 'brand-filter-store']);

        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'IP15PM-TG01', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $store->id, 'brand' => 'Generic', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'GEN-IP15PM', 'stock_status' => 'in_stock']);

        $modelResponse = $this->get('/glass-finder?store_slug=brand-filter-store&brand=Apple&phone_model=iPhone+15+Pro+Max');
        $modelResponse->assertStatus(200);
        $modelResponse->assertSee('data-normalized-code="ip15pmtg01"', false);
        $modelResponse->assertDontSee('data-normalized-code="genip15pm"', false);

        $codeResponse = $this->get('/glass-finder?store_slug=brand-filter-store&brand=Generic&glass_code=GEN-IP15PM');
        $codeResponse->assertStatus(200);
        $codeResponse->assertSee('data-normalized-code="genip15pm"', false);
        $codeResponse->assertDontSee('data-normalized-code="ip15pmtg01"', false);
    }

    public function test_model_search_preserves_store_isolation(): void
    {
        $storeA = Store::create(['name' => 'Search Store A', 'slug' => 'search-store-a']);
        $storeB = Store::create(['name' => 'Search Store B', 'slug' => 'search-store-b']);

        GlassFinderItem::create(['store_id' => $storeA->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'A-CODE', 'stock_status' => 'in_stock']);
        GlassFinderItem::create(['store_id' => $storeB->id, 'brand' => 'Apple', 'phone_model' => 'iPhone 15 Pro Max', 'glass_code' => 'B-CODE', 'stock_status' => 'in_stock']);

        $response = $this->get('/glass-finder?store_slug=search-store-a&phone_model=iPhone+15+Pro+Max');

        $response->assertStatus(200);
        $response->assertSee('data-normalized-code="acode"', false);
        $response->assertDontSee('data-normalized-code="bcode"', false);
    }

    public function test_glass_finder_import_creates_items_and_skips_duplicates(): void
    {
        Storage::fake('local');

        $store = Store::create(['name' => 'Import Store', 'slug' => 'import-store']);
        $staff = User::create([
            'name' => 'Import Staff',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone XR',
            'glass_code' => 'GX-001',
            'stock_status' => 'in_stock',
        ]);

        $csv = "brand,phone_model,glass_code,stock_status\n"
            . "Apple,iPhone XR,GX 001,in_stock\n"
            . "Samsung,A52,SM-A52,out_of_stock\n";

        $path = tempnam(sys_get_temp_dir(), 'glass_import_') . '.csv';
        file_put_contents($path, $csv);
        $file = new UploadedFile($path, 'glass.csv', 'text/csv', null, true);

        $previewResponse = $this->actingAs($staff)
            ->post('/store/import-store/admin/glass-finder/import', ['file' => $file]);

        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('import_preview');
        $preview = $previewResponse->getSession()->get('import_preview');
        $this->assertEquals(2, $preview['total']);
        $this->assertEquals(1, $preview['valid_rows']);
        $this->assertEquals(1, $preview['duplicate_rows']);
        $this->assertEquals(0, $preview['failed']);

        $confirmResponse = $this->actingAs($staff)
            ->post('/store/import-store/admin/glass-finder/import/confirm', ['token' => $preview['token']]);

        $confirmResponse->assertRedirect();
        $confirmResponse->assertSessionHas('import_result');

        $result = $confirmResponse->getSession()->get('import_result');
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(1, $result['skipped_duplicate']);

        $this->assertDatabaseHas('glass_finder_items', [
            'store_id' => $store->id,
            'phone_model' => 'A52',
            'normalized_glass_code' => 'sma52',
            'stock_status' => 'out_of_stock',
        ]);

        $this->assertDatabaseHas('import_histories', [
            'store_id' => $store->id,
            'type' => 'glass_finder',
            'total_rows' => 2,
            'success_rows' => 1,
            'failed_rows' => 0,
        ]);
    }

    public function test_glass_finder_xlsx_import_creates_items(): void
    {
        Storage::fake('local');

        $store = Store::create(['name' => 'XLSX Store', 'slug' => 'xlsx-store']);
        $staff = User::create([
            'name' => 'XLSX Staff',
            'phone' => '09888888881',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        $file = $this->makeXlsx([
            ['brand', 'phone_model', 'glass_code', 'stock_status'],
            ['Apple', 'iPhone 15', 'GX 015', 'in_stock'],
        ]);

        $response = $this->previewAndConfirm($staff, 'xlsx-store', $file);

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['imported']);

        $this->assertDatabaseHas('glass_finder_items', [
            'store_id' => $store->id,
            'phone_model' => 'iPhone 15',
            'normalized_glass_code' => 'gx015',
        ]);
    }

    public function test_glass_finder_import_preview_reports_invalid_rows(): void
    {
        Storage::fake('local');

        $store = Store::create(['name' => 'Invalid Import Store', 'slug' => 'invalid-import-store']);
        $staff = User::create([
            'name' => 'Invalid Import Staff',
            'phone' => '09888888883',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        $file = $this->makeXlsx([
            ['brand', 'phone_model', 'glass_code', 'stock_status'],
            ['Apple', 'iPhone 15', 'GX-015', 'invalid_status'],
        ]);

        $response = $this->actingAs($staff)
            ->post('/store/invalid-import-store/admin/glass-finder/import', ['file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('import_preview');

        $preview = $response->getSession()->get('import_preview');
        $this->assertEquals(1, $preview['total']);
        $this->assertEquals(0, $preview['valid_rows']);
        $this->assertEquals(1, $preview['failed']);
        $this->assertDatabaseMissing('glass_finder_items', ['store_id' => $store->id, 'phone_model' => 'iPhone 15']);
    }

    public function test_glass_finder_import_reads_named_sheet_from_multi_sheet_workbook(): void
    {
        Storage::fake('local');

        $store = Store::create(['name' => 'Multi Sheet Store', 'slug' => 'multi-sheet-store']);
        $staff = User::create([
            'name' => 'Multi Sheet Staff',
            'phone' => '09888888882',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        $response = $this->previewAndConfirm($staff, 'multi-sheet-store', $this->makeProductAndGlassWorkbook());

        $result = $response->getSession()->get('import_result');
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['failed']);

        $this->assertDatabaseHas('glass_finder_items', [
            'store_id' => $store->id,
            'phone_model' => 'iPhone 15 Pro Max',
            'normalized_glass_code' => 'ip15pmtg01',
        ]);
    }

    public function test_glass_finder_import_store_isolation(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'slug' => 'store-a']);
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b']);
        $staffA = User::create([
            'name' => 'Staff A',
            'phone' => '09666666666',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staffA->stores()->attach($storeA->id, ['role' => 'staff', 'status' => 'active']);

        $path = tempnam(sys_get_temp_dir(), 'glass_import_') . '.csv';
        file_put_contents($path, "brand,phone_model,glass_code,stock_status\nApple,iPhone 12,GX-12,in_stock\n");
        $file = new UploadedFile($path, 'glass.csv', 'text/csv', null, true);

        $response = $this->actingAs($staffA)
            ->post('/store/store-b/admin/glass-finder/import', ['file' => $file]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('glass_finder_items', ['store_id' => $storeB->id]);
    }

    public function test_favorite_creation_and_guest_behavior(): void
    {
        $store = Store::create(['name' => 'Main Store', 'slug' => 'main-store']);
        $item = GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone 11',
            'glass_code' => 'GX-001',
            'stock_status' => 'in_stock',
        ]);

        // 1. Guest Favorite -> Saved locally JSON response
        $responseGuest = $this->postJson('/glass-finder/favorite', [
            'glass_finder_item_id' => $item->id,
        ]);
        $responseGuest->assertStatus(200);
        $responseGuest->assertJson(['message' => 'Guest favorite saved locally']);

        // 2. Logged-in User Favorite -> Saved in database
        $user = User::create([
            'name' => 'User Fav',
            'phone' => '09777777777',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $responseAuth = $this->actingAs($user)->postJson('/glass-finder/favorite', [
            'glass_finder_item_id' => $item->id,
        ]);
        $responseAuth->assertStatus(200);
        $this->assertDatabaseHas('glass_favorites', [
            'user_id' => $user->id,
            'glass_finder_item_id' => $item->id,
        ]);
    }

    public function test_favorite_explicit_action_never_creates_ghost_remove(): void
    {
        $store = Store::create(['name' => 'Ghost Store', 'slug' => 'ghost-store']);
        $item = GlassFinderItem::create([
            'store_id' => $store->id,
            'brand' => 'Apple',
            'phone_model' => 'iPhone 12',
            'glass_code' => 'GX-012',
            'stock_status' => 'in_stock',
        ]);

        $user = User::create([
            'name' => 'User Ghost',
            'phone' => '09777777778',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Scenario: guest favorited locally, then logged in and un-favorited.
        // The browser now sends action=remove; no server row exists yet, so
        // the server must NOT create one (previously the blind toggle would).
        $responseRemove = $this->actingAs($user)->postJson('/glass-finder/favorite', [
            'glass_finder_item_id' => $item->id,
            'action' => 'remove',
        ]);
        $responseRemove->assertStatus(200);
        $responseRemove->assertJson(['status' => 'removed']);
        $this->assertDatabaseMissing('glass_favorites', [
            'user_id' => $user->id,
            'glass_finder_item_id' => $item->id,
        ]);

        // Repeating an add when the row already exists stays a single row.
        $this->actingAs($user)->postJson('/glass-finder/favorite', [
            'glass_finder_item_id' => $item->id,
            'action' => 'add',
        ])->assertJson(['status' => 'added']);
        $this->actingAs($user)->postJson('/glass-finder/favorite', [
            'glass_finder_item_id' => $item->id,
            'action' => 'add',
        ])->assertJson(['status' => 'added']);

        $this->assertSame(
            1,
            GlassFavorite::where('user_id', $user->id)
                ->where('glass_finder_item_id', $item->id)
                ->count(),
            'Duplicate add must not create duplicate rows.'
        );

        // An explicit remove after an add deletes the row.
        $this->actingAs($user)->postJson('/glass-finder/favorite', [
            'glass_finder_item_id' => $item->id,
            'action' => 'remove',
        ])->assertJson(['status' => 'removed']);
        $this->assertDatabaseMissing('glass_favorites', [
            'user_id' => $user->id,
            'glass_finder_item_id' => $item->id,
        ]);

        // An invalid action is rejected.
        $this->actingAs($user)->postJson('/glass-finder/favorite', [
            'glass_finder_item_id' => $item->id,
            'action' => 'toggle',
        ])->assertStatus(422);
    }

    public function test_app_data_sheet_format_imports_with_spaced_stock_values(): void
    {
        $store = Store::create(['name' => 'ACDC Store', 'slug' => 'acdc-store']);
        $staff = User::create([
            'name' => 'Staff ACDC',
            'phone' => '09888888888',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $staff->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        // The ACDC AppSheet export has no "Glass Finder" sheet — data lives in
        // "App_Data" with human-readable stock values ("Out Of Stock").
        $path = tempnam(sys_get_temp_dir(), 'acdc_') . '.xlsx';
        $spreadsheet = new Spreadsheet();
        $appData = $spreadsheet->getActiveSheet();
        $appData->setTitle('App_Data');
        $appData->fromArray([
            ['Record_ID', 'Glass_Code', 'Brand', 'Phone_Model', 'Search_Key', 'Stock_Status', 'Active', 'Verified', 'Remark'],
            ['K101-0001', 'K101', 'Apple', 'iPhone 16 Pro Max', 'iphone16promax', 'Out Of Stock', true, true, 'အတည်ပြုပြီး'],
            ['K104-0002', 'K104', 'Apple', 'iPhone 16 Pro', 'iphone16pro', 'In Stock', true, true, 'အတည်ပြုပြီး'],
            ['', '', '', '', '', '', false, false, ''],
        ]);
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile($path, 'ACDC Mobile Glass List.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->previewAndConfirm($staff, 'acdc-store', $file);

        // Only the active rows are imported (the blank inactive row is skipped).
        $this->assertDatabaseHas('glass_finder_items', [
            'store_id' => $store->id,
            'phone_model' => 'iPhone 16 Pro Max',
            'glass_code' => 'K101',
            'stock_status' => 'out_of_stock',
        ]);
        $this->assertDatabaseHas('glass_finder_items', [
            'store_id' => $store->id,
            'phone_model' => 'iPhone 16 Pro',
            'glass_code' => 'K104',
            'stock_status' => 'in_stock',
        ]);
        $this->assertEquals(2, GlassFinderItem::where('store_id', $store->id)->count());
    }
}
