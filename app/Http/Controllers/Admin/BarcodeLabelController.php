<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarcodeTemplate;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\BarcodeGeneratorService;
use App\Services\StoreContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BarcodeLabelController extends Controller
{
    public function __construct(
        protected BarcodeGeneratorService $barcodeService,
    ) {
    }

    /**
     * Display the barcode label designer and product selection matrix.
     */
    public function index(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $totalProducts = Product::where('store_id', $store->id)->count();
        $inStockCount = Product::where('store_id', $store->id)->where('stock_status', 'in_stock')->count();
        $withBarcodeCount = Product::where('store_id', $store->id)->whereNotNull('barcode')->where('barcode', '!=', '')->count();

        // Recent / Top in-stock products for quick selection
        $recentProducts = Product::where('store_id', $store->id)
            ->with(['variants', 'category', 'brand'])
            ->latest('id')
            ->take(50)
            ->get();

        $categories = Category::where('store_id', $store->id)->withCount('products')->get();
        $brands = Brand::where('store_id', $store->id)->withCount('products')->get();

        // Load Built-in Presets & Store's Saved Custom Templates
        $presets = $this->getPresetsForStore($store->id);
        $customTemplates = BarcodeTemplate::where('store_id', $store->id)->orderBy('name')->get();
        $exportUrl = route('store.admin.barcode.export', ['store_slug' => $store->slug]);

        return view('admin.barcode.index', compact(
            'store',
            'recentProducts',
            'presets',
            'customTemplates',
            'totalProducts',
            'inStockCount',
            'withBarcodeCount',
            'categories',
            'brands',
            'exportUrl'
        ));
    }

    /**
     * AJAX search endpoint for products and variants.
     */
    public function search(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (! $store) {
            return response()->json([], 404);
        }

        $query = trim((string) $request->input('q', ''));
        $categoryId = $request->input('category_id');
        $brandId = $request->input('brand_id');

        $q = Product::where('store_id', $store->id);

        if (strlen($query) >= 1) {
            $term = '%' . $query . '%';
            $q->where(function ($sq) use ($term) {
                $sq->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term)
                  ->orWhere('barcode', 'like', $term);
            });
        }

        if (!empty($categoryId)) {
            $q->where('category_id', $categoryId);
        }

        if (!empty($brandId)) {
            $q->where('brand_id', $brandId);
        }

        $products = $q->with(['variants', 'category', 'brand'])
            ->take(30)
            ->get();

        $results = [];
        foreach ($products as $product) {
            if ($product->variants->isNotEmpty()) {
                foreach ($product->variants as $variant) {
                    $code = $variant->barcode ?: ($variant->sku ?: ($product->barcode ?: $product->sku));
                    $results[] = [
                        'id' => "v-{$variant->id}",
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'name' => $product->name . ' (' . $variant->name . ')',
                        'product_name' => $product->name,
                        'variant_name' => $variant->name,
                        'category_name' => $product->category?->name ?? '-',
                        'code' => $code ?: 'PRD-' . $product->id,
                        'price' => (float) ($variant->retail_price ?: $product->retail_price),
                        'stock' => $variant->stock_quantity ?? $product->stock_quantity ?? 0,
                    ];
                }
            } else {
                $code = $product->barcode ?: $product->sku;
                $results[] = [
                    'id' => "p-{$product->id}",
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => $product->name,
                    'product_name' => $product->name,
                    'variant_name' => null,
                    'category_name' => $product->category?->name ?? '-',
                    'code' => $code ?: 'PRD-' . $product->id,
                    'price' => (float) $product->retail_price,
                    'stock' => $product->stock_quantity ?? 0,
                ];
            }
        }

        return response()->json($results);
    }

    /**
     * AJAX endpoint to save or update a custom barcode template.
     */
    public function saveTemplate(StoreContext $context, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (! $store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $validated = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:120',
            'type' => 'required|in:thermal,sheet',
            'width_mm' => 'required|numeric|min:10|max:300',
            'height_mm' => 'required|numeric|min:10|max:300',
            'gap_x_mm' => 'nullable|numeric|min:0|max:50',
            'gap_y_mm' => 'nullable|numeric|min:0|max:50',
            'padding_top_mm' => 'nullable|numeric|min:0|max:20',
            'padding_bottom_mm' => 'nullable|numeric|min:0|max:20',
            'padding_left_mm' => 'nullable|numeric|min:0|max:20',
            'padding_right_mm' => 'nullable|numeric|min:0|max:20',
            'spacing_store_to_name_mm' => 'nullable|numeric|min:0|max:20',
            'spacing_name_to_code_mm' => 'nullable|numeric|min:0|max:20',
            'spacing_code_to_price_mm' => 'nullable|numeric|min:0|max:20',
            'margin_top_mm' => 'nullable|numeric|min:0|max:50',
            'margin_bottom_mm' => 'nullable|numeric|min:0|max:50',
            'margin_left_mm' => 'nullable|numeric|min:0|max:50',
            'margin_right_mm' => 'nullable|numeric|min:0|max:50',
            'cols' => 'nullable|integer|min:1|max:10',
            'rows' => 'nullable|integer|min:1|max:30',
            'bar_height' => 'nullable|integer|min:10|max:100',
            'bar_width' => 'nullable|numeric|min:0.5|max:3.0',
            'store_font' => 'nullable|string|max:15',
            'name_font' => 'nullable|string|max:15',
            'name_max_lines' => 'nullable|integer|min:1|max:3',
            'price_font' => 'nullable|string|max:15',
            'code_type' => 'nullable|in:barcode_128,qr_code',
            'show_store_name' => 'nullable|boolean',
            'show_product_name' => 'nullable|boolean',
            'show_price' => 'nullable|boolean',
            'show_code_text' => 'nullable|boolean',
        ]);

        $templateId = $validated['id'] ?? null;
        unset($validated['id']);
        $validated['store_id'] = $store->id;

        if ($templateId) {
            $template = BarcodeTemplate::where('store_id', $store->id)->where('id', $templateId)->first();
            if ($template) {
                $template->update($validated);
            } else {
                $template = BarcodeTemplate::create($validated);
            }
        } else {
            $template = BarcodeTemplate::create($validated);
        }

        $presetKey = "custom_{$template->id}";
        $formattedPreset = $this->formatTemplateToPreset($template);

        return response()->json([
            'success' => true,
            'message' => 'Template saved successfully',
            'template' => $template,
            'preset_key' => $presetKey,
            'preset' => $formattedPreset,
        ]);
    }

    /**
     * AJAX endpoint to delete a custom barcode template.
     */
    public function deleteTemplate(StoreContext $context, string $store_slug, int|string $id, Request $request): JsonResponse
    {
        $store = $context->getStore();
        if (! $store) {
            return response()->json(['error' => 'Store not found'], 404);
        }

        $template = BarcodeTemplate::where('store_id', $store->id)->where('id', $id)->first();
        if (! $template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully',
            'deleted_id' => $id,
        ]);
    }

    /**
     * Render printable sticker sheet view.
     */
    public function print(StoreContext $context, Request $request): View
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $presetKey = $request->input('preset', 'thermal_50x30');
        $presets = $this->getPresetsForStore($store->id);
        $preset = $presets[$presetKey] ?? null;

        // If custom dimensions override is passed dynamically from custom designer
        if ($request->boolean('is_custom_override') || ! $preset) {
            $type = $request->input('custom_type', $preset['type'] ?? 'thermal');
            $widthMm = (float) $request->input('custom_width_mm', $preset['width_mm'] ?? 50);
            $heightMm = (float) $request->input('custom_height_mm', $preset['height_mm'] ?? 30);
            $gapXMm = (float) $request->input('custom_gap_x_mm', $preset['gap_x_mm'] ?? 0);
            $gapYMm = (float) $request->input('custom_gap_y_mm', $preset['gap_y_mm'] ?? 0);
            $padTop = (float) $request->input('custom_padding_top_mm', 1.2);
            $padBottom = (float) $request->input('custom_padding_bottom_mm', 1.2);
            $padLeft = (float) $request->input('custom_padding_left_mm', 1.5);
            $padRight = (float) $request->input('custom_padding_right_mm', 1.5);
            $spStore = (float) $request->input('custom_spacing_store_to_name_mm', 0.5);
            $spName = (float) $request->input('custom_spacing_name_to_code_mm', 0.5);
            $spCode = (float) $request->input('custom_spacing_code_to_price_mm', 0.5);

            $preset = [
                'name' => $request->input('custom_name', 'Custom Label (' . $widthMm . 'mm × ' . $heightMm . 'mm)'),
                'type' => $type,
                'category' => 'custom',
                'width_mm' => $widthMm,
                'height_mm' => $heightMm,
                'gap_x_mm' => $gapXMm,
                'gap_y_mm' => $gapYMm,
                'padding' => "{$padTop}mm {$padRight}mm {$padBottom}mm {$padLeft}mm",
                'padding_top_mm' => $padTop,
                'padding_bottom_mm' => $padBottom,
                'padding_left_mm' => $padLeft,
                'padding_right_mm' => $padRight,
                'spacing_store_to_name_mm' => $spStore,
                'spacing_name_to_code_mm' => $spName,
                'spacing_code_to_price_mm' => $spCode,
                'margin_top_mm' => (float) $request->input('custom_margin_top_mm', 0),
                'margin_bottom_mm' => (float) $request->input('custom_margin_bottom_mm', 0),
                'margin_left_mm' => (float) $request->input('custom_margin_left_mm', 0),
                'margin_right_mm' => (float) $request->input('custom_margin_right_mm', 0),
                'cols' => (int) $request->input('custom_cols', $preset['cols'] ?? 1),
                'rows' => (int) $request->input('custom_rows', $preset['rows'] ?? 1),
                'bar_height' => (int) $request->input('custom_bar_height', $heightMm <= 22 ? 16 : 28),
                'bar_width' => (float) $request->input('custom_bar_width', 1.3),
                'store_font' => $request->input('custom_store_font', $heightMm <= 22 ? '7.5px' : '9px'),
                'name_font' => $request->input('custom_name_font', $heightMm <= 22 ? '7.5px' : '8.5px'),
                'name_max_lines' => (int) $request->input('custom_name_max_lines', $heightMm <= 22 ? 1 : 2),
                'price_font' => $request->input('custom_price_font', $heightMm <= 22 ? '9px' : '11px'),
                'description' => 'စိတ်ကြိုက် အရွယ်အစားနှင့် အကွာအဝေး စတစ်ကာ',
            ];
        }

        $isTestSingle = $request->boolean('is_test_single');
        $showStoreName = $request->boolean('show_store_name', true);
        $showProductName = $request->boolean('show_product_name', true);
        $showPrice = $request->boolean('show_price', true);
        $showCodeText = $request->boolean('show_code_text', true);
        $showCustomText = $request->boolean('show_custom_text', false);
        $customText = trim((string) $request->input('custom_text', ''));
        $skipLabels = max(0, (int) $request->input('skip_labels', 0));
        $codeType = $request->input('code_type', 'barcode_128');

        $rawItems = json_decode($request->input('items_json', '[]'), true) ?: [];
        if ($isTestSingle && !empty($rawItems)) {
            $rawItems = [$rawItems[0]];
            $rawItems[0]['quantity'] = 1;
        }

        // Build flat array of label items repeating by quantity
        $labels = [];
        $svgCache = [];

        foreach ($rawItems as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $code = trim($item['code'] ?? '000000');
            $name = trim($item['name'] ?? 'Product');
            $price = (float) ($item['price'] ?? 0);

            // Generate SVG barcode if not cached
            if (! isset($svgCache[$code])) {
                if ($codeType === 'qr_code') {
                    $svgCache[$code] = $this->barcodeService->generateQrCodeSvg($code, 64);
                } else {
                    $barHeight = $preset['bar_height'] ?? 28;
                    $barWidth = $preset['bar_width'] ?? 1.3;
                    $svgCache[$code] = $this->barcodeService->generateCode128Svg($code, $barHeight, $barWidth, $showCodeText);
                }
            }

            for ($i = 0; $i < $qty; $i++) {
                $labels[] = [
                    'store_name' => $store->name,
                    'name' => $name,
                    'code' => $code,
                    'price' => $price,
                    'svg' => $svgCache[$code],
                    'custom_text' => $customText,
                ];
            }
        }

        // Thermal Roll & Sheet Layout Metrics
        $cols = max(1, (int) ($preset['cols'] ?? 1));
        $gapXMm = (float) ($preset['gap_x_mm'] ?? 0);
        $gapYMm = (float) ($preset['gap_y_mm'] ?? 0);
        $widthMm = (float) ($preset['width_mm'] ?? 50);
        $heightMm = (float) ($preset['height_mm'] ?? 30);
        $marginLeft = (float) ($preset['margin_left_mm'] ?? 0);
        $marginRight = (float) ($preset['margin_right_mm'] ?? 0);
        $marginTop = (float) ($preset['margin_top_mm'] ?? 0);
        $marginBottom = (float) ($preset['margin_bottom_mm'] ?? 0);

        if (($preset['type'] ?? 'thermal') === 'thermal') {
            // Calculate total roll width for thermal multi-column feed
            $pageWidthMm = ($cols * $widthMm) + (($cols - 1) * $gapXMm) + $marginLeft + $marginRight;
            $pageHeightMm = $heightMm + $marginTop + $marginBottom;
            $labelRows = array_chunk($labels, $cols);
            $sheetLabels = [];
            $sheetPages = [];
            $labelsPerPage = $cols;
        } else {
            $isLetter = ($presetKey ?? '') === 'letter_30';
            $pageWidthMm = $isLetter ? 215.9 : 210;
            $pageHeightMm = $isLetter ? 279.4 : 297;
            $labelRows = [];
            // Prepend skipped blank positions for partial sheets
            $sheetLabels = [];
            for ($s = 0; $s < $skipLabels; $s++) {
                $sheetLabels[] = ['is_blank' => true];
            }
            foreach ($labels as $lbl) {
                $sheetLabels[] = array_merge($lbl, ['is_blank' => false]);
            }

            // Paginate into individual physical sheets based on cols * rows (e.g. 3 * 8 = 24 for a4_24)
            $labelsPerPage = max(1, ($preset['cols'] ?? 3) * ($preset['rows'] ?? 8));
            $sheetPages = array_chunk($sheetLabels, $labelsPerPage);
        }

        return view('admin.barcode.print', compact(
            'store',
            'preset',
            'presetKey',
            'labels',
            'labelRows',
            'sheetLabels',
            'sheetPages',
            'labelsPerPage',
            'pageWidthMm',
            'pageHeightMm',
            'showStoreName',
            'showProductName',
            'showPrice',
            'showCodeText',
            'showCustomText',
            'customText',
            'skipLabels',
            'isTestSingle',
            'codeType'
        ));
    }

    /**
     * Get presets merged with store's custom templates.
     */
    private function getPresetsForStore(int $storeId): array
    {
        $presets = $this->getDefaultPresets();

        $customs = BarcodeTemplate::where('store_id', $storeId)->orderBy('name')->get();
        foreach ($customs as $custom) {
            $presets["custom_{$custom->id}"] = $this->formatTemplateToPreset($custom);
        }

        return $presets;
    }

    /**
     * Format Eloquent BarcodeTemplate to Preset array structure.
     */
    private function formatTemplateToPreset(BarcodeTemplate $t): array
    {
        $padTop = $t->padding_top_mm ?? 1.2;
        $padRight = $t->padding_right_mm ?? 2.0;
        $padBottom = $t->padding_bottom_mm ?? 1.2;
        $padLeft = $t->padding_left_mm ?? 2.0;

        return [
            'id' => $t->id,
            'name' => $t->name,
            'type' => $t->type,
            'category' => 'custom',
            'is_custom' => true,
            'template_id' => $t->id,
            'width_mm' => (float) $t->width_mm,
            'height_mm' => (float) $t->height_mm,
            'gap_x_mm' => (float) $t->gap_x_mm,
            'gap_y_mm' => (float) $t->gap_y_mm,
            'padding' => "{$padTop}mm {$padRight}mm {$padBottom}mm {$padLeft}mm",
            'padding_top_mm' => (float) $padTop,
            'padding_bottom_mm' => (float) $padBottom,
            'padding_left_mm' => (float) $padLeft,
            'padding_right_mm' => (float) $padRight,
            'spacing_store_to_name_mm' => (float) ($t->spacing_store_to_name_mm ?? 0.5),
            'spacing_name_to_code_mm' => (float) ($t->spacing_name_to_code_mm ?? 0.5),
            'spacing_code_to_price_mm' => (float) ($t->spacing_code_to_price_mm ?? 0.5),
            'margin_top_mm' => (float) $t->margin_top_mm,
            'margin_bottom_mm' => (float) $t->margin_bottom_mm,
            'margin_left_mm' => (float) $t->margin_left_mm,
            'margin_right_mm' => (float) $t->margin_right_mm,
            'cols' => (int) $t->cols,
            'rows' => (int) $t->rows,
            'bar_height' => (int) $t->bar_height,
            'bar_width' => (float) $t->bar_width,
            'store_font' => $t->store_font ?: '9px',
            'name_font' => $t->name_font ?: '8.5px',
            'name_max_lines' => (int) ($t->name_max_lines ?: 2),
            'price_font' => $t->price_font ?: '11px',
            'code_type' => $t->code_type ?: 'barcode_128',
            'show_store_name' => (bool) $t->show_store_name,
            'show_product_name' => (bool) $t->show_product_name,
            'show_price' => (bool) $t->show_price,
            'show_code_text' => (bool) $t->show_code_text,
            'description' => 'စိတ်ကြိုက်သိမ်းဆည်းထားသော Template (' . $t->width_mm . 'mm × ' . $t->height_mm . 'mm)',
        ];
    }

    /**
     * Supported default label sheet & roll presets.
     */
    private function getDefaultPresets(): array
    {
        return [
            // ── Thermal 1-Column (Single Roll) ──
            'thermal_40x30' => [
                'name' => 'Thermal Roll (40mm × 30mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 40,
                'height_mm' => 30,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 26,
                'bar_width' => 1.25,
                'padding' => '1.2mm 1.5mm',
                'padding_top_mm' => 1.2,
                'padding_bottom_mm' => 1.2,
                'padding_left_mm' => 1.5,
                'padding_right_mm' => 1.5,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '8.5px',
                'name_font' => '8.5px',
                'name_max_lines' => 2,
                'price_font' => '10px',
                'description' => 'ဖုန်းအပိုပစ္စည်း၊ အိတ်၊ ကာဗာနှင့် ဆက်စပ်ပစ္စည်းများအတွက် စံနှုန်းမီ စတစ်ကာ',
            ],
            'thermal_50x30' => [
                'name' => 'Thermal Roll (50mm × 30mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 50,
                'height_mm' => 30,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 28,
                'bar_width' => 1.35,
                'padding' => '1.2mm 2mm',
                'padding_top_mm' => 1.2,
                'padding_bottom_mm' => 1.2,
                'padding_left_mm' => 2.0,
                'padding_right_mm' => 2.0,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '9px',
                'name_font' => '9px',
                'name_max_lines' => 2,
                'price_font' => '11px',
                'description' => 'ဖုန်းနှင့် အီလက်ထရောနစ် အရောင်းဆိုင်သုံး လူသုံးအများဆုံး စံနှုန်းမီ စတစ်ကာ',
            ],
            'thermal_30x20' => [
                'name' => 'Thermal Roll (30mm × 20mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 30,
                'height_mm' => 20,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 16,
                'bar_width' => 1.05,
                'padding' => '0.8mm 1.0mm',
                'padding_top_mm' => 0.8,
                'padding_bottom_mm' => 0.8,
                'padding_left_mm' => 1.0,
                'padding_right_mm' => 1.0,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '7px',
                'name_font' => '7px',
                'name_max_lines' => 1,
                'price_font' => '8.5px',
                'description' => 'အားသွင်းခေါင်း၊ ကြိုး၊ အပိုပစ္စည်းအသေးစားများအတွက် Mini စတစ်ကာ',
            ],
            'thermal_35x25' => [
                'name' => 'Thermal Roll (35mm × 25mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 35,
                'height_mm' => 25,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 20,
                'bar_width' => 1.15,
                'padding' => '1.0mm 1.2mm',
                'padding_top_mm' => 1.0,
                'padding_bottom_mm' => 1.0,
                'padding_left_mm' => 1.2,
                'padding_right_mm' => 1.2,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '8px',
                'name_font' => '8px',
                'name_max_lines' => 2,
                'price_font' => '9.5px',
                'description' => 'လက်လီစတိုးနှင့် မိုဘိုင်းပစ္စည်း Compact အရွယ်အစား',
            ],
            'thermal_40x20' => [
                'name' => 'Thermal Roll (40mm × 20mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 40,
                'height_mm' => 20,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 16,
                'bar_width' => 1.15,
                'padding' => '0.8mm 1.2mm',
                'padding_top_mm' => 0.8,
                'padding_bottom_mm' => 0.8,
                'padding_left_mm' => 1.2,
                'padding_right_mm' => 1.2,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '7.5px',
                'name_font' => '7.5px',
                'name_max_lines' => 1,
                'price_font' => '9px',
                'description' => 'ကြိုး၊ ဖုန်းကာဗာ၊ အလှကုန်ပစ္စည်းငယ်များအတွက်',
            ],
            'thermal_50x25' => [
                'name' => 'Thermal Roll (50mm × 25mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 50,
                'height_mm' => 25,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 22,
                'bar_width' => 1.3,
                'padding' => '1.0mm 1.5mm',
                'padding_top_mm' => 1.0,
                'padding_bottom_mm' => 1.0,
                'padding_left_mm' => 1.5,
                'padding_right_mm' => 1.5,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '8.5px',
                'name_font' => '8.5px',
                'name_max_lines' => 2,
                'price_font' => '10px',
                'description' => 'ဘားကုဒ် ရှည်လျားသော ပစ္စည်းများအတွက် ၂:၁ အချိုး အကျယ်စတစ်ကာ',
            ],
            'thermal_60x40' => [
                'name' => 'Thermal Roll (60mm × 40mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 60,
                'height_mm' => 40,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 32,
                'bar_width' => 1.45,
                'padding' => '1.5mm 2.0mm',
                'padding_top_mm' => 1.5,
                'padding_bottom_mm' => 1.5,
                'padding_left_mm' => 2.0,
                'padding_right_mm' => 2.0,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '10px',
                'name_font' => '10px',
                'name_max_lines' => 2,
                'price_font' => '13px',
                'description' => 'ပစ္စည်းဘူးခွံကြီးများ၊ ပါဆယ်ပို့ဆောင်ရေးနှင့် ကွန်ပျူတာပစ္စည်းများအတွက်',
            ],
            'thermal_80x50' => [
                'name' => 'Thermal Roll (80mm × 50mm)',
                'type' => 'thermal',
                'category' => 'thermal_1up',
                'is_custom' => false,
                'width_mm' => 80,
                'height_mm' => 50,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 38,
                'bar_width' => 1.6,
                'padding' => '2.0mm 2.5mm',
                'padding_top_mm' => 2.0,
                'padding_bottom_mm' => 2.0,
                'padding_left_mm' => 2.5,
                'padding_right_mm' => 2.5,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '12px',
                'name_font' => '11px',
                'name_max_lines' => 2,
                'price_font' => '15px',
                'description' => 'စင်တင်ဈေးနှုန်းကတ် (Shelf Tag)၊ Laptop / Monitor သေတ္တာကပ် စတစ်ကာ',
            ],

            // ── Thermal 2-Column (Twin Roll / 2-Up - တစ်တန်း ၂ ကတ်တွဲ) ──
            'thermal_2up_35x25' => [
                'name' => '2-Up Roll (35mm × 25mm × 2 Columns)',
                'type' => 'thermal',
                'category' => 'thermal_2up',
                'is_custom' => false,
                'width_mm' => 35,
                'height_mm' => 25,
                'gap_x_mm' => 2,
                'gap_y_mm' => 0,
                'bar_height' => 20,
                'bar_width' => 1.15,
                'padding' => '1.0mm 1.2mm',
                'padding_top_mm' => 1.0,
                'padding_bottom_mm' => 1.0,
                'padding_left_mm' => 1.2,
                'padding_right_mm' => 1.2,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 2,
                'rows' => 1,
                'store_font' => '8px',
                'name_font' => '7.5px',
                'name_max_lines' => 2,
                'price_font' => '9px',
                'description' => 'တစ်တန်းလျှင် ၂ ကတ်ကပ်လျက် (စုစုပေါင်းစက္ကူအကျယ် 72mm) - ပရင့်အမြန်ဆုံး',
            ],
            'thermal_2up_40x30' => [
                'name' => '2-Up Roll (40mm × 30mm × 2 Columns)',
                'type' => 'thermal',
                'category' => 'thermal_2up',
                'is_custom' => false,
                'width_mm' => 40,
                'height_mm' => 30,
                'gap_x_mm' => 2,
                'gap_y_mm' => 0,
                'bar_height' => 25,
                'bar_width' => 1.25,
                'padding' => '1.2mm 1.5mm',
                'padding_top_mm' => 1.2,
                'padding_bottom_mm' => 1.2,
                'padding_left_mm' => 1.5,
                'padding_right_mm' => 1.5,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 2,
                'rows' => 1,
                'store_font' => '8.5px',
                'name_font' => '8px',
                'name_max_lines' => 2,
                'price_font' => '10px',
                'description' => 'တစ်တန်းလျှင် ၂ ကတ်ကပ်လျက် (စုစုပေါင်းစက္ကူအကျယ် 82mm) - 80mm ပရင်တာသုံး',
            ],
            'thermal_2up_30x20' => [
                'name' => '2-Up Roll (30mm × 20mm × 2 Columns)',
                'type' => 'thermal',
                'category' => 'thermal_2up',
                'is_custom' => false,
                'width_mm' => 30,
                'height_mm' => 20,
                'gap_x_mm' => 2,
                'gap_y_mm' => 0,
                'bar_height' => 16,
                'bar_width' => 1.05,
                'padding' => '0.8mm 1.0mm',
                'padding_top_mm' => 0.8,
                'padding_bottom_mm' => 0.8,
                'padding_left_mm' => 1.0,
                'padding_right_mm' => 1.0,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 2,
                'rows' => 1,
                'store_font' => '7px',
                'name_font' => '7px',
                'name_max_lines' => 1,
                'price_font' => '8.5px',
                'description' => 'တစ်တန်းလျှင် ၂ ကတ်ကပ်လျက် အသေးစား (စုစုပေါင်းစက္ကူအကျယ် 62mm)',
            ],

            // ── Jewelry / Cable Tail Tag (ခေါက်တံဆိပ် / တွဲလောင်းစတစ်ကာ) ──
            'jewelry_tail_70x12' => [
                'name' => 'Jewelry / Cable Tail Tag (70mm × 12mm)',
                'type' => 'thermal',
                'category' => 'specialty',
                'is_custom' => false,
                'width_mm' => 70,
                'height_mm' => 12,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 12,
                'bar_width' => 1.0,
                'padding' => '0.5mm 1.5mm',
                'padding_top_mm' => 0.5,
                'padding_bottom_mm' => 0.5,
                'padding_left_mm' => 1.5,
                'padding_right_mm' => 1.5,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'cols' => 1,
                'rows' => 1,
                'store_font' => '6.5px',
                'name_font' => '6.5px',
                'name_max_lines' => 1,
                'price_font' => '7.5px',
                'description' => 'လက်ဝတ်ရတနာ၊ နာရီ၊ ကြိုးများတွင် တွဲလောင်းချည်နှောင်ရသော Foldable Tag',
            ],

            // ── Standard Pre-cut Sheets (A4 & US Letter) ──
            'a4_24' => [
                'name' => 'A4 Sheet (24 Labels - 3×8, 70×37mm)',
                'type' => 'sheet',
                'category' => 'sheet',
                'is_custom' => false,
                'cols' => 3,
                'rows' => 8,
                'width_mm' => 70,
                'height_mm' => 37,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 30,
                'bar_width' => 1.4,
                'padding' => '2mm 2.5mm',
                'padding_top_mm' => 2.0,
                'padding_bottom_mm' => 2.0,
                'padding_left_mm' => 2.5,
                'padding_right_mm' => 2.5,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'store_font' => '10px',
                'name_font' => '9px',
                'name_max_lines' => 2,
                'price_font' => '12px',
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၂၄ ကတ် - Avery 7160)',
            ],
            'a4_30' => [
                'name' => 'A4 Sheet (30 Labels - 3×10, 70×29.7mm)',
                'type' => 'sheet',
                'category' => 'sheet',
                'is_custom' => false,
                'cols' => 3,
                'rows' => 10,
                'width_mm' => 70,
                'height_mm' => 29.7,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 24,
                'bar_width' => 1.3,
                'padding' => '1.5mm 2mm',
                'padding_top_mm' => 1.5,
                'padding_bottom_mm' => 1.5,
                'padding_left_mm' => 2.0,
                'padding_right_mm' => 2.0,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'store_font' => '9px',
                'name_font' => '8.5px',
                'name_max_lines' => 2,
                'price_font' => '10.5px',
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၃၀ ကတ် - Avery L7161)',
            ],
            'a4_40' => [
                'name' => 'A4 Sheet (40 Labels - 4×10, 52.5×29.7mm)',
                'type' => 'sheet',
                'category' => 'sheet',
                'is_custom' => false,
                'cols' => 4,
                'rows' => 10,
                'width_mm' => 52.5,
                'height_mm' => 29.7,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 22,
                'bar_width' => 1.15,
                'padding' => '1.2mm 1.5mm',
                'padding_top_mm' => 1.2,
                'padding_bottom_mm' => 1.2,
                'padding_left_mm' => 1.5,
                'padding_right_mm' => 1.5,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'store_font' => '8px',
                'name_font' => '7.5px',
                'name_max_lines' => 1,
                'price_font' => '9.5px',
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၄၀ ကတ် - Compact Sheet)',
            ],
            'a4_65' => [
                'name' => 'A4 Sheet (65 Labels - 5×13, 38.1×21.2mm)',
                'type' => 'sheet',
                'category' => 'sheet',
                'is_custom' => false,
                'cols' => 5,
                'rows' => 13,
                'width_mm' => 38.1,
                'height_mm' => 21.2,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 16,
                'bar_width' => 1.05,
                'padding' => '0.8mm 1.2mm',
                'padding_top_mm' => 0.8,
                'padding_bottom_mm' => 0.8,
                'padding_left_mm' => 1.2,
                'padding_right_mm' => 1.2,
                'margin_top_mm' => 0,
                'margin_bottom_mm' => 0,
                'margin_left_mm' => 0,
                'margin_right_mm' => 0,
                'store_font' => '7px',
                'name_font' => '7px',
                'name_max_lines' => 1,
                'price_font' => '8.5px',
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၆၅ ကတ် - Avery L7651 Mini)',
            ],
            'letter_30' => [
                'name' => 'US Letter (30 Labels - 3×10, 66.7×25.4mm)',
                'type' => 'sheet',
                'category' => 'sheet',
                'is_custom' => false,
                'cols' => 3,
                'rows' => 10,
                'width_mm' => 66.7,
                'height_mm' => 25.4,
                'gap_x_mm' => 3.17,
                'gap_y_mm' => 0,
                'bar_height' => 20,
                'bar_width' => 1.2,
                'padding' => '1.0mm 1.5mm',
                'padding_top_mm' => 1.0,
                'padding_bottom_mm' => 1.0,
                'padding_left_mm' => 1.5,
                'padding_right_mm' => 1.5,
                'margin_top_mm' => 12.7,
                'margin_bottom_mm' => 12.7,
                'margin_left_mm' => 4.8,
                'margin_right_mm' => 4.8,
                'store_font' => '8.5px',
                'name_font' => '8px',
                'name_max_lines' => 1,
                'price_font' => '10px',
                'description' => 'US Letter စတစ်ကာ စာရွက် (Avery 5160 / 8160 စံနှုန်းမီ)',
            ],
        ];
    }

    /**
     * Export products and barcodes to formatted Excel (.xlsx) or CSV.
     */
    public function export(StoreContext $context, Request $request): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        if (! $store) {
            abort(404);
        }

        $format = strtolower((string) $request->query('format', 'xlsx'));

        $products = Product::where('store_id', $store->id)
            ->with(['category', 'brand', 'variants'])
            ->orderBy('name')
            ->get();

        if ($format === 'csv') {
            $filename = 'Barcodes_' . $store->slug . '_' . now()->format('Ymd_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->streamDownload(function () use ($products) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['Product Name', 'Barcode / Code', 'SKU', 'Category', 'Brand', 'Retail Price', 'Stock Quantity', 'Stock Status']);

                foreach ($products as $p) {
                    $code = $p->barcode ?: ($p->sku ?: 'PRD-' . $p->id);
                    fputcsv($stream, [
                        $this->csvCell($p->name),
                        $this->csvCell($code),
                        $this->csvCell($p->sku ?? ''),
                        $this->csvCell($p->category?->name ?? '-'),
                        $this->csvCell($p->brand?->name ?? '-'),
                        (float) $p->retail_price,
                        (int) $p->stock_quantity,
                        $p->stock_status ?? 'in_stock',
                    ]);
                }

                fclose($stream);
            }, $filename, $headers);
        }

        $filename = 'Barcodes_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_barcode_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Barcodes');

        // Header Title
        $sheet->setCellValue('A1', $store->name . ' - Barcode & QR Label Product Inventory');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Products: ' . $products->count());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('4C1D95');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        $row = 4;
        $headers = [
            'A' => 'Product Name',
            'B' => 'Barcode / Code',
            'C' => 'SKU',
            'D' => 'Category',
            'E' => 'Brand',
            'F' => 'Retail Price',
            'G' => 'Stock Quantity',
            'H' => 'Stock Status',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '6D28D9'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);

        $row++;
        foreach ($products as $p) {
            $code = $p->barcode ?: ($p->sku ?: 'PRD-' . $p->id);
            $sheet->setCellValue("A{$row}", $p->name);
            $sheet->setCellValueExplicit("B{$row}", $code, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string) ($p->sku ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("D{$row}", $p->category?->name ?? '-');
            $sheet->setCellValue("E{$row}", $p->brand?->name ?? '-');
            $sheet->setCellValue("F{$row}", (float) $p->retail_price);
            $sheet->setCellValue("G{$row}", (int) $p->stock_quantity);
            $sheet->setCellValue("H{$row}", ucfirst(str_replace('_', ' ', (string) ($p->stock_status ?? 'in_stock'))));

            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('#,##0');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()->applyFromArray([
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'],
                ]);
            }
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Neutralize spreadsheet formula injection in exported CSV cells.
     */
    private function csvCell(mixed $value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
