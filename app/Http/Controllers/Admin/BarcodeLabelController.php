<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        // Recent / Top in-stock products for quick selection
        $recentProducts = Product::where('store_id', $store->id)
            ->with(['variants', 'category', 'brand'])
            ->latest('id')
            ->take(30)
            ->get();

        $presets = $this->getPresets();

        return view('admin.barcode.index', compact('store', 'recentProducts', 'presets'));
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

        $query = trim($request->input('q', ''));
        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $term = '%' . $query . '%';

        $products = Product::where('store_id', $store->id)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term)
                  ->orWhere('barcode', 'like', $term);
            })
            ->with(['variants'])
            ->take(20)
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
                    'code' => $code ?: 'PRD-' . $product->id,
                    'price' => (float) $product->retail_price,
                    'stock' => $product->stock_quantity ?? 0,
                ];
            }
        }

        return response()->json($results);
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
        $presets = $this->getPresets();
        $preset = $presets[$presetKey] ?? $presets['thermal_50x30'];

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
                    $barHeight = $preset['bar_height'] ?? 40;
                    $svgCache[$code] = $this->barcodeService->generateCode128Svg($code, $barHeight, 1.6, $showCodeText);
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
     * Supported label sheet & roll presets.
     */
    private function getPresets(): array
    {
        return [
            'thermal_50x30' => [
                'name' => 'Thermal Roll (50mm × 30mm)',
                'type' => 'thermal',
                'width_mm' => 50,
                'height_mm' => 30,
                'bar_height' => 38,
                'font_size_px' => 11,
                'description' => 'စံနှုန်းမီ ဖုန်းနှင့် အပိုပစ္စည်း အရောင်းဆိုင်သုံး စတစ်ကာ',
            ],
            'thermal_40x30' => [
                'name' => 'Thermal Roll (40mm × 30mm)',
                'type' => 'thermal',
                'width_mm' => 40,
                'height_mm' => 30,
                'bar_height' => 32,
                'font_size_px' => 10,
                'description' => 'အပိုပစ္စည်း အသေးစားများအတွက် စတစ်ကာ',
            ],
            'thermal_40x20' => [
                'name' => 'Thermal Roll (40mm × 20mm)',
                'type' => 'thermal',
                'width_mm' => 40,
                'height_mm' => 20,
                'bar_height' => 24,
                'font_size_px' => 9,
                'description' => 'ကြိုး၊ ဖုန်းကာဗာ၊ အလှကုန်ပစ္စည်းငယ်များအတွက်',
            ],
            'a4_24' => [
                'name' => 'A4 Sheet (24 Labels - 3×8)',
                'type' => 'sheet',
                'cols' => 3,
                'rows' => 8,
                'width_mm' => 70,
                'height_mm' => 37,
                'bar_height' => 42,
                'font_size_px' => 11,
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၂၄ ကတ်)',
            ],
            'a4_30' => [
                'name' => 'A4 Sheet (30 Labels - 3×10)',
                'type' => 'sheet',
                'cols' => 3,
                'rows' => 10,
                'width_mm' => 70,
                'height_mm' => 29.7,
                'bar_height' => 34,
                'font_size_px' => 10,
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၃၀ ကတ်)',
            ],
            'a4_40' => [
                'name' => 'A4 Sheet (40 Labels - 4×10)',
                'type' => 'sheet',
                'cols' => 4,
                'rows' => 10,
                'width_mm' => 52.5,
                'height_mm' => 29.7,
                'bar_height' => 30,
                'font_size_px' => 9,
                'description' => 'A4 စတစ်ကာ စာရွက် (တစ်ရွက် ၄၀ ကတ်)',
            ],
        ];
    }
}
