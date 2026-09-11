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

    public function test_voucher_template_update_dynamically_reflects_in_printable_documents(): void
    {
        $service = app(VoucherTemplateService::class);
        $service->ensureDefaultTemplates($this->store);

        // 1. Customize 80mm template
        $tmpl80 = VoucherTemplate::where('store_id', $this->store->id)->where('paper_size', '80mm')->first();
        $tmpl80->update([
            'header_title' => 'Custom 80mm Dynamic Title',
            'footer_greeting' => 'Custom 80mm Dynamic Greeting',
        ]);

        // 2. Customize A4 template
        $tmplA4 = VoucherTemplate::where('store_id', $this->store->id)->where('paper_size', 'a4')->first();
        $tmplA4->update([
            'header_title' => 'Custom A4 Dynamic Corporate Title',
            'footer_policy' => 'Custom A4 Dynamic Warranty Policy',
        ]);

        // 3. Customize A5 template
        $tmplA5 = VoucherTemplate::where('store_id', $this->store->id)->where('paper_size', 'a5')->first();
        $tmplA5->update([
            'header_title' => 'Custom A5 Dynamic Finance Title',
            'footer_greeting' => 'Custom A5 Dynamic Finance Greeting',
        ]);

        // Test A4 Warranty Certificate reflects VoucherTemplate
        $warranty = \App\POS\Models\DeviceWarranty::create([
            'store_id' => $this->store->id,
            'product_name' => 'Dell Laptop Inspiron',
            'serial_number' => 'DELL-DYN-001',
            'purchase_date' => now(),
            'warranty_duration_months' => 12,
            'warranty_expiry_date' => now()->addYear(),
            'warranty_type' => 'official_brand',
            'status' => 'active',
        ]);

        $certResponse = $this->actingAs($this->manager)->get(route('store.admin.warranty.certificate', [
            'store_slug' => $this->store->slug,
            'warranty' => $warranty->id,
        ]));
        $certResponse->assertOk();
        $certResponse->assertSee('Custom A4 Dynamic Corporate Title');
        $certResponse->assertSee('Custom A4 Dynamic Warranty Policy');

        // Test A5 Financial Voucher reflects VoucherTemplate
        $finService = app(\App\POS\Services\FinancialTransactionService::class);
        $finService->ensureDefaultAccounts($this->store);
        $cashAcc = \App\Models\FinancialAccount::where('store_id', $this->store->id)->first();
        $tx = $finService->recordDeposit($this->store, [
            'to_account_id' => $cashAcc->id,
            'amount' => 50000,
            'category' => 'capital_injection',
            'payer_or_payee' => 'Ko Aung',
        ], $this->manager);

        $voucherResponse = $this->actingAs($this->manager)->get(route('store.admin.transactions.voucher', [
            'store_slug' => $this->store->slug,
            'transaction' => $tx->id,
        ]));
        $voucherResponse->assertOk();
        $voucherResponse->assertSee('Custom A5 Dynamic Finance Title');
        $voucherResponse->assertSee('Custom A5 Dynamic Finance Greeting');

        // Test 80mm E-Load Slip reflects VoucherTemplate
        $opAccount = \App\Models\EloadAccount::create([
            'store_id' => $this->store->id,
            'operator' => 'mytel',
            'name' => 'Mytel Float Account',
            'phone_number' => '09690000000',
            'balance' => 100000,
            'discount_percent' => 5.0,
        ]);
        $eload = \App\Models\EloadTransaction::create([
            'store_id' => $this->store->id,
            'eload_account_id' => $opAccount->id,
            'cashier_id' => $this->staff->id,
            'operator' => 'mytel',
            'phone_number' => '09691112223',
            'type' => 'topup',
            'amount' => 5000,
            'cost' => 4750,
            'profit' => 250,
            'discount_percent' => 5.0,
            'payment_method' => 'cash',
            'ref_no' => 'EL-DYN-001',
            'status' => 'completed',
            'occurred_at' => now(),
        ]);

        $slipResponse = $this->actingAs($this->manager)->get(route('store.admin.eload.slip', [
            'store_slug' => $this->store->slug,
            'id' => $eload->id,
        ]));
        $slipResponse->assertOk();
        $slipResponse->assertSee('Custom 80mm Dynamic Title');
        $slipResponse->assertSee('Custom 80mm Dynamic Greeting');

        // Test POS Sale Receipt reflects 80mm VoucherTemplate
        $sale = \App\POS\Models\PosSale::create([
            'store_id' => $this->store->id,
            'cashier_id' => $this->staff->id,
            'receipt_number' => 'RCP-2026-001',
            'status' => 'posted',
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 0,
            'total' => 10000,
            'posted_at' => now(),
        ]);
        $receiptResponse = $this->actingAs($this->staff)->get(route('pos.receipt', [
            'store_slug' => $this->store->slug,
            'sale' => $sale->id,
            'paper_size' => '80mm',
        ]));
        $receiptResponse->assertOk();
        $receiptResponse->assertSee('Custom 80mm Dynamic Title');
        $receiptResponse->assertSee('Custom 80mm Dynamic Greeting');

        // Test Online Order Invoice reflects A4 VoucherTemplate
        $order = \App\Models\Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-2026-001',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => 'Ko Kyaw',
            'customer_phone' => '0912345678',
            'subtotal' => 20000,
            'tax_amount' => 0,
            'shipping_fee' => 0,
            'total_amount' => 20000,
        ]);
        $orderResponse = $this->actingAs($this->manager)->get(route('store.admin.orders.invoice', [
            'store_slug' => $this->store->slug,
            'order' => $order->id,
        ]));
        $orderResponse->assertOk();
        $orderResponse->assertSee('Custom A4 Dynamic Corporate Title');
        $orderResponse->assertSee('Custom A4 Dynamic Warranty Policy');
    }
}
