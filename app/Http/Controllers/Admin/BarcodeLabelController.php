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

        return view('admin.barcode.index', compact(
            'store',
            'recentProducts',
            'presets',
            'customTemplates',
            'totalProducts',
            'inStockCount',
            'withBarcodeCount',
            'categories',
            'brands'
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

        $showStoreName = $request->boolean('show_store_name', true);
        $showProductName = $request->boolean('show_product_name', true);
        $showPrice = $request->boolean('show_price', true);
        $showCodeText = $request->boolean('show_code_text', true);
        $codeType = $request->input('code_type', 'barcode_128');

        $rawItems = json_decode($request->input('items_json', '[]'), true) ?: [];

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
                ];
            }
        }

        return view('admin.barcode.print', compact(
            'store',
            'preset',
            'presetKey',
            'labels',
            'showStoreName',
            'showProductName',
            'showPrice',
            'showCodeText',
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
            'thermal_50x30' => [
                'name' => 'Thermal Roll (50mm × 30mm)',
                'type' => 'thermal',
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
                'description' => 'စံနှုန်းမီ ဖုန်းနှင့် အပိုပစ္စည်း အရောင်းဆိုင်သုံး စတစ်ကာ',
            ],
            'thermal_40x30' => [
                'name' => 'Thermal Roll (40mm × 30mm)',
                'type' => 'thermal',
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
                'description' => 'အပိုပစ္စည်း အသေးစားများအတွက် စတစ်ကာ',
            ],
            'thermal_40x20' => [
                'name' => 'Thermal Roll (40mm × 20mm)',
                'type' => 'thermal',
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
            'a4_24' => [
                'name' => 'A4 Sheet (24 Labels - 3×8)',
                'type' => 'sheet',
                'is_custom' => false,
                'cols' => 3,
                'rows' => 8,
                'width_mm' => 70,
                'height_mm' => 37,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 32,
                'bar_width' => 1.45,
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
                'name_font' => '9.5px',
                'name_max_lines' => 2,
                'price_font' => '12px',
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၂၄ ကတ်)',
            ],
            'a4_30' => [
                'name' => 'A4 Sheet (30 Labels - 3×10)',
                'type' => 'sheet',
                'is_custom' => false,
                'cols' => 3,
                'rows' => 10,
                'width_mm' => 70,
                'height_mm' => 29.7,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 26,
                'bar_width' => 1.35,
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
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၃၀ ကတ်)',
            ],
            'a4_40' => [
                'name' => 'A4 Sheet (40 Labels - 4×10)',
                'type' => 'sheet',
                'is_custom' => false,
                'cols' => 4,
                'rows' => 10,
                'width_mm' => 52.5,
                'height_mm' => 29.7,
                'gap_x_mm' => 0,
                'gap_y_mm' => 0,
                'bar_height' => 24,
                'bar_width' => 1.2,
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
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၄၀ ကတ်)',
            ],
        ];
    }
}
