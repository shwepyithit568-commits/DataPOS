<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductMasterPreset;
use App\Models\Store;
use App\Models\User;
use App\Models\VariantPreset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterDataExportImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'store_manager',
        ]);

        $this->store = Store::create([
            'slug' => 'test-store',
            'name' => 'Test Store',
        ]);

        $this->user->stores()->attach($this->store->id, ['role' => 'store_manager']);
    }

    public function test_master_data_tab_renders_toolbar_with_export_import(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/store/{$this->store->slug}/admin/products/master-data?tab=connectors");

        $response->assertStatus(200);
        $response->assertSee('/admin/product-master-presets/export?type=connector_spec', false);
        $response->assertSee('/admin/product-master-presets/import?type=connector_spec', false);

        $responseVariants = $this->actingAs($this->user)
            ->get("/store/{$this->store->slug}/admin/products/master-data?tab=variant-presets");

        $responseVariants->assertStatus(200);
        $responseVariants->assertSee('/admin/variant-presets/export', false);
        $responseVariants->assertSee('/admin/variant-presets/import', false);
    }

    public function test_master_preset_csv_and_xlsx_export(): void
    {
        ProductMasterPreset::create([
            'store_id' => $this->store->id,
            'type' => 'connector_spec',
            'code' => 'TYPEC',
            'name' => 'USB Type-C',
            'content' => 'High speed charging',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // CSV Export
        $csvResponse = $this->actingAs($this->user)
            ->get("/store/{$this->store->slug}/admin/product-master-presets/export?type=connector_spec&format=csv");

        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('Content-Disposition');

        // XLSX Export
        $xlsxResponse = $this->actingAs($this->user)
            ->get("/store/{$this->store->slug}/admin/product-master-presets/export?type=connector_spec&format=xlsx");

        $xlsxResponse->assertStatus(200);
    }

    public function test_master_preset_template_download(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/store/{$this->store->slug}/admin/product-master-presets/import-template?type=color");

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    public function test_master_preset_import_preview_and_confirm(): void
    {
        Storage::fake('local');

        $csvContent = "\xEF\xBB\xBFtype,code,name,color_hex,content,sort_order,is_active\n"
            . "color,RED,Crimson Red,#FF0000,Bright red color,1,1\n"
            . "color,BLU,Ocean Blue,#0000FF,Deep blue color,2,1\n";

        $file = UploadedFile::fake()->createWithContent('colors.csv', $csvContent);

        $previewResponse = $this->actingAs($this->user)
            ->post("/store/{$this->store->slug}/admin/product-master-presets/import", [
                'type' => 'color',
                'file' => $file,
                'duplicate_strategy' => 'skip',
            ]);

        $previewResponse->assertSessionHas('import_preview');
        $previewData = session('import_preview');
        $this->assertEquals(2, $previewData['total']);
        $this->assertEquals(2, $previewData['creatable']);

        $token = $previewData['token'];

        $confirmResponse = $this->actingAs($this->user)
            ->post("/store/{$this->store->slug}/admin/product-master-presets/import/confirm", [
                'token' => $token,
                'duplicate_strategy' => 'skip',
            ]);

        $confirmResponse->assertRedirect("/store/{$this->store->slug}/admin/products/master-data?tab=colors");

        $this->assertDatabaseHas('product_master_presets', [
            'store_id' => $this->store->id,
            'type' => 'color',
            'code' => 'RED',
            'name' => 'Crimson Red',
        ]);
        $this->assertDatabaseHas('product_master_presets', [
            'store_id' => $this->store->id,
            'type' => 'color',
            'code' => 'BLU',
            'name' => 'Ocean Blue',
        ]);
    }

    public function test_variant_preset_csv_and_xlsx_export(): void
    {
        VariantPreset::create([
            'store_id' => $this->store->id,
            'name' => 'Mobile Storage',
            'category_family' => 'mobile',
            'options' => [
                ['name' => 'Storage', 'values' => ['128GB', '256GB']],
            ],
            'sort_order' => 1,
        ]);

        $csvResponse = $this->actingAs($this->user)
            ->get("/store/{$this->store->slug}/admin/variant-presets/export?format=csv");

        $csvResponse->assertStatus(200);

        $xlsxResponse = $this->actingAs($this->user)
            ->get("/store/{$this->store->slug}/admin/variant-presets/export?format=xlsx");

        $xlsxResponse->assertStatus(200);
    }

    public function test_variant_preset_import_preview_and_confirm(): void
    {
        Storage::fake('local');

        $csvContent = "\xEF\xBB\xBFname,category_family,option_name,option_values,sort_order\n"
            . "Shoe Sizes,fashion,Size,\"38, 39, 40, 41, 42\",1\n";

        $file = UploadedFile::fake()->createWithContent('variants.csv', $csvContent);

        $previewResponse = $this->actingAs($this->user)
            ->post("/store/{$this->store->slug}/admin/variant-presets/import", [
                'file' => $file,
                'duplicate_strategy' => 'skip',
            ]);

        $previewResponse->assertSessionHas('import_preview');
        $previewData = session('import_preview');
        $this->assertEquals(1, $previewData['total']);
        $this->assertEquals(1, $previewData['creatable']);

        $token = $previewData['token'];

        $confirmResponse = $this->actingAs($this->user)
            ->post("/store/{$this->store->slug}/admin/variant-presets/import/confirm", [
                'token' => $token,
                'duplicate_strategy' => 'skip',
            ]);

        $confirmResponse->assertRedirect("/store/{$this->store->slug}/admin/products/master-data?tab=variant-presets");

        $this->assertDatabaseHas('variant_presets', [
            'store_id' => $this->store->id,
            'name' => 'Shoe Sizes',
            'category_family' => 'fashion',
        ]);
    }

    public function test_category_and_brand_export_xlsx_and_csv(): void
    {
        Category::create([
            'store_id' => $this->store->id,
            'name' => 'Smartphones',
            'slug' => 'smartphones',
        ]);

        Brand::create([
            'store_id' => $this->store->id,
            'name' => 'Apple',
            'slug' => 'apple',
        ]);

        $catCsv = $this->actingAs($this->user)->get("/store/{$this->store->slug}/admin/categories/export?format=csv");
        $catCsv->assertStatus(200);

        $catXlsx = $this->actingAs($this->user)->get("/store/{$this->store->slug}/admin/categories/export?format=xlsx");
        $catXlsx->assertStatus(200);

        $brandCsv = $this->actingAs($this->user)->get("/store/{$this->store->slug}/admin/brands/export?format=csv");
        $brandCsv->assertStatus(200);

        $brandXlsx = $this->actingAs($this->user)->get("/store/{$this->store->slug}/admin/brands/export?format=xlsx");
        $brandXlsx->assertStatus(200);
    }
}
