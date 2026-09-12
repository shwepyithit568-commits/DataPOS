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

    public function test_voucher_customizer_index_renders_document_defaults_tab(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.vouchers.index', [
                'store_slug' => $this->store->slug,
                'tab' => 'defaults',
            ]));

        $response->assertOk();
        $response->assertSee(__('messages.vouchers_tab_defaults'));
        $response->assertSee(__('messages.vouchers_doc_pos_sale'));
        $response->assertSee(__('messages.vouchers_doc_closing'));
        $response->assertSee(__('messages.vouchers_doc_repair'));
        $response->assertSee(__('messages.vouchers_doc_invoice'));
        $response->assertSee(__('messages.vouchers_doc_transaction'));
        $response->assertSee(__('messages.vouchers_doc_eload'));
        $response->assertSee(__('messages.vouchers_doc_warranty'));
        $response->assertSee(__('messages.vouchers_doc_wholesale'));
    }

    public function test_update_document_voucher_defaults_persists_settings(): void
    {
        $defaultsData = [
            'defaults' => [
                'pos_sale' => '58mm',
                'closing' => '58mm',
                'repair' => 'a4',
                'invoice' => 'a5',
                'transaction' => '80mm',
                'eload' => '58mm',
                'warranty' => 'a5',
                'wholesale' => 'a4',
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.vouchers.document_defaults', ['store_slug' => $this->store->slug]), $defaultsData);

        $response->assertRedirect(route('store.admin.vouchers.index', [
            'store_slug' => $this->store->slug,
            'tab' => 'defaults',
        ]));
        $response->assertSessionHas('success');

        $templateService = app(VoucherTemplateService::class);
        $saved = $templateService->getDocumentDefaults($this->store);

        $this->assertSame('58mm', $saved['pos_sale']);
        $this->assertSame('58mm', $saved['closing']);
        $this->assertSame('a4', $saved['repair']);
        $this->assertSame('a5', $saved['invoice']);
        $this->assertSame('80mm', $saved['transaction']);
        $this->assertSame('58mm', $saved['eload']);
        $this->assertSame('a5', $saved['warranty']);
        $this->assertSame('a4', $saved['wholesale']);
    }

    public function test_unauthorized_staff_cannot_update_document_voucher_defaults(): void
    {
        $response = $this->actingAs($this->staff)
            ->post(route('store.admin.vouchers.document_defaults', ['store_slug' => $this->store->slug]), [
                'defaults' => [
                    'pos_sale' => '58mm',
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_document_routes_respect_configured_voucher_defaults(): void
    {
        $templateService = app(VoucherTemplateService::class);
        $templateService->saveDocumentDefaults($this->store, [
            'pos_sale' => '58mm',
            'invoice' => 'a5',
            'repair' => 'a4',
        ], $this->manager);

        // 1. POS Sale receipt automatically defaults to 58mm
        $sale = \App\POS\Models\PosSale::create([
            'store_id' => $this->store->id,
            'cashier_id' => $this->staff->id,
            'receipt_number' => 'RCP-TEST-58',
            'status' => 'posted',
            'subtotal' => 5000,
            'discount' => 0,
            'tax' => 0,
            'total' => 5000,
            'posted_at' => now(),
        ]);

        $receiptResponse = $this->actingAs($this->staff)->get(route('pos.receipt', [
            'store_slug' => $this->store->slug,
            'sale' => $sale->id,
        ]));
        $receiptResponse->assertOk();
        $this->assertSame('58mm', $receiptResponse->viewData('paperSize'));

        // Explicit override still works when supplied
        $overrideResponse = $this->actingAs($this->staff)->get(route('pos.receipt', [
            'store_slug' => $this->store->slug,
            'sale' => $sale->id,
            'paper_size' => 'a4',
        ]));
        $overrideResponse->assertOk();
        $this->assertSame('a4', $overrideResponse->viewData('paperSize'));

        // 2. Order Invoice automatically defaults to A5
        $order = \App\Models\Order::create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-TEST-A5',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => 'Ko Aung',
            'customer_phone' => '0911223344',
            'subtotal' => 15000,
            'tax_amount' => 0,
            'shipping_fee' => 0,
            'total_amount' => 15000,
        ]);

        $orderResponse = $this->actingAs($this->manager)->get(route('store.admin.orders.invoice', [
            'store_slug' => $this->store->slug,
            'order' => $order->id,
        ]));
        $orderResponse->assertOk();
        $this->assertSame('a5', $orderResponse->viewData('paperSize'));

        // 3. Repair Ticket automatically defaults to A4
        $repair = \App\POS\Models\ServiceJob::create([
            'store_id' => $this->store->id,
            'created_by' => $this->manager->id,
            'job_number' => 'JOB-TEST-A4',
            'device_type' => 'laptop',
            'status' => 'received',
            'contact_name' => 'Ko Phyo',
            'contact_phone' => '0999887766',
            'device_model' => 'Dell Latitude 5420',
            'device_password' => '1234',
            'reported_problem' => 'Display issue',
            'estimated_charge' => 25000,
        ]);

        $repairResponse = $this->actingAs($this->manager)->get(route('store.admin.repairs.print', [
            'store_slug' => $this->store->slug,
            'repair' => $repair->id,
        ]));
        $repairResponse->assertOk();
        $this->assertSame('a4', $repairResponse->viewData('paperSize'));
    }

    public function test_template_with_document_type_persists_and_reflects_on_respective_voucher_print(): void
    {
        app(\App\POS\Services\VoucherTemplateService::class)->ensureDefaultTemplates($this->store);

        // 1. Update POS Invoice template subtitle
        $posTemplate = VoucherTemplate::where('store_id', $this->store->id)
            ->where('document_type', 'pos_sale')
            ->first();

        $this->assertNotNull($posTemplate);

        $updateResponse = $this->actingAs($this->manager)
            ->put(route('store.admin.vouchers.update', [
                'store_slug' => $this->store->slug,
                'voucher' => $posTemplate->id,
            ]), [
                'name' => 'POS Invoice (Super Retail)',
                'document_type' => 'pos_sale',
                'paper_size' => '80mm',
                'style_preset' => 'modern_tech',
                'header_title' => 'Super Retail Mart',
                'header_subtitle' => 'Specialized POS Retail Slip',
                'qr_type' => 'kpay',
                'font_size' => 'medium',
                'is_default' => 1,
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('voucher_templates', [
            'id' => $posTemplate->id,
            'header_subtitle' => 'Specialized POS Retail Slip',
            'document_type' => 'pos_sale',
        ]);

        // 2. Make sure POS Sale receipt loads this exact template even when printed with ?paper_size=a5
        $sale = \App\POS\Models\PosSale::create([
            'store_id' => $this->store->id,
            'user_id' => $this->manager->id,
            'receipt_number' => 'RCP-DOC-TEST-01',
            'subtotal' => 50000,
            'tax' => 0,
            'discount' => 0,
            'total' => 50000,
            'payment_method' => 'cash',
            'status' => 'posted',
        ]);

        $receiptResponse = $this->actingAs($this->manager)->get(route('pos.receipt', [
            'store_slug' => $this->store->slug,
            'sale' => $sale->id,
            'paper_size' => 'a5',
        ]));

        $receiptResponse->assertOk();
        $receiptResponse->assertSee('Specialized POS Retail Slip');
        $receiptResponse->assertDontSee('Service Job & Parts Delivery Voucher');

        // 3. Update Repair template subtitle
        $repairTemplate = VoucherTemplate::where('store_id', $this->store->id)
            ->where('document_type', 'repair')
            ->first();

        $this->assertNotNull($repairTemplate);

        $this->actingAs($this->manager)
            ->put(route('store.admin.vouchers.update', [
                'store_slug' => $this->store->slug,
                'voucher' => $repairTemplate->id,
            ]), [
                'name' => 'Service Invoice (Fast Fix)',
                'document_type' => 'repair',
                'paper_size' => 'a5',
                'style_preset' => 'classic_border',
                'header_title' => 'Fast Fix Tech Repair',
                'header_subtitle' => 'Certified Mobile & Laptop Service Station',
                'qr_type' => 'kpay',
                'font_size' => 'medium',
                'is_default' => 1,
            ]);

        $repair = \App\POS\Models\ServiceJob::create([
            'store_id' => $this->store->id,
            'created_by' => $this->manager->id,
            'job_number' => 'JOB-DOC-TEST-02',
            'device_type' => 'phone',
            'status' => 'received',
            'contact_name' => 'Ko Aung',
            'contact_phone' => '0912345678',
            'device_model' => 'iPhone 13 Pro',
            'reported_problem' => 'Battery replacement',
            'estimated_charge' => 45000,
        ]);

        $repairPrint = $this->actingAs($this->manager)->get(route('store.admin.repairs.print', [
            'store_slug' => $this->store->slug,
            'repair' => $repair->id,
        ]));

        $repairPrint->assertOk();
        $repairPrint->assertSee('Certified Mobile & Laptop Service Station');
        $repairPrint->assertDontSee('Specialized POS Retail Slip');
    }

    public function test_gallery_tabs_render_document_type_names_and_icons(): void
    {
        $response = $this->actingAs($this->manager)->get(route('store.admin.vouchers.index', [
            'store_slug' => $this->store->slug,
        ]));

        $response->assertOk();
        $response->assertSee('POS Invoice');
        $response->assertSee('Service Invoice');
        $response->assertSee('Commercial Tax Invoice');
        $response->assertSee('Mini Slip');
        $response->assertSee('🛒');
        $response->assertSee('🔧');
    }

    public function test_commercial_tax_invoice_renders_with_multi_paper_sizes_and_business_details(): void
    {
        // 1. Create order
        $order = \App\Models\Order::create([
            'store_id' => $this->store->id,
            'user_id' => $this->manager->id,
            'order_number' => 'ORD-B2B-TAX-99',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => 'Apex Global Tech Ltd.',
            'customer_phone' => '0999112233',
            'customer_address' => 'No. 45, Pyay Road, Kamayut, Yangon',
            'subtotal' => 150000,
            'tax_amount' => 7500,
            'shipping_fee' => 3000,
            'discount_amount' => 5000,
            'total_amount' => 155500,
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Hikvision 8-Channel NVR Security Hub',
            'quantity' => 1,
            'unit_price' => 150000,
            'subtotal' => 150000,
        ]);

        // 2. Render default A4
        $a4Response = $this->actingAs($this->manager)->get(route('store.admin.orders.invoice', [
            'store_slug' => $this->store->slug,
            'order' => $order->id,
        ]));

        $a4Response->assertOk();
        $this->assertSame('a4', $a4Response->viewData('paperSize'));
        $a4Response->assertSee('ORD-B2B-TAX-99');
        $a4Response->assertSee('Apex Global Tech Ltd.');
        $a4Response->assertSee('Hikvision 8-Channel NVR Security Hub');
        $a4Response->assertSee(__('messages.invoice_prepared_by'));
        $a4Response->assertSee(__('messages.invoice_company_stamp'));
        $a4Response->assertSee('155,500');

        // 3. Render A5
        $a5Response = $this->actingAs($this->manager)->get(route('store.admin.orders.invoice', [
            'store_slug' => $this->store->slug,
            'order' => $order->id,
            'paper_size' => 'a5',
        ]));
        $a5Response->assertOk();
        $this->assertSame('a5', $a5Response->viewData('paperSize'));
        $a5Response->assertSee('size-a5');

        // 4. Render 80mm thermal slip
        $thermalResponse = $this->actingAs($this->manager)->get(route('store.admin.orders.invoice', [
            'store_slug' => $this->store->slug,
            'order' => $order->id,
            'paper_size' => '80mm',
        ]));
        $thermalResponse->assertOk();
        $this->assertSame('80mm', $thermalResponse->viewData('paperSize'));
        $thermalResponse->assertSee('size-80mm');
    }
}
