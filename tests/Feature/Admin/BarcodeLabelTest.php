<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Services\BarcodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeLabelTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'DataPOS Mobile Hub',
            'slug' => 'datapos-mobile',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'store_manager',
        ]);
        $this->admin->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Remax 20000mAh Powerbank',
            'slug' => 'remax-20000mah-powerbank',
            'sku' => '885912345678',
            'retail_price' => 45000,
            'wholesale_price' => 40000,
        ]);
    }

    public function test_admin_can_access_barcode_designer_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.barcode.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Remax 20000mAh Powerbank');
        $response->assertSee('885912345678');
        $response->assertSee('50mm × 30mm');
    }

    public function test_admin_can_search_products_for_barcode_printing(): void
    {
        $response = $this->actingAs($this->admin)->get(route('store.admin.barcode.search', [
            'store_slug' => $this->store->slug,
            'q' => 'Powerbank',
        ]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Remax 20000mAh Powerbank',
            'code' => '885912345678',
            'price' => 45000,
        ]);
    }

    public function test_admin_can_generate_and_print_thermal_labels(): void
    {
        $items = [
            [
                'id' => "p-{$this->product->id}",
                'product_id' => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->sku,
                'price' => $this->product->retail_price,
                'quantity' => 3,
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('store.admin.barcode.print', [
            'store_slug' => $this->store->slug,
        ]), [
            'preset' => 'thermal_50x30',
            'code_type' => 'barcode_128',
            'show_store_name' => '1',
            'show_product_name' => '1',
            'show_price' => '1',
            'show_code_text' => '1',
            'items_json' => json_encode($items),
        ]);

        $response->assertStatus(200);
        $response->assertSee('DataPOS Mobile Hub');
        $response->assertSee('Remax 20000mAh Powerbank');
        $response->assertSee('45,000 Ks');
        $response->assertSee('50mm 30mm'); // in @page CSS
        $response->assertSee('<svg', false); // Contains SVG barcode
    }

    public function test_admin_can_generate_and_print_a4_sheet_labels(): void
    {
        $items = [
            [
                'id' => "p-{$this->product->id}",
                'product_id' => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->sku,
                'price' => $this->product->retail_price,
                'quantity' => 5,
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('store.admin.barcode.print', [
            'store_slug' => $this->store->slug,
        ]), [
            'preset' => 'a4_24',
            'code_type' => 'barcode_128',
            'show_store_name' => '1',
            'show_product_name' => '1',
            'show_price' => '1',
            'show_code_text' => '1',
            'items_json' => json_encode($items),
        ]);

        $response->assertStatus(200);
        $response->assertSee('A4 portrait');
    }

    public function test_barcode_generator_service_encodes_code128_svg_correctly(): void
    {
        $service = app(BarcodeGeneratorService::class);
        $svg = $service->generateCode128Svg('ITEM-998877', 40, 1.6, true);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<rect', $svg);
        $this->assertStringContainsString('ITEM-998877', $svg);
    }

    public function test_admin_can_save_and_delete_custom_barcode_template(): void
    {
        $saveResponse = $this->actingAs($this->admin)->postJson(route('store.admin.barcode.templates.save', [
            'store_slug' => $this->store->slug,
        ]), [
            'name' => 'Custom Jewelry Tag 45x25',
            'type' => 'thermal',
            'width_mm' => 45.0,
            'height_mm' => 25.0,
            'gap_x_mm' => 2.0,
            'gap_y_mm' => 2.0,
            'bar_height' => 20,
            'code_type' => 'barcode_128',
            'show_store_name' => true,
            'show_product_name' => true,
            'show_price' => true,
        ]);

        $saveResponse->assertStatus(200);
        $saveResponse->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('barcode_templates', [
            'store_id' => $this->store->id,
            'name' => 'Custom Jewelry Tag 45x25',
            'width_mm' => 45.0,
        ]);

        $template = \App\Models\BarcodeTemplate::where('store_id', $this->store->id)->where('name', 'Custom Jewelry Tag 45x25')->first();
        $this->assertNotNull($template);

        // Delete template
        $deleteResponse = $this->actingAs($this->admin)->deleteJson(route('store.admin.barcode.templates.delete', [
            'store_slug' => $this->store->slug,
            'id' => $template->id,
        ]));

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('barcode_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_admin_can_print_with_custom_dimensions_override(): void
    {
        $items = [
            [
                'id' => "p-{$this->product->id}",
                'product_id' => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->barcode,
                'price' => $this->product->retail_price,
                'quantity' => 2,
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('store.admin.barcode.print', [
            'store_slug' => $this->store->slug,
        ]), [
            'is_custom_override' => '1',
            'custom_name' => 'Custom 45x25mm Label',
            'custom_type' => 'thermal',
            'custom_width_mm' => 45,
            'custom_height_mm' => 25,
            'custom_gap_x_mm' => 2,
            'custom_gap_y_mm' => 2,
            'code_type' => 'qr_code',
            'show_store_name' => '1',
            'show_product_name' => '1',
            'show_price' => '1',
            'show_code_text' => '1',
            'items_json' => json_encode($items),
        ]);

        $response->assertStatus(200);
        $response->assertSee('45mm 25mm');
        $response->assertSee('<svg', false);
    }

    public function test_admin_can_export_barcode_products_excel_and_csv(): void
    {
        // Test Excel (.xlsx) export
        $excelResponse = $this->actingAs($this->admin)->get(route('store.admin.barcode.export', [
            'store_slug' => $this->store->slug,
            'format' => 'xlsx',
        ]));

        $excelResponse->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $excelResponse->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $excelResponse->headers->get('content-disposition'));

        // Test CSV export
        $csvResponse = $this->actingAs($this->admin)->get(route('store.admin.barcode.export', [
            'store_slug' => $this->store->slug,
            'format' => 'csv',
        ]));

        $csvResponse->assertStatus(200);
        $this->assertStringContainsString('text/csv', $csvResponse->headers->get('content-type'));
        $content = $csvResponse->streamedContent();
        $this->assertStringContainsString('Remax 20000mAh Powerbank', $content);
        $this->assertStringContainsString('885912345678', $content);
    }
}

