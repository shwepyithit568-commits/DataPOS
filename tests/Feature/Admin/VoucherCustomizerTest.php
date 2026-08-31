<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use App\Models\VoucherTemplate;
use App\POS\Services\VoucherTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherCustomizerTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'test-voucher-store',
            'name' => 'Test Voucher Store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff']);
    }

    public function test_voucher_customizer_index_renders_with_default_templates(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.vouchers.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee('80mm');
        $response->assertSee('58mm');
        $response->assertSee('A4');
        $response->assertSee('A5');
        $response->assertSee(__('messages.vouchers_title'));
    }

    public function test_create_custom_80mm_voucher_template(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.vouchers.store', ['store_slug' => $this->store->slug]), [
                'name' => 'VIP Gold 80mm Thermal Receipt',
                'paper_size' => '80mm',
                'style_preset' => 'modern_tech',
                'header_title' => 'VIP Gold Tech Store',
                'header_subtitle' => 'Premium Gadgets & Warranty Support',
                'show_logo' => 1,
                'address' => 'Junction City, Yangon',
                'phone' => '09-999888777',
                'show_qr' => 1,
                'qr_type' => 'kpay',
                'qr_label' => 'Scan with KBZPay',
                'show_customer_info' => 1,
                'show_cashier_name' => 1,
                'show_tax_breakdown' => 1,
                'show_discount_line' => 1,
                'show_barcode' => 1,
                'footer_greeting' => 'Thank you for your VIP purchase!',
                'footer_policy' => 'Official 1-Year Brand Warranty Included.',
                'font_size' => 'medium',
                'is_default' => 0,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('voucher_templates', [
            'store_id' => $this->store->id,
            'name' => 'VIP Gold 80mm Thermal Receipt',
            'paper_size' => '80mm',
            'style_preset' => 'modern_tech',
            'header_title' => 'VIP Gold Tech Store',
        ]);
    }

    public function test_create_a4_invoice_template(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.vouchers.store', ['store_slug' => $this->store->slug]), [
                'name' => 'Commercial B2B Tax Invoice A4',
                'paper_size' => 'a4',
                'style_preset' => 'classic_border',
                'header_title' => 'DataPOS Enterprise Myanmar Ltd.',
                'header_subtitle' => 'Corporate IT Equipment & POS Systems',
                'address' => 'Sule Square, Yangon',
                'phone' => '01-2345678',
                'show_qr' => 1,
                'qr_type' => 'bank',
                'qr_label' => 'CB / AYA Bank Direct Transfer',
                'show_customer_info' => 1,
                'show_cashier_name' => 1,
                'show_tax_breakdown' => 1,
                'show_discount_line' => 1,
                'show_barcode' => 1,
                'footer_greeting' => 'Thank you for choosing DataPOS Enterprise!',
                'footer_policy' => 'Payment terms: 30 days net.',
                'font_size' => 'medium',
                'is_default' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('voucher_templates', [
            'store_id' => $this->store->id,
            'name' => 'Commercial B2B Tax Invoice A4',
            'paper_size' => 'a4',
            'is_default' => 1,
        ]);
    }

    public function test_update_voucher_template_branding_and_qr(): void
    {
        $service = app(VoucherTemplateService::class);
        $service->ensureDefaultTemplates($this->store);

        $template = VoucherTemplate::where('store_id', $this->store->id)->where('paper_size', '80mm')->first();

        $response = $this->actingAs($this->manager)
            ->put(route('store.admin.vouchers.update', [
                'store_slug' => $this->store->slug,
                'voucher' => $template->id,
            ]), [
                'name' => 'Updated 80mm Receipt',
                'paper_size' => '80mm',
                'style_preset' => 'clean_minimal',
                'header_title' => 'Updated Store Name Header',
                'address' => 'Mandalay 78th Street',
                'phone' => '09-777666555',
                'qr_type' => 'wave',
                'qr_label' => 'Scan with WavePay App',
                'font_size' => 'large',
                'show_qr' => 1,
                'show_customer_info' => 1,
                'show_cashier_name' => 1,
                'show_tax_breakdown' => 1,
                'show_discount_line' => 1,
                'show_barcode' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $template->refresh();
        $this->assertEquals('Updated Store Name Header', $template->header_title);
        $this->assertEquals('Mandalay 78th Street', $template->address);
        $this->assertEquals('wave', $template->qr_type);
        $this->assertEquals('large', $template->font_size);
    }

    public function test_set_default_voucher_template(): void
    {
        $service = app(VoucherTemplateService::class);
        $service->ensureDefaultTemplates($this->store);

        $newTmpl = VoucherTemplate::create([
            'store_id' => $this->store->id,
            'name' => 'Second 80mm Template',
            'paper_size' => '80mm',
            'style_preset' => 'modern_tech',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.vouchers.set_default', [
                'store_slug' => $this->store->slug,
                'voucher' => $newTmpl->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($newTmpl->fresh()->is_default);
    }

    public function test_preview_sample_voucher_renders_200(): void
    {
        $service = app(VoucherTemplateService::class);
        $service->ensureDefaultTemplates($this->store);

        $template = VoucherTemplate::where('store_id', $this->store->id)->first();

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.vouchers.preview', [
                'store_slug' => $this->store->slug,
                'voucher' => $template->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee(__('messages.vouchers_print_sample'));
        $response->assertSee($template->header_title);
    }

    public function test_delete_custom_template(): void
    {
        $service = app(VoucherTemplateService::class);
        $service->ensureDefaultTemplates($this->store);

        $template = VoucherTemplate::create([
            'store_id' => $this->store->id,
            'name' => 'Temporary To Delete',
            'paper_size' => '80mm',
            'style_preset' => 'clean_minimal',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.vouchers.destroy', [
                'store_slug' => $this->store->slug,
                'voucher' => $template->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('voucher_templates', ['id' => $template->id]);
    }

    public function test_voucher_views_render_without_translation_leaks_in_all_locales(): void
    {
        $service = app(VoucherTemplateService::class);
        $service->ensureDefaultTemplates($this->store);

        $template = VoucherTemplate::where('store_id', $this->store->id)->first();

        foreach (['en', 'my', 'zh_CN'] as $locale) {
            // Test Index View
            $indexResponse = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get(route('store.admin.vouchers.index', ['store_slug' => $this->store->slug]));

            $indexResponse->assertOk();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $indexResponse->getContent()),
                "Found leaked translation key in locale [{$locale}] on vouchers.index"
            );

            // Test Preview View
            $previewResponse = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get(route('store.admin.vouchers.preview', [
                    'store_slug' => $this->store->slug,
                    'voucher' => $template->id,
                ]));

            $previewResponse->assertOk();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $previewResponse->getContent()),
                "Found leaked translation key in locale [{$locale}] on vouchers.preview"
            );
        }
    }
}
