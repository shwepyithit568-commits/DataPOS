<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\AdminListReturn;
use App\Support\ImageOptimizer;
use App\Models\Category;
use App\Models\ImportHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\VariantPreset;
use App\POS\Enums\InventoryMovementType;
use App\POS\Services\InventoryService;
use App\Services\ProductImportService;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    private const IMAGE_MAX_KB = 10240;
    private const MAX_GALLERY_IMAGES = 4;

    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        $summary = $this->summaryStats($store->id);

        $query = Product::where('store_id', $store->id)->with(['category', 'brand']);

        // Enhanced Search: name, SKU, brand name, category name, variant name/SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('barcode', 'like', '%' . $search . '%')
                  ->orWhere('compatible_models', 'like', '%' . $search . '%')
                  ->orWhere('shelf_location', 'like', '%' . $search . '%')
                  ->orWhereHas('brand', function ($bq) use ($search) {
                      $bq->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('variants', function ($vq) use ($search) {
                      $vq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('sku', 'like', '%' . $search . '%');
                  });
            });
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // Filter by product archetype (standard, serialized, service, weight_based)
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        // Filter by online visibility (is_ecommerce = "Sell Online" toggle)
        if ($request->filled('is_ecommerce')) {
            $query->where('is_ecommerce', $request->is_ecommerce === 'online');
        }

        // Load category hierarchy (Main → Sub) for the filter dropdown + parent expansion
        $allCategories = Category::where('store_id', $store->id)->get();
        $categoryTree = $allCategories
            ->whereNull('parent_id')
            ->map(fn ($main) => (object) [
                'category' => $main,
                'children' => $allCategories->where('parent_id', $main->id)->values(),
            ])
            ->values();

        // Filter by Category (selecting a Main category also shows its Sub-categories)
        if ($request->filled('category_id')) {
            $catId = (int) $request->category_id;
            $childIds = collect($categoryTree)->firstWhere('category.id', $catId)?->children->pluck('id') ?? collect();
            $query->whereIn('category_id', $childIds->push($catId)->unique()->values()->all());
        }

        // Filter by Brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Sorting options
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('retail_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('retail_price', 'desc');
                break;
            case 'stock':
                $query->orderBy('stock_status', 'asc');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        // Per-page: 25 / 50 / 100 / 200 (dropdown) — legacy ?per_page=all still honored
        $perPageValue = request('per_page', 50);
        $perPage = $perPageValue === 'all' ? 100000 : (int) $perPageValue;
        if (! in_array($perPage, [25, 50, 100, 200, 100000], true)) {
            $perPage = 50;
        }
        $products = $query->paginate($perPage)->withQueryString();

        // Flat list (kept for active-filter pill labels) + grouped Main→Sub structure (for optgroup dropdown)
        $categories = [];
        $categoryGroups = [];
        foreach ($categoryTree as $row) {
            $categories[$row->category->id] = $row->category->name;
            $groupOptions = [$row->category->id => 'All in ' . $row->category->name];
            foreach ($row->children as $child) {
                $categories[$child->id] = $child->name;
                $groupOptions[$child->id] = $child->name;
            }
            $categoryGroups[$row->category->id] = ['label' => $row->category->name, 'options' => $groupOptions];
        }
        $brands = Brand::where('store_id', $store->id)->pluck('name', 'id')->toArray();

        // Remember this filtered list URL so an edit/create round-trip can
        // return the user to the exact same search/filter state.
        AdminListReturn::capture($request, 'admin_products_return');

        return view('admin.products.index', compact('store', 'products', 'categories', 'categoryGroups', 'brands', 'summary'));
    }

    /**
     * Lightweight aggregate counts used by the product index hero-stat cards.
     * Runs 5 count-queries (one-shot, not N+1) across the full store catalog.
     */
    private function summaryStats(int $storeId): array
    {
        $base = Product::where('store_id', $storeId);
        return [
            'total'        => (clone $base)->count(),
            'in_stock'     => (clone $base)->where('stock_status', 'in_stock')->count(),
            'out_of_stock' => (clone $base)->where('stock_status', 'out_of_stock')->count(),
            'featured'     => (clone $base)->where('is_featured', true)->count(),
            'online'       => (clone $base)->where('is_ecommerce', true)->count(),
        ];
    }

    /**
     * Stream the store's full product list as an Excel-friendly CSV or XLSX file.
     * One export, one button: the 18 round-trip columns the product importer
     * accepts, plus the customer-facing Specifications columns the old
     * separate "Specs CSV" export carried — Burmese stock label,
     * human-readable variant names/SKUs, dynamic variant-attribute columns,
     * and the sanitized description — all merged into a single file.
     */
    public function export(StoreContext $context): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $store = $context->getStore();
        $request = request();

        $products = $this->buildExportProducts($store, $request);

        // Fixed column labels — the importer round-trip names must not change.
        $fixedColumns = [
            'SKU', 'Name', 'Category', 'Parent Category', 'Brand',
            'Retail Price (Ks)', 'Wholesale Price (Ks)', 'Discount Price (Ks)',
            'Sale Starts At', 'Sale Ends At', 'Stock Status', 'Stock Status (Burmese)',
            'Warranty', 'Return Policy', 'Meta Description', 'Featured',
            'Description', 'Sanitized Description', 'Images', 'Variants',
            'Variant Name(s)', 'Variant SKU(s)',
        ];

        $attributeColumns = $this->getExportAttributeColumns($products, $fixedColumns);

        $format = strtolower((string) $request->input('format', 'csv'));

        if ($format === 'xlsx' || $format === 'excel') {
            return $this->exportXlsx($products, $fixedColumns, $attributeColumns);
        }

        return $this->exportCsv($products, $fixedColumns, $attributeColumns);
    }

    /**
     * Build and execute the query for product export based on request filters.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    private function buildExportProducts(Store $store, Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::where('store_id', $store->id)
            ->with(['category.parent', 'brand', 'images', 'variants']);

        // Enhanced Search: name, SKU, brand name, category name, barcode, etc.
        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('barcode', 'like', '%' . $search . '%')
                  ->orWhere('compatible_models', 'like', '%' . $search . '%')
                  ->orWhere('shelf_location', 'like', '%' . $search . '%')
                  ->orWhereHas('brand', function ($bq) use ($search) {
                      $bq->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('variants', function ($vq) use ($search) {
                      $vq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('sku', 'like', '%' . $search . '%');
                  });
            });
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // Filter by product archetype
        if ($request->filled('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        // Filter by online visibility
        if ($request->filled('is_ecommerce')) {
            $query->where('is_ecommerce', $request->is_ecommerce === 'online');
        }

        // Filter by Category (and subcategories)
        if ($request->filled('category_id')) {
            $catId = (int) $request->category_id;
            $allCategories = Category::where('store_id', $store->id)->get();
            $categoryTree = $allCategories
                ->whereNull('parent_id')
                ->map(fn ($main) => (object) [
                    'category' => $main,
                    'children' => $allCategories->where('parent_id', $main->id)->values(),
                ])
                ->values();
            $childIds = collect($categoryTree)->firstWhere('category.id', $catId)?->children->pluck('id') ?? collect();
            $query->whereIn('category_id', $childIds->push($catId)->unique()->values()->all());
        }

        // Filter by Brand
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Sorting
        $sort = $request->input('sort', 'sku');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('retail_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('retail_price', 'desc');
                break;
            case 'stock':
                $query->orderBy('stock_status', 'asc');
                break;
            case 'newest':
                $query->latest();
                break;
            default:
                $query->orderBy('sku', 'asc');
                break;
        }

        // Respect the toolbar's per-page choice (25 / 50 / 100 / 200 / all).
        // Absent per_page keeps the legacy full export (import pages rely on it).
        $perPage = $request->input('per_page');
        if ($perPage !== 'all' && in_array((int) $perPage, [25, 50, 100, 200], true)) {
            $query->limit((int) $perPage);
        }

        return $query->get();
    }

    /**
     * Extract dynamic variant-attribute columns from the given products.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Product> $products
     * @param array<int, string> $fixedColumns
     * @return array<int, string>
     */
    private function getExportAttributeColumns(\Illuminate\Database\Eloquent\Collection $products, array $fixedColumns): array
    {
        $normalizedFixed = array_map(fn ($label) => $this->normalizeHeaderKey($label), $fixedColumns);
        $attributeColumns = [];

        foreach ($products as $product) {
            foreach (\App\Support\ProductSpecifications::structuredFor($product) as $key => $value) {
                if (str_starts_with($key, 'attr_')) {
                    $label = substr($key, 5);
                    if (! in_array($this->normalizeHeaderKey($label), $normalizedFixed, true)
                        && ! in_array($label, $attributeColumns, true)) {
                        $attributeColumns[] = $label;
                    }
                }
            }
        }

        sort($attributeColumns);

        return $attributeColumns;
    }

    /**
     * Export products to XLSX spreadsheet format.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Product> $products
     * @param array<int, string> $fixedColumns
     * @param array<int, string> $attributeColumns
     */
    private function exportXlsx(
        \Illuminate\Database\Eloquent\Collection $products,
        array $fixedColumns,
        array $attributeColumns
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $filename = 'products-' . now()->format('Ymd-His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_prod_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');

        $allHeaders = array_merge($fixedColumns, $attributeColumns);
        $sheet->fromArray([$allHeaders], null, 'A1');

        // Header styling
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->freezePane('A2');

        $rowIndex = 2;
        foreach ($products as $product) {
            $specs = \App\Support\ProductSpecifications::structuredFor($product);

            $images = collect([$product->image_path])
                ->merge($product->images->pluck('image_path'))
                ->filter()
                ->unique()
                ->implode('; ');

            $variantsJson = $product->variants
                ->map(fn ($v) => [
                    'name' => $v->name,
                    'attributes' => $v->attributes ?? [],
                    'sku' => $v->sku,
                    'retail_price' => (float) $v->retail_price,
                    'wholesale_price' => $v->wholesale_price !== null ? (float) $v->wholesale_price : null,
                    'stock_status' => $v->stock_status,
                ])
                ->values()
                ->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $row = [
                (string) $product->sku,
                (string) $product->name,
                $product->category?->name ?? '',
                $product->category?->parent?->name ?? '',
                $product->brand?->name ?? '',
                (float) $product->retail_price,
                (float) $product->wholesale_price,
                $product->old_price !== null ? (float) $product->old_price : '',
                $product->sale_starts_at ? $product->sale_starts_at->format('Y-m-d H:i') : '',
                $product->sale_ends_at ? $product->sale_ends_at->format('Y-m-d H:i') : '',
                $product->stock_status,
                $specs['stock'] ?? '',
                $product->warranty ?? '',
                $product->return_policy ?? '',
                $product->meta_description ?? '',
                $product->is_featured ? 'yes' : 'no',
                $product->description ?? '',
                \App\Support\SafeHtml::sanitize($product->description ?? ''),
                $images,
                $variantsJson,
                $specs['variant_names'] ?? '',
                $specs['variant_skus'] ?? '',
            ];
            foreach ($attributeColumns as $column) {
                $row[] = $specs['attr_' . $column] ?? '';
            }

            $sheet->fromArray([$row], null, "A{$rowIndex}");

            // Number format for retail & wholesale prices
            $sheet->getStyle("F{$rowIndex}:H{$rowIndex}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getRowDimension($rowIndex)->setRowHeight(20);
            $rowIndex++;
        }

        foreach (range('A', $highestCol) as $col) {
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
     * Export products to CSV format.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Product> $products
     * @param array<int, string> $fixedColumns
     * @param array<int, string> $attributeColumns
     */
    private function exportCsv(
        \Illuminate\Database\Eloquent\Collection $products,
        array $fixedColumns,
        array $attributeColumns
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $filename = 'products-' . now()->format('Ymd-His') . '.csv';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_csv_');
        $stream = fopen($tempFile, 'w');

        // UTF-8 BOM so Excel opens the file with correct encoding
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, array_merge($fixedColumns, $attributeColumns));

        foreach ($products as $product) {
            $specs = \App\Support\ProductSpecifications::structuredFor($product);

            $images = collect([$product->image_path])
                ->merge($product->images->pluck('image_path'))
                ->filter()
                ->unique()
                ->implode('; ');

            // Variants as JSON — same format the importer accepts, so a
            // product list can be edited offline and re-imported.
            $variantsJson = $product->variants
                ->map(fn ($v) => [
                    'name' => $v->name,
                    'attributes' => $v->attributes ?? [],
                    'sku' => $v->sku,
                    'retail_price' => (float) $v->retail_price,
                    'wholesale_price' => $v->wholesale_price !== null ? (float) $v->wholesale_price : null,
                    'stock_status' => $v->stock_status,
                ])
                ->values()
                ->toJson(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $row = [
                $this->csvCell($product->sku),
                $this->csvCell($product->name),
                $this->csvCell($product->category?->name ?? ''),
                $this->csvCell($product->category?->parent?->name ?? ''),
                $this->csvCell($product->brand?->name ?? ''),
                number_format((float) $product->retail_price),
                number_format((float) $product->wholesale_price),
                $product->old_price !== null ? number_format((float) $product->old_price) : '',
                $product->sale_starts_at ? $product->sale_starts_at->format('Y-m-d H:i') : '',
                $product->sale_ends_at ? $product->sale_ends_at->format('Y-m-d H:i') : '',
                $product->stock_status,
                $this->csvCell($specs['stock'] ?? ''),
                $this->csvCell($product->warranty ?? ''),
                $this->csvCell($product->return_policy ?? ''),
                $this->csvCell($product->meta_description ?? ''),
                $product->is_featured ? 'yes' : 'no',
                $this->csvCell($product->description ?? ''),
                $this->csvCell(\App\Support\SafeHtml::sanitize($product->description ?? '')),
                $this->csvCell($images),
                $variantsJson,
                $this->csvCell($specs['variant_names'] ?? ''),
                $this->csvCell($specs['variant_skus'] ?? ''),
            ];
            foreach ($attributeColumns as $column) {
                $row[] = $this->csvCell($specs['attr_' . $column] ?? '');
            }

            fputcsv($stream, $row);
        }

        fclose($stream);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Server-rendered Description | Specifications partial for the admin
     * product-list detail modal. Uses the same SafeHtml sanitizer and
     * ProductSpecifications presenter as the storefront, so staff see exactly
     * what shoppers see.
     */
    public function details(string $store_slug, Product $product, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        $product->load(['category.parent', 'brand', 'variants', 'images', 'warehouse', 'supplier']);

        return view('admin.products._details', [
            'product' => $product,
            'store'   => $store,
        ]);
    }

    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        $categories = Category::where('store_id', $store->id)->with('parent')->get();
        $brands = Brand::where('store_id', $store->id)->get();
        $suppliers = \App\Models\Supplier::where('store_id', $store->id)->orderBy('name')->get(['id', 'name']);
        $warehouses = \App\POS\Models\Warehouse::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $variantPresets = $this->variantPresets($store->id);
        $masterPresets = \App\Models\ProductMasterPreset::where('store_id', $store->id)->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $imageMaxMb = self::IMAGE_MAX_KB / 1024;
        $product = new Product(['store_id' => $store->id, 'stock_status' => 'in_stock', 'product_type' => 'standard']);

        $returnTo = AdminListReturn::peek('admin_products_return', '/store/' . $store->slug . '/admin/products');

        return view('admin.products.create', compact('store', 'categories', 'brands', 'suppliers', 'warehouses', 'variantPresets', 'masterPresets', 'imageMaxMb', 'product', 'returnTo'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            // SKU is optional on create — the Auto-SKU toggle generates one.
            'sku'             => ['nullable', 'string', 'max:100'],
            'product_type'    => ['nullable', 'string', 'in:standard,serialized,variant,service,digital,weight_based'],
            'barcode'         => ['nullable', 'string', 'max:100'],
            'shelf_location'  => ['nullable', 'string', 'max:100'],
            'warehouse_id'    => ['nullable', \Illuminate\Validation\Rule::exists('warehouses', 'id')->where('store_id', $store->id)],
            'category_id'     => ['nullable', \Illuminate\Validation\Rule::exists('categories', 'id')->where('store_id', $store->id)],
            'brand_id'        => ['nullable', \Illuminate\Validation\Rule::exists('brands', 'id')->where('store_id', $store->id)],
            'retail_price'    => ['required', 'numeric', 'min:0'],
            'old_price'       => ['nullable', 'numeric', 'min:0'],
            'sale_starts_at'  => ['nullable', 'date'],
            'sale_ends_at'    => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'stock_status'    => ['nullable', 'in:in_stock,out_of_stock'],
            'auto_sku'        => ['nullable', 'boolean'],
            'reorder_level'   => ['nullable', 'numeric', 'min:0'],
            'supplier_id'     => ['nullable', \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('store_id', $store->id)],
            'purchase_cost'   => ['nullable', 'numeric', 'min:0'],
            'initial_stock'   => ['nullable', 'numeric', 'min:0'],
            'service_duration'=> ['nullable', 'string', 'max:100'],
            'digital_delivery_method' => ['nullable', 'string', 'max:100'],
            'is_ecommerce'    => ['nullable', 'boolean'],
            'image'           => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            'gallery_images'  => ['nullable', 'array', 'max:' . self::MAX_GALLERY_IMAGES],
            'gallery_images.*'=> ['image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            'description'     => ['nullable', 'string'],
            'compatible_models'=> ['nullable', 'string'],
            'specs'           => ['nullable', 'array'],
            'meta_description'=> ['nullable', 'string', 'max:1000'],
            'warranty'        => ['nullable', 'string', 'max:255'],
            'return_policy'   => ['nullable', 'string', 'max:255'],
            'is_featured'     => ['boolean'],
            'variants'        => ['nullable', 'array', 'max:30'],
            'variants.*.id'             => ['nullable', 'integer'],
            'variants.*.name'           => ['required', 'string', 'max:255'],
            'variants.*.sku'            => ['nullable', 'string', 'max:100'],
            'variants.*.retail_price'   => ['required', 'numeric', 'min:0'],
            'variants.*.wholesale_price'=> ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_status'   => ['nullable', 'in:in_stock,out_of_stock'],
            'variants.*.quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_default'     => ['nullable', 'boolean'],
            'variants.*.attributes'     => ['nullable', 'array', 'max:5'],
            'variants.*.attributes.*.label' => ['required', 'string', 'max:50'],
            'variants.*.attributes.*.value' => ['required', 'string', 'max:100'],
            'variants.*.remove_image'   => ['nullable', 'boolean'],
        ]);

        // Auto-SKU: generate a store-unique code when the toggle is on or no
        // SKU was typed (the create form disables the field in auto mode).
        $sku = trim((string) ($validated['sku'] ?? ''));
        if ($request->boolean('auto_sku') || $sku === '') {
            do {
                $sku = 'SKU-' . strtoupper(Str::random(8));
            } while (Product::where('store_id', $store->id)
                ->whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])
                ->exists());
        }

        // Duplicate SKU check within store (case-insensitive)
        if (Product::where('store_id', $store->id)
            ->whereRaw('LOWER(sku) = ?', [mb_strtolower($sku)])
            ->exists()) {
            return back()->withErrors(['sku' => 'A product with this SKU already exists in this store.'])->withInput();
        }

        if ($error = $this->variantSkuError($store, $validated['variants'] ?? [], $sku)) {
            return back()->withErrors(['variants' => $error])->withInput();
        }

        if ($error = $this->variantNameError($validated['variants'] ?? [])) {
            return back()->withErrors(['variants' => $error])->withInput();
        }

        $imagePath = $request->hasFile('image')
            ? ImageOptimizer::store($request->file('image'), 'products', 1600)
            : null;

        // Auto-derive stock status: if initial stock is provided, starts in_stock; else out_of_stock (or passed value)
        $initialStock = isset($validated['initial_stock']) ? (float) $validated['initial_stock'] : 0.0;
        $stockStatus = $initialStock > 0 ? 'in_stock' : ($validated['stock_status'] ?? 'out_of_stock');

        $product = Product::create([
            'store_id'        => $store->id,
            'category_id'     => $validated['category_id'] ?? null,
            'brand_id'        => $validated['brand_id'] ?? null,
            'sku'             => $sku,
            'product_type'    => $validated['product_type'] ?? 'standard',
            'barcode'         => $validated['barcode'] ?? null,
            'name'            => $validated['name'],
            'slug'            => Str::slug($validated['name'] . '-' . Str::random(5)),
            'description'     => $validated['description'] ?? null,
            'compatible_models'=> $validated['compatible_models'] ?? null,
            'specs'           => $validated['specs'] ?? null,
            'meta_description'=> $validated['meta_description'] ?? null,
            'retail_price'    => $validated['retail_price'],
            'old_price'       => $validated['old_price'] ?? null,
            'sale_starts_at'  => $validated['sale_starts_at'] ?? null,
            'sale_ends_at'    => $validated['sale_ends_at'] ?? null,
            'wholesale_price' => $validated['wholesale_price'],
            'stock_status'    => $stockStatus,
            'image_path'      => $imagePath,
            'warranty'        => $validated['warranty'] ?? null,
            'return_policy'   => $validated['return_policy'] ?? null,
            'is_featured'     => $request->boolean('is_featured', false),
            'reorder_level'   => $validated['reorder_level'] ?? null,
            'shelf_location'  => $validated['shelf_location'] ?? null,
            'warehouse_id'    => $validated['warehouse_id'] ?? null,
            'supplier_id'     => $validated['supplier_id'] ?? null,
            'purchase_cost'   => $validated['purchase_cost'] ?? null,
            'service_duration'=> $validated['service_duration'] ?? null,
            'digital_delivery_method' => $validated['digital_delivery_method'] ?? null,
            // Hidden 0-input + checkbox: boolean() reflects the checkbox state.
            'is_ecommerce'    => $request->boolean('is_ecommerce', true),
        ]);

        $this->storeGalleryImages($product, $request->file('gallery_images', []));
        $this->syncVariants($product, $validated['variants'] ?? null, $request->file('variants', []));

        // Variant products: main stock is derived from per-variant quantities.
        if (($validated['product_type'] ?? 'standard') === 'variant') {
            $product->update(['stock_status' => $this->variantProductStockStatus($product)]);
        }

        // Initial stock on create → one opening_balance ledger movement so the
        // product starts with real stock (valued at the purchase cost when set).
        if ($initialStock > 0) {
            app(InventoryService::class)->postMovement([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'product_variant_id' => null,
                'movement_type' => InventoryMovementType::OpeningBalance->value,
                'quantity_delta' => (string) $validated['initial_stock'],
                'unit_cost' => $validated['purchase_cost'] ?? null,
                'source_type' => 'product_create',
                'source_id' => $product->id,
                'client_transaction_id' => 'product-create:' . $product->id . ':initial',
                'occurred_at' => now(),
                'posted_by' => $request->user()?->id,
            ]);
        }

        return redirect(AdminListReturn::resolve('admin_products_return', '/store/' . $store->slug . '/admin/products'))
            ->with('success', __('messages.product_created'));
    }

    public function edit(string $store_slug, Product $product, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        $categories = Category::where('store_id', $store->id)->with('parent')->get();
        $brands = Brand::where('store_id', $store->id)->get();
        $suppliers = \App\Models\Supplier::where('store_id', $store->id)->orderBy('name')->get(['id', 'name']);
        $warehouses = \App\POS\Models\Warehouse::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $variantPresets = $this->variantPresets($store->id);
        $masterPresets = \App\Models\ProductMasterPreset::where('store_id', $store->id)->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $images = $product->images;
        $variants = $product->variants;
        $imageMaxMb = self::IMAGE_MAX_KB / 1024;
        $maxGalleryImages = self::MAX_GALLERY_IMAGES;
        $remainingGallerySlots = max(0, $maxGalleryImages - $images->count());

        $returnTo = AdminListReturn::peek('admin_products_return', '/store/' . $store->slug . '/admin/products');

        return view('admin.products.edit', compact('store', 'product', 'categories', 'brands', 'suppliers', 'warehouses', 'variantPresets', 'masterPresets', 'images', 'variants', 'imageMaxMb', 'maxGalleryImages', 'remainingGallerySlots', 'returnTo'));
    }

    public function update(Request $request, string $store_slug, Product $product, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'sku'             => ['required', 'string', 'max:100'],
            'product_type'    => ['nullable', 'string', 'in:standard,serialized,variant,service,digital,weight_based'],
            'barcode'         => ['nullable', 'string', 'max:100'],
            'shelf_location'  => ['nullable', 'string', 'max:100'],
            'warehouse_id'    => ['nullable', \Illuminate\Validation\Rule::exists('warehouses', 'id')->where('store_id', $store->id)],
            'category_id'     => ['nullable', \Illuminate\Validation\Rule::exists('categories', 'id')->where('store_id', $store->id)],
            'brand_id'        => ['nullable', \Illuminate\Validation\Rule::exists('brands', 'id')->where('store_id', $store->id)],
            'retail_price'    => ['required', 'numeric', 'min:0'],
            'old_price'       => ['nullable', 'numeric', 'min:0'],
            'sale_starts_at'  => ['nullable', 'date'],
            'sale_ends_at'    => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'stock_status'    => ['nullable', 'in:in_stock,out_of_stock'],
            'reorder_level'   => ['nullable', 'numeric', 'min:0'],
            'supplier_id'     => ['nullable', \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('store_id', $store->id)],
            'purchase_cost'   => ['nullable', 'numeric', 'min:0'],
            'service_duration'=> ['nullable', 'string', 'max:100'],
            'digital_delivery_method' => ['nullable', 'string', 'max:100'],
            'is_ecommerce'    => ['nullable', 'boolean'],
            'image'           => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            'gallery_images'  => ['nullable', 'array', 'max:' . self::MAX_GALLERY_IMAGES],
            'gallery_images.*'=> ['image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            'description'     => ['nullable', 'string'],
            'compatible_models'=> ['nullable', 'string'],
            'specs'           => ['nullable', 'array'],
            'meta_description'=> ['nullable', 'string', 'max:1000'],
            'warranty'        => ['nullable', 'string', 'max:255'],
            'return_policy'   => ['nullable', 'string', 'max:255'],
            'is_featured'     => ['boolean'],
            'variants'        => ['nullable', 'array', 'max:30'],
            'variants.*.id'             => ['nullable', 'integer'],
            'variants.*.name'           => ['required', 'string', 'max:255'],
            'variants.*.sku'            => ['nullable', 'string', 'max:100'],
            'variants.*.retail_price'   => ['required', 'numeric', 'min:0'],
            'variants.*.wholesale_price'=> ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_status'   => ['nullable', 'in:in_stock,out_of_stock'],
            'variants.*.quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
            'variants.*.is_default'     => ['nullable', 'boolean'],
            'variants.*.attributes'     => ['nullable', 'array', 'max:5'],
            'variants.*.attributes.*.label' => ['required', 'string', 'max:50'],
            'variants.*.attributes.*.value' => ['required', 'string', 'max:100'],
            'variants.*.remove_image'   => ['nullable', 'boolean'],
        ]);

        // Duplicate SKU check within store ignoring self (case-insensitive)
        if (Product::where('store_id', $store->id)
            ->whereRaw('LOWER(sku) = ?', [mb_strtolower($validated['sku'])])
            ->where('id', '!=', $product->id)
            ->exists()) {
            return back()->withErrors(['sku' => 'A product with this SKU already exists in this store.'])->withInput();
        }

        if ($error = $this->variantSkuError($store, $validated['variants'] ?? [], $validated['sku'], $product)) {
            return back()->withErrors(['variants' => $error])->withInput();
        }

        if ($error = $this->variantNameError($validated['variants'] ?? [])) {
            return back()->withErrors(['variants' => $error])->withInput();
        }

        $galleryFiles = $request->file('gallery_images', []);
        if ($galleryFiles !== [] && $product->images()->count() + count($galleryFiles) > self::MAX_GALLERY_IMAGES) {
            return back()->withErrors([
                'gallery_images' => 'A product gallery can have up to ' . self::MAX_GALLERY_IMAGES . ' images. Delete an existing image before uploading more.',
            ])->withInput();
        }

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $imagePath = ImageOptimizer::store($request->file('image'), 'products', 1600);
        }

        // Auto-derive stock status from inventory balances if inventory exists
        $hasInventoryBalance = \Illuminate\Support\Facades\DB::table('inventory_balances')
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($hasInventoryBalance) {
            $totalOnHand = (float) \Illuminate\Support\Facades\DB::table('inventory_balances')
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->sum('quantity_on_hand');
            $stockStatus = $totalOnHand > 0 ? 'in_stock' : 'out_of_stock';
        } else {
            $stockStatus = $validated['stock_status'] ?? $product->stock_status ?? 'out_of_stock';
        }

        // Data-safety: only write nullable fields when the request actually sent
        // them. A field the form left untouched (absent) must preserve the
        // persisted value; a field explicitly sent blank is an intentional clear.
        // $request->has() is presence-based, so '' counts as "sent".
        $product->update([
            'category_id'     => $request->has('category_id') ? ($validated['category_id'] ?? null) : $product->category_id,
            'brand_id'        => $request->has('brand_id') ? ($validated['brand_id'] ?? null) : $product->brand_id,
            'sku'             => $validated['sku'],
            'product_type'    => $request->has('product_type') ? ($validated['product_type'] ?? 'standard') : $product->product_type,
            'barcode'         => $request->has('barcode') ? ($validated['barcode'] ?? null) : $product->barcode,
            'shelf_location'  => $request->has('shelf_location') ? ($validated['shelf_location'] ?? null) : $product->shelf_location,
            'warehouse_id'    => $request->has('warehouse_id') ? ($validated['warehouse_id'] ?? null) : $product->warehouse_id,
            'name'            => $validated['name'],
            'description'     => $request->has('description') ? ($validated['description'] ?? null) : $product->description,
            'compatible_models'=> $request->has('compatible_models') ? ($validated['compatible_models'] ?? null) : $product->compatible_models,
            'specs'           => $request->has('specs') ? ($validated['specs'] ?? null) : $product->specs,
            'meta_description'=> $request->has('meta_description') ? ($validated['meta_description'] ?? null) : $product->meta_description,
            'retail_price'    => $validated['retail_price'],
            'old_price'       => $validated['old_price'] ?? null,
            'sale_starts_at'  => $validated['sale_starts_at'] ?? null,
            'sale_ends_at'    => $validated['sale_ends_at'] ?? null,
            'wholesale_price' => $validated['wholesale_price'],
            'stock_status'    => $stockStatus,
            'image_path'      => $imagePath,
            'warranty'        => $request->has('warranty') ? ($validated['warranty'] ?? null) : $product->warranty,
            'return_policy'   => $request->has('return_policy') ? ($validated['return_policy'] ?? null) : $product->return_policy,
            'is_featured'     => $request->boolean('is_featured', false),
            'reorder_level'   => $request->has('reorder_level') ? ($validated['reorder_level'] ?? null) : $product->reorder_level,
            'supplier_id'     => $request->has('supplier_id') ? ($validated['supplier_id'] ?? null) : $product->supplier_id,
            'purchase_cost'   => $request->has('purchase_cost') ? ($validated['purchase_cost'] ?? null) : $product->purchase_cost,
            'service_duration'=> $request->has('service_duration') ? ($validated['service_duration'] ?? null) : $product->service_duration,
            'digital_delivery_method' => $request->has('digital_delivery_method') ? ($validated['digital_delivery_method'] ?? null) : $product->digital_delivery_method,
            'is_ecommerce'    => $request->has('is_ecommerce') ? $request->boolean('is_ecommerce') : $product->is_ecommerce,
        ]);

        if ($galleryFiles !== []) {
            $this->storeGalleryImages($product, $galleryFiles);
        }

        $this->syncVariants($product, $validated['variants'] ?? null, $request->file('variants', []));

        // Variant products: main stock is derived from per-variant quantities.
        if (($validated['product_type'] ?? 'standard') === 'variant') {
            $product->update(['stock_status' => $this->variantProductStockStatus($product)]);
        }

        return redirect(AdminListReturn::resolve('admin_products_return', '/store/' . $store->slug . '/admin/products'))
            ->with('success', __('messages.product_updated'));
    }

    public function uploadImages(Request $request, string $store_slug, Product $product, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        $request->validate([
            'images'   => ['required', 'array', 'min:1', 'max:' . self::MAX_GALLERY_IMAGES],
            'images.*' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
        ]);

        $files = $request->file('images', []);
        $existingImageCount = $product->images()->count();

        if ($existingImageCount + count($files) > self::MAX_GALLERY_IMAGES) {
            return back()->withErrors([
                'images' => 'A product gallery can have up to ' . self::MAX_GALLERY_IMAGES . ' images. Delete an existing image before uploading more.',
            ]);
        }

        $this->storeGalleryImages($product, $files);

        return back()->with('success', 'Gallery images uploaded successfully.');
    }

    private function storeGalleryImages(Product $product, array $files): void
    {
        foreach ($files as $file) {
            $path = ImageOptimizer::store($file, 'products', 1600);
            $hasPrimary = $product->images()->where('is_primary', true)->exists();

            $productImage = $product->images()->create([
                'image_path' => $path,
                'is_primary' => !$hasPrimary && empty($product->image_path),
                'sort_order' => $product->images()->count() + 1,
            ]);

            if (!$product->image_path) {
                $product->update(['image_path' => $path]);
            }
        }
    }

    /**
     * Sync the product's variants from the form repeater: rows with an id are
     * updated, rows without one are created, rows removed in the UI are
     * deleted. Exactly one variant is marked is_default (the checked one, or
     * the first row otherwise). Per-variant images come from $variantFiles,
     * keyed by the same row index as the repeater.
     */
    private function syncVariants(Product $product, ?array $variants, array $variantFiles = []): void
    {
        $rows = collect($variants ?? [])
            ->filter(fn ($v) => !empty($v['name']))
            ->values();

        $submittedIds = $rows->pluck('id')->filter()->all();
        $product->variants()->whereNotIn('id', $submittedIds)->delete();

        $defaultId = null;
        foreach ($rows as $i => $v) {
            $data = [
                'name'            => $v['name'],
                'attributes'      => $this->normalizeVariantAttributes($v['attributes'] ?? []),
                'sku'             => $v['sku'] ?? null,
                'retail_price'    => $v['retail_price'],
                'wholesale_price' => !empty($v['wholesale_price']) ? $v['wholesale_price'] : null,
                'stock_status'    => $this->variantStockStatus($v),
                'quantity_on_hand'=> array_key_exists('quantity_on_hand', $v) && $v['quantity_on_hand'] !== '' && $v['quantity_on_hand'] !== null
                    ? (float) $v['quantity_on_hand']
                    : 0.0,
                'sort_order'      => $i,
                'is_default'      => false,
            ];

            if (!empty($v['id']) && ($existing = $product->variants()->find($v['id']))) {
                if (!empty($v['remove_image'])) {
                    if ($existing->image_path) {
                        Storage::disk('public')->delete($existing->image_path);
                    }
                    $data['image_path'] = null;
                }
                $existing->update($data);
                $variantId = $existing->id;
            } else {
                $variantId = $product->variants()->create($data)->id;
            }

            // Optional per-variant image upload (same row index)
            if (!empty($variantFiles[$i]['image'])) {
                $path = ImageOptimizer::store($variantFiles[$i]['image'], 'products/variants', 1200);
                $variant = $product->variants()->find($variantId);
                if ($variant->image_path) {
                    Storage::disk('public')->delete($variant->image_path);
                }
                $variant->update(['image_path' => $path]);
            }

            if (!empty($v['is_default']) && $defaultId === null) {
                $defaultId = $variantId;
            }
        }

        $defaultId = $defaultId ?? $product->variants()->orderBy('sort_order')->value('id');
        $product->variants()->update(['is_default' => false]);
        if ($defaultId) {
            $product->variants()->where('id', $defaultId)->update(['is_default' => true]);
        }
    }

    /**
     * A variant row's stock status: explicit quantity wins (qty > 0 ⇒ in
     * stock), otherwise fall back to the submitted status.
     */
    private function variantStockStatus(array $v): string
    {
        if (array_key_exists('quantity_on_hand', $v) && $v['quantity_on_hand'] !== '' && $v['quantity_on_hand'] !== null) {
            return (float) $v['quantity_on_hand'] > 0 ? 'in_stock' : 'out_of_stock';
        }

        return $v['stock_status'] ?? 'in_stock';
    }

    /**
     * Main product stock for a variant product: in stock when any variant has
     * a positive quantity on hand (falls back to legacy stock_status flags).
     */
    private function variantProductStockStatus(Product $product): string
    {
        $variants = $product->variants()->get(['quantity_on_hand', 'stock_status']);

        foreach ($variants as $variant) {
            if ((float) ($variant->quantity_on_hand ?? 0) > 0) {
                return 'in_stock';
            }
        }

        return $variants->contains(fn ($variant) => $variant->stock_status === 'in_stock')
            ? 'in_stock'
            : 'out_of_stock';
    }

    /**
     * Normalize the attributes array a variant row submits (label/value pairs,
     * e.g. [{label: "Storage", value: "256GB"}, {label: "Color", value: "Black"}]).
     */
    private function normalizeVariantAttributes(array $attributes): array
    {
        return collect($attributes)
            ->map(fn ($attr) => [
                'label' => trim((string) ($attr['label'] ?? '')),
                'value' => trim((string) ($attr['value'] ?? '')),
            ])
            ->filter(fn ($attr) => $attr['label'] !== '' && $attr['value'] !== '')
            ->values()
            ->take(5)
            ->all();
    }

    private function variantNameError(array $variants): ?string
    {
        $names = collect($variants)
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->filter(fn ($name) => $name !== '')
            ->values();

        if ($names->duplicates()->isNotEmpty()) {
            return 'Variant names must be unique within this product.';
        }

        return null;
    }

    private function variantPresets(int $storeId)
    {
        return VariantPreset::where('store_id', $storeId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (VariantPreset $preset): array => [
                'id' => $preset->id,
                'name' => $preset->name,
                'category_family' => $preset->category_family,
                'options' => $preset->options ?? [],
            ]);
    }

    private function variantSkuError(Store $store, array $variants, string $productSku, ?Product $product = null): ?string
    {
        $skus = collect($variants)
            ->pluck('sku')
            ->filter(fn ($sku) => is_string($sku) && trim($sku) !== '')
            ->map(fn ($sku) => trim($sku))
            ->values();

        if ($skus->isEmpty()) {
            return null;
        }

        $lowerSkus = $skus->map(fn ($sku) => mb_strtolower($sku));

        if ($lowerSkus->duplicates()->isNotEmpty()) {
            return 'Variant SKU codes must be unique within this product.';
        }

        if ($lowerSkus->contains(mb_strtolower($productSku))) {
            return 'Variant SKU codes must be different from the main product SKU.';
        }

        $productSkuExists = Product::where('store_id', $store->id)
            ->whereIn('sku', $skus)
            ->when($product, fn ($query) => $query->where('id', '!=', $product->id))
            ->exists();

        if ($productSkuExists) {
            return 'One or more variant SKU codes are already used by another product in this store.';
        }

        $submittedIds = collect($variants)->pluck('id')->filter()->all();
        if ($product && $submittedIds !== []) {
            $submittedIds = $product->variants()
                ->whereIn('id', $submittedIds)
                ->pluck('id')
                ->all();
        }
        $variantSkuExists = ProductVariant::whereIn('sku', $skus)
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
            ->when($submittedIds !== [], fn ($query) => $query->whereNotIn('id', $submittedIds))
            ->exists();

        if ($variantSkuExists) {
            return 'One or more variant SKU codes are already used by another variant in this store.';
        }

        return null;
    }

    public function setPrimaryImage(string $store_slug, Product $product, \App\Models\ProductImage $image, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id || $image->product_id !== $product->id) {
            abort(403, 'Unauthorized product image action.');
        }

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);
        $product->update(['image_path' => $image->image_path]);

        return back()->with('success', 'Primary image updated successfully.');
    }

    public function deleteImage(string $store_slug, Product $product, \App\Models\ProductImage $image, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id || $image->product_id !== $product->id) {
            abort(403, 'Unauthorized product image action.');
        }

        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary || $product->image_path === $image->image_path) {
            /** @var \App\Models\ProductImage|null $nextImage */
            $nextImage = $product->images()->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
                $product->update(['image_path' => $nextImage->image_path]);
            } else {
                $product->update(['image_path' => null]);
            }
        }

        return back()->with('success', 'Product image deleted successfully.');
    }

    public function importForm(StoreContext $context): View
    {
        $store = $context->getStore();
        $histories = ImportHistory::where('store_id', $store->id)
            ->where('type', 'products')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.products.import', compact('store', 'histories'));
    }

    public function import(Request $request, StoreContext $context, ProductImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'duplicate_strategy' => ['nullable', 'in:skip,update'],
        ]);

        $file = $request->file('file');
        $safeName = $this->safeImportFilename($file->getClientOriginalName());
        $storedPath = $file->storeAs(
            'imports/tmp',
            Str::uuid()->toString() . '-' . $safeName,
            'local'
        );

        try {
            $duplicateStrategy = $validated['duplicate_strategy'] ?? 'skip';
            $fullPath = Storage::disk('local')->path($storedPath);
            $preview = $importer->preview($fullPath, $store, $duplicateStrategy);
            $token = Str::random(40);

            session()->put("imports.products.{$token}", [
                'path' => $storedPath,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
            ]);

            return back()->with('import_preview', $preview + [
                'token' => $token,
                'filename' => $safeName,
                'duplicate_strategy' => $duplicateStrategy,
            ]);

        } catch (\InvalidArgumentException $e) {
            Storage::disk('local')->delete($storedPath);
            return back()->withErrors(['file' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            Storage::disk('local')->delete($storedPath);
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function confirmImport(Request $request, StoreContext $context, ProductImportService $importer): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'duplicate_strategy' => ['required', 'in:skip,update'],
        ]);

        $sessionKey = "imports.products.{$validated['token']}";
        $pendingImport = session()->pull($sessionKey);

        if (!$pendingImport || empty($pendingImport['path'])) {
            return back()->withErrors(['file' => 'Import preview expired. Please upload the file again.']);
        }

        $storedPath = $pendingImport['path'];

        try {
            $result = $importer->import(
                Storage::disk('local')->path($storedPath),
                $store,
                $request->user(),
                $pendingImport['filename'] ?? 'products-import.csv',
                $validated['duplicate_strategy']
            );

            return back()->with('import_result', $result);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        } finally {
            Storage::disk('local')->delete($storedPath);
        }
    }

    public function downloadImportTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_tpl_');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Products');
        $sheet->fromArray([
            [
                'name',
                'sku',
                'brand',
                'category',
                'parent_category',
                'retail_price',
                'wholesale_price',
                'old_price',
                'sale_starts_at',
                'sale_ends_at',
                'stock_status',
                'warranty',
                'return_policy',
                'description',
                'meta_description',
                'image_url',
                'featured',
                'variants',
            ],
            [
                'iPhone 15 Pro Max Tempered Glass',
                'SKU-001',
                'Apple',
                'Tempered Glass',
                'Accessories',
                '18000',
                '15000',
                '22000',
                '2026-08-01 00:00',
                '2026-08-31 23:59',
                'in_stock',
                '7 days',
                'No return after installation',
                'Premium clear glass',
                'Best-selling screen protector for iPhone 15 Pro Max',
                '',
                'yes',
                '[{"name":"128GB","sku":"SKU-001-128","retail_price":18000,"wholesale_price":15000,"stock_status":"in_stock"},{"name":"256GB","sku":"SKU-001-256","retail_price":19500,"wholesale_price":16200,"stock_status":"in_stock"}]',
            ],
            // Example 2 — one product, three connector variants with DIFFERENT
            // prices + variant attributes (these become the Specifications tab).
            [
                'L2009 Fast Charging Cable',
                'L2009',
                '168',
                'Cable',
                'Accessories',
                '5000',
                '4500',
                '',
                '',
                '',
                'in_stock',
                '1 Month Warranty',
                '',
                'Fast charging cable with detachable heads.',
                '',
                '',
                'no',
                '[{"name":"Type C","sku":"L2009-TC","retail_price":5000,"wholesale_price":4500,"stock_status":"in_stock","attributes":[{"label":"Connector","value":"Type C"},{"label":"Length","value":"1.2m"}]},{"name":"Micro","sku":"L2009-MC","retail_price":4000,"wholesale_price":3700,"stock_status":"in_stock","attributes":[{"label":"Connector","value":"Micro"}]},{"name":"Lightning","sku":"L2009-LT","retail_price":7000,"wholesale_price":6300,"stock_status":"in_stock","attributes":[{"label":"Connector","value":"Lightning"}]}]',
            ],
        ]);

        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Instructions');
        $instructionSheet->fromArray([
            ['Instruction', 'Value'],
            ['Required columns', 'name, sku, retail_price, wholesale_price, stock_status'],
            ['Optional columns', 'brand, category, parent_category, old_price, sale_starts_at, sale_ends_at, warranty, return_policy, description, meta_description, image_url, featured, variants'],
            ['Allowed stock_status', 'in_stock, out_of_stock'],
            ['parent_category', 'The Main Category the "category" (sub-category) belongs under. Blank = category is top-level.'],
            ['old_price', 'Original price shown as a discount (strikethrough). Blank = no discount.'],
            ['sale_starts_at / sale_ends_at', 'Sale window. Format Y-m-d H:i (e.g. 2026-08-01 00:00). Blank = no limit.'],
            ['Accepted featured true values', '1, true, yes, Y'],
            ['Featured default', 'Blank or other values are imported as false.'],
            ['Duplicate SKU default', 'Skipped unless Update existing products is selected during import.'],
            ['Variants format', 'JSON array of {name, sku, retail_price, wholesale_price, stock_status}. Use double quotes. Blank = no variants.'],
            ['Variants example', '[{"name":"256GB","sku":"SKU-001-256","retail_price":19500,"wholesale_price":16200,"stock_status":"in_stock"}]'],
            ['Variants — different prices', 'Every variant has its OWN retail_price / wholesale_price / sku / stock_status. Sizes, colors and connector types with different prices are fully supported (see row 3 of the Products sheet).'],
            ['Variants — two dimensions', 'For connector + color (e.g. "Type C / Black") write one variant per combination, each with its own sku and price. The system also auto-generates combinations from the admin form Variant Presets.'],
            ['Variant attributes (Specifications)', 'Each variant may carry an attributes array of {label, value} pairs — they appear on the product page Specifications tab (e.g. Battery: 300mAh, Water Resistance: IP68). See row 3 of the Products sheet.'],
            ['Store assignment', 'The system always uses the current admin store. Do not add store_id.'],
        ]);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, 'product-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function safeImportFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = Str::slug($name) ?: 'import';

        return $name . '-' . now()->format('YmdHis') . '.' . $extension;
    }

    /**
     * Neutralize spreadsheet formula injection in exported CSV cells:
     * cells starting with =, +, - or @ get a leading apostrophe so Excel /
     * Google Sheets treat them as text instead of formulas.
     */
    private function csvCell(mixed $value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Mirror of SpreadsheetImportReader::normalizeHeader() — used to detect
     * variant-attribute labels that would collide with a fixed export column
     * once the importer normalizes the header (e.g. an attribute called
     * "Brand" would otherwise produce two headers mapping to the same key).
     */
    private function normalizeHeaderKey(string $header): string
    {
        $h = strtolower(trim($header));
        $h = str_replace('(ks)', '', $h);
        $h = preg_replace('/[^a-z0-9]+/', '_', $h) ?? '';

        return trim($h, '_');
    }

    public function destroy(string $store_slug, Product $product, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        foreach ($product->images as $img) {
            if ($img->image_path) {
                Storage::disk('public')->delete($img->image_path);
            }
        }

        $product->delete();

        return back()->with('success', __('messages.product_deleted'));
    }

    public function bulkStock(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'ids'          => ['required', 'array', 'min:1'],
            'ids.*'        => ['integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'stock_status' => ['required', 'in:in_stock,out_of_stock'],
        ]);

        $count = Product::where('store_id', $store->id)
            ->whereIn('id', $validated['ids'])
            ->update(['stock_status' => $validated['stock_status']]);

        return back()->with('success', "Updated stock status for {$count} products.");
    }

    /**
     * Bulk-adjust retail/wholesale prices of selected products by a fixed
     * amount (Ks) or a percentage — useful when a price list changes.
     */
    public function bulkAdjustPrices(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'ids'       => ['required', 'array', 'min:1'],
            'ids.*'     => ['integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
            'apply_to'  => ['required', 'in:retail,wholesale,both'],
            'mode'      => ['required', 'in:amount,percent'],
            'direction' => ['required', 'in:increase,decrease'],
            'value'     => ['required', 'numeric', 'gt:0', 'max:100000000'],
        ]);

        $products = Product::where('store_id', $store->id)
            ->whereIn('id', $validated['ids'])
            ->get();

        $value = (float) $validated['value'];
        $count = 0;

        foreach ($products as $product) {
            $update = [];

            foreach (['retail_price', 'wholesale_price'] as $field) {
                $fieldName = str_replace('_price', '', $field);

                if ($validated['apply_to'] !== 'both' && $validated['apply_to'] !== $fieldName) {
                    continue;
                }

                $current = (float) $product->{$field};
                $sign = $validated['direction'] === 'increase' ? 1 : -1;

                $new = $validated['mode'] === 'percent'
                    ? $current * (1 + $sign * $value / 100)
                    : $current + $sign * $value;

                $update[$field] = max(0, round($new));
            }

            if ($update !== []) {
                $product->update($update);
                $count++;
            }
        }

        $label = $validated['apply_to'] === 'both'
            ? 'Retail & Wholesale'
            : ucfirst($validated['apply_to']);

        return back()->with('success', "Adjusted {$label} prices for {$count} products.");
    }

    public function bulkDelete(Request $request, string $store_slug, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', \Illuminate\Validation\Rule::exists('products', 'id')->where('store_id', $store->id)],
        ]);

        $products = Product::where('store_id', $store->id)
            ->whereIn('id', $validated['ids'])
            ->get();

        $count = 0;
        foreach ($products as $product) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            foreach ($product->images as $img) {
                if ($img->image_path) {
                    Storage::disk('public')->delete($img->image_path);
                }
            }
            $product->delete();
            $count++;
        }

        return back()->with('success', "Deleted {$count} products successfully.");
    }

    /**
     * Toggle the is_featured flag on a product.
     */
    public function toggleFeatured(string $store_slug, Product $product, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        $product->update([
            'is_featured' => !$product->is_featured,
        ]);

        $status = $product->fresh()->is_featured ? 'featured' : 'unfeatured';

        return back()->with('success', "Product {$product->name} marked as {$status}.");
    }

    /**
     * Per-row "Sell Online" toggle (is_ecommerce) — the storefront only shows
     * products with the flag on; POS and admin always see everything.
     */
    public function toggleEcommerce(string $store_slug, Product $product, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        $product->update([
            'is_ecommerce' => ! $product->is_ecommerce,
        ]);

        $status = $product->fresh()->is_ecommerce ? 'online' : 'counter only';

        return back()->with('success', "Product {$product->name} is now {$status}.");
    }

    /**
     * Bulk "Sell Online / Counter only" — updates only the selected products
     * that belong to this store.
     */
    public function bulkSetEcommerce(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
            'is_ecommerce' => ['required', 'boolean'],
        ]);

        $count = Product::where('store_id', $store->id)
            ->whereIn('id', $validated['ids'])
            ->update(['is_ecommerce' => $request->boolean('is_ecommerce')]);

        return back()->with('success', "{$count} products updated.");
    }

    /**
     * Duplicate a product (and its gallery images) so variants can be
     * created quickly. Files are copied so deleting one product never
     * breaks the other's images.
     */
    public function duplicate(string $store_slug, Product $product, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($product->store_id !== $store->id) {
            abort(403, 'Unauthorized product access.');
        }

        $name = $product->name . ' (Copy)';

        $copy = Product::create([
            'store_id'        => $store->id,
            'category_id'     => $product->category_id,
            'brand_id'        => $product->brand_id,
            'sku'             => $this->uniqueCopySku($store, $product->sku),
            'name'            => $name,
            'slug'            => Str::slug($name . '-' . Str::random(5)),
            'description'     => $product->description,
            'retail_price'    => $product->retail_price,
            'old_price'       => $product->old_price,
            'sale_starts_at'  => $product->sale_starts_at,
            'sale_ends_at'    => $product->sale_ends_at,
            'wholesale_price' => $product->wholesale_price,
            'stock_status'    => $product->stock_status,
            'image_path'      => $this->copyImageFile($product->image_path),
            'warranty'        => $product->warranty,
            'return_policy'   => $product->return_policy,
            'is_featured'     => false,
        ]);

        foreach ($product->images as $image) {
            $copy->images()->create([
                'image_path' => $this->copyImageFile($image->image_path),
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ]);
        }

        foreach ($product->variants as $variant) {
            $copy->variants()->create([
                'name' => $variant->name,
                'attributes' => $variant->attributes,
                'sku' => $variant->sku ? $this->uniqueCopyVariantSku($store, $variant->sku) : null,
                'retail_price' => $variant->retail_price,
                'wholesale_price' => $variant->wholesale_price,
                'stock_status' => $variant->stock_status,
                'image_path' => $this->copyImageFile($variant->image_path),
                'is_default' => $variant->is_default,
                'sort_order' => $variant->sort_order,
            ]);
        }

        return back()->with('success', "Product {$product->name} duplicated as {$name}.");
    }

    /**
     * Build a unique SKU for the copy: "<sku>-copy", "-copy-2", ...
     */
    private function uniqueCopySku(Store $store, string $sku): string
    {
        $base = $sku !== '' ? $sku : 'copy';
        $candidate = $base . '-copy';
        $suffix = 2;

        while (Product::where('store_id', $store->id)->where('sku', $candidate)->exists()) {
            $candidate = $base . '-copy-' . $suffix++;
        }

        return $candidate;
    }

    private function uniqueCopyVariantSku(Store $store, string $sku): string
    {
        $base = $sku !== '' ? $sku : 'variant';
        $candidate = $base . '-copy';
        $suffix = 2;

        while (
            Product::where('store_id', $store->id)->where('sku', $candidate)->exists()
            || ProductVariant::where('sku', $candidate)
                ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
                ->exists()
        ) {
            $candidate = $base . '-copy-' . $suffix++;
        }

        return $candidate;
    }

    /**
     * Copy a stored image file to a fresh path so the original and the
     * copy remain independent.
     */
    private function copyImageFile(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = 'products/' . Str::uuid()->toString() . '.' . $extension;

        $disk->copy($path, $newPath);

        return $newPath;
    }
}
