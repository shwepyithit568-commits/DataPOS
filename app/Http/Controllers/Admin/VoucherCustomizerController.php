<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoucherTemplate;
use App\POS\Services\VoucherTemplateService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherCustomizerController extends Controller
{
    public function __construct(
        protected VoucherTemplateService $templateService
    ) {
    }

    /**
     * Display the Voucher Customizer Studio & Template Gallery.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $templates = $this->templateService->getTemplates($store);

        $selectedId = $request->query('template_id');
        $selectedTemplate = $selectedId
            ? $templates->firstWhere('id', (int) $selectedId)
            : $templates->firstWhere('is_default', true) ?? $templates->first();

        return view('admin.vouchers.index', compact('store', 'templates', 'selectedTemplate'));
    }

    /**
     * Store a new custom voucher template.
     */
    public function store(StoreContext $context, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'paper_size' => 'required|in:80mm,58mm,a4,a5',
            'style_preset' => 'required|in:clean_minimal,modern_tech,classic_border',
            'header_title' => 'nullable|string|max:150',
            'header_subtitle' => 'nullable|string|max:200',
            'show_logo' => 'nullable|boolean',
            'logo_file' => 'nullable|image|max:2048',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:120',
            'show_qr' => 'nullable|boolean',
            'qr_type' => 'required|in:kpay,wave,bank,custom',
            'qr_file' => 'nullable|image|max:2048',
            'qr_label' => 'nullable|string|max:150',
            'show_customer_info' => 'nullable|boolean',
            'show_cashier_name' => 'nullable|boolean',
            'show_tax_breakdown' => 'nullable|boolean',
            'show_discount_line' => 'nullable|boolean',
            'show_barcode' => 'nullable|boolean',
            'footer_greeting' => 'nullable|string|max:500',
            'footer_policy' => 'nullable|string|max:1000',
            'font_size' => 'required|in:small,medium,large',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $template = $this->templateService->saveTemplate($store, $validated, null, $request->user());

        return redirect()->route('store.admin.vouchers.index', [
            'store_slug' => $store->slug,
            'template_id' => $template->id,
        ])->with('success', __('messages.vouchers_created_success'));
    }

    /**
     * Update an existing voucher template.
     */
    public function update(StoreContext $context, string $store_slug, int|string $voucher, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $template = VoucherTemplate::where('store_id', $store->id)->findOrFail($voucher);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'paper_size' => 'required|in:80mm,58mm,a4,a5',
            'style_preset' => 'required|in:clean_minimal,modern_tech,classic_border',
            'header_title' => 'nullable|string|max:150',
            'header_subtitle' => 'nullable|string|max:200',
            'show_logo' => 'nullable|boolean',
            'logo_file' => 'nullable|image|max:2048',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:120',
            'show_qr' => 'nullable|boolean',
            'qr_type' => 'required|in:kpay,wave,bank,custom',
            'qr_file' => 'nullable|image|max:2048',
            'qr_label' => 'nullable|string|max:150',
            'show_customer_info' => 'nullable|boolean',
            'show_cashier_name' => 'nullable|boolean',
            'show_tax_breakdown' => 'nullable|boolean',
            'show_discount_line' => 'nullable|boolean',
            'show_barcode' => 'nullable|boolean',
            'footer_greeting' => 'nullable|string|max:500',
            'footer_policy' => 'nullable|string|max:1000',
            'font_size' => 'required|in:small,medium,large',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $template = $this->templateService->saveTemplate($store, $validated, $template, $request->user());

        return redirect()->route('store.admin.vouchers.index', [
            'store_slug' => $store->slug,
            'template_id' => $template->id,
        ])->with('success', __('messages.vouchers_updated_success'));
    }

    /**
     * Delete a custom voucher template.
     */
    public function destroy(StoreContext $context, string $store_slug, int|string $voucher, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $template = VoucherTemplate::where('store_id', $store->id)->findOrFail($voucher);
        $this->templateService->deleteTemplate($store, $template, $request->user());

        return redirect()->route('store.admin.vouchers.index', ['store_slug' => $store->slug])
            ->with('success', __('messages.vouchers_deleted_success'));
    }

    /**
     * Set a template as default for its paper size.
     */
    public function setDefault(StoreContext $context, string $store_slug, int|string $voucher, Request $request): RedirectResponse
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $template = VoucherTemplate::where('store_id', $store->id)->findOrFail($voucher);
        $this->templateService->setDefault($store, $template, $request->user());

        return redirect()->route('store.admin.vouchers.index', [
            'store_slug' => $store->slug,
            'template_id' => $template->id,
        ])->with('success', __('messages.vouchers_set_default_success'));
    }

    /**
     * Standalone printable sample preview.
     */
    public function preview(StoreContext $context, string $store_slug, int|string $voucher): View
    {
        $store = $context->getStore();
        if (!$store) {
            abort(404);
        }

        $template = VoucherTemplate::where('store_id', $store->id)->findOrFail($voucher);

        return view('admin.vouchers.preview', compact('store', 'template'));
    }
}
