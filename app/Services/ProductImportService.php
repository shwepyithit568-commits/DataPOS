<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportHistory;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImportService
{
    private const REQUIRED_HEADERS = [
        'name',
        'sku',
        'retail_price',
        'wholesale_price',
        'stock_status',
    ];

    private const SUPPORTED_HEADERS = [
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
        'images',
        'featured',
        'variants',
    ];

    private const VALID_STOCK_STATUSES = ['in_stock', 'out_of_stock'];

    private const MAX_GALLERY_IMAGES = 4;

    /**
     * Aliases that let the legacy POS/AppSheet export (the "Products.csv"
     * with Product_ID / Product_Name / Sale_Price / Warranty_Period /
     * Current_Stock columns) upload directly through the same import flow.
     * Keys are the normalized reader headers; values are the canonical
     * importer columns. category / brand / description / wholesale_price /
     * images already normalize to the canonical names.
     */
    private const HEADER_ALIASES = [
        'product_id' => 'sku',
        'product_name' => 'name',
        'sale_price' => 'retail_price',
        'warranty_period' => 'warranty',
        // The export writes "Discount Price (Ks)" — normalize it back to the
        // canonical column. Plain "old_price" still works unchanged.
        'discount_price' => 'old_price',
    ];

    public function __construct(private SpreadsheetImportReader $reader)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(string $filePath, Store $store, string $duplicateStrategy = 'skip'): array
    {
        $spreadsheet = $this->reader->read($filePath, 'Products');
        $normalized = $this->normalizeForImport($spreadsheet['headers'], $spreadsheet['rows']);
        $this->validateHeaders($normalized['headers']);

        $rows = $normalized['rows'];
        $existingSkuSet = $this->existingSkuSet($store);
        $seenSkuSet = [];

        $result = $this->emptyResult();

        foreach ($rows as $row) {
            $result['total']++;
            $validationError = $this->validateRow($row);
            $skuKey = strtolower(trim((string) ($row['sku'] ?? '')));

            if ($validationError !== null) {
                $result['failed']++;
                $result['failed_rows'][] = $validationError;
                continue;
            }

            if (isset($seenSkuSet[$skuKey])) {
                $result['failed']++;
                $result['failed_rows'][] = $this->failure($row, 'sku', 'Duplicate SKU inside uploaded file.');
                continue;
            }

            $seenSkuSet[$skuKey] = true;

            if (isset($existingSkuSet[$skuKey])) {
                if ($duplicateStrategy === 'update') {
                    $result['updatable']++;
                } else {
                    $result['skipped_duplicate']++;
                }
                $this->appendPreviewRow($result, $row, $duplicateStrategy === 'update' ? 'update' : 'skip_duplicate');
                continue;
            }

            $result['creatable']++;
            $this->appendPreviewRow($result, $row, 'create');
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $filePath, Store $store, ?User $user, string $filename, string $duplicateStrategy = 'skip'): array
    {
        $duplicateStrategy = $duplicateStrategy === 'update' ? 'update' : 'skip';

        return DB::transaction(function () use ($filePath, $store, $user, $filename, $duplicateStrategy) {
            $spreadsheet = $this->reader->read($filePath, 'Products');
            $normalized = $this->normalizeForImport($spreadsheet['headers'], $spreadsheet['rows']);
            $this->validateHeaders($normalized['headers']);

            $brandCache = $this->brandCache($store);
            $categoryCache = $this->categoryCache($store);
            $existingSkuSet = $this->existingSkuSet($store);
            $seenSkuSet = [];

            $result = $this->emptyResult();

            foreach ($normalized['rows'] as $row) {
                $result['total']++;
                $validationError = $this->validateRow($row);
                $skuKey = strtolower(trim((string) ($row['sku'] ?? '')));

                if ($validationError !== null) {
                    $result['failed']++;
                    $result['failed_rows'][] = $validationError;
                    continue;
                }

                if (isset($seenSkuSet[$skuKey])) {
                    $result['failed']++;
                    $result['failed_rows'][] = $this->failure($row, 'sku', 'Duplicate SKU inside uploaded file.');
                    continue;
                }

                $seenSkuSet[$skuKey] = true;

                $brandId = $this->resolveBrand($row['brand'] ?? null, $store, $brandCache);
                $categoryId = $this->resolveCategory(
                    $row['category'] ?? null,
                    $row['parent_category'] ?? $row['item_type'] ?? null,
                    $store,
                    $categoryCache
                );
                $payload = $this->productPayload($row, $store, $brandId, $categoryId);

                $existingProduct = Product::where('store_id', $store->id)
                    ->whereRaw('LOWER(sku) = ?', [mb_strtolower($row['sku'])])
                    ->first();

                if ($existingProduct) {
                    if ($duplicateStrategy !== 'update') {
                        $result['skipped_duplicate']++;
                        continue;
                    }

                    $existingProduct->update($payload);
                    $this->syncImportedVariants($existingProduct, $row);
                    $result['updated']++;
                    continue;
                }

                $product = Product::create($payload + [
                    'store_id' => $store->id,
                    'sku' => $row['sku'],
                    'slug' => Str::slug($row['name'] . '-' . Str::random(5)),
                    'is_featured' => $this->truthyFeatured($row['featured'] ?? null),
                ]);
                $this->syncImportedVariants($product, $row);
                $this->attachImportedImages($product, $row);

                $existingSkuSet[$skuKey] = true;
                $result['imported']++;
            }

            $result['success'] = $result['imported'] + $result['updated'];
            $errorFilePath = $this->writeErrorFile($store, 'products', $result['failed_rows']);

            ImportHistory::create([
                'store_id' => $store->id,
                'user_id' => $user?->id,
                'type' => 'products',
                'filename' => $filename,
                'total_rows' => $result['total'],
                'success_rows' => $result['success'],
                'failed_rows' => $result['failed'],
                'error_file_path' => $errorFilePath,
            ]);

            return $result;
        });
    }

    public function importFromCsv(string $filePath, Store $store): array
    {
        return $this->import($filePath, $store, null, basename($filePath), 'skip');
    }

    /**
     * @param array<int, string> $headers
     */
    private function validateHeaders(array $headers): void
    {
        $missingHeaders = array_diff(self::REQUIRED_HEADERS, $headers);

        // POS/AppSheet exports carry Current_Stock (a quantity) instead of
        // stock_status; the row normalizer derives the status from it.
        if (in_array('stock_status', $missingHeaders, true) && in_array('current_stock', $headers, true)) {
            unset($missingHeaders[array_search('stock_status', $missingHeaders, true)]);
        }

        if (!empty($missingHeaders)) {
            throw new \InvalidArgumentException('Missing required columns: ' . implode(', ', $missingHeaders));
        }
    }

    /**
     * Map legacy POS/AppSheet column names onto the canonical product
     * columns and derive fields the source file does not provide:
     *   - stock_status is derived from current_stock (a quantity) when the
     *     source has no stock_status column: positive quantity → in_stock,
     *     zero/empty/negative → out_of_stock.
     *   - A non-positive retail price always forces out_of_stock so a
     *     "0 Ks" product can never be ordered for free.
     *
     * @param array<int, string> $headers
     * @param array<int, array<string, ?string>> $rows
     * @return array{headers: array<int, string>, rows: array<int, array<string, ?string>>}
     */
    private function normalizeForImport(array $headers, array $rows): array
    {
        $headers = array_map(fn(string $header) => self::HEADER_ALIASES[$header] ?? $header, $headers);

        $rows = array_map(function (array $row) {
            foreach (self::HEADER_ALIASES as $from => $to) {
                if (array_key_exists($from, $row) && !array_key_exists($to, $row)) {
                    $row[$to] = $row[$from];
                    unset($row[$from]);
                }
            }

            // POS exports carry an empty wholesale cell for products with no
            // wholesale tier; store 0 and let the storefront fall back to the
            // retail price instead of rejecting the whole row.
            if (array_key_exists('wholesale_price', $row) && trim((string) $row['wholesale_price']) === '') {
                $row['wholesale_price'] = '0';
            }

            if (!array_key_exists('stock_status', $row) && array_key_exists('current_stock', $row)) {
                $stock = $this->sanitizeNumber($row['current_stock'] ?? '');
                $row['stock_status'] = (is_numeric($stock) && (float) $stock > 0) ? 'in_stock' : 'out_of_stock';
            }

            $retail = $this->sanitizeNumber($row['retail_price'] ?? '');
            if (is_numeric($retail) && (float) $retail <= 0) {
                $row['stock_status'] = 'out_of_stock';
            }

            return $row;
        }, $rows);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(): array
    {
        return [
            'total' => 0,
            'creatable' => 0,
            'updatable' => 0,
            'imported' => 0,
            'updated' => 0,
            'success' => 0,
            'skipped_duplicate' => 0,
            'failed' => 0,
            'failed_rows' => [],
            'preview_rows' => [],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function validateRow(array $row): ?array
    {
        foreach (self::REQUIRED_HEADERS as $header) {
            if (trim((string) ($row[$header] ?? '')) === '') {
                return $this->failure($row, $header, "{$header} is required.");
            }
        }

        if (strlen((string) $row['name']) > 255) {
            return $this->failure($row, 'name', 'Product name is too long.');
        }

        if (strlen((string) $row['sku']) > 100) {
            return $this->failure($row, 'sku', 'SKU is too long.');
        }

        $retailPrice = $this->sanitizeNumber($row['retail_price']);
        if (!is_numeric($retailPrice) || (float) $retailPrice < 0) {
            return $this->failure($row, 'retail_price', 'Invalid retail_price: ' . $row['retail_price']);
        }

        $wholesalePrice = $this->sanitizeNumber($row['wholesale_price']);
        if (!is_numeric($wholesalePrice) || (float) $wholesalePrice < 0) {
            return $this->failure($row, 'wholesale_price', 'Invalid wholesale_price: ' . $row['wholesale_price']);
        }

        if (trim((string) ($row['old_price'] ?? '')) !== '') {
            $oldPrice = $this->sanitizeNumber($row['old_price']);
            if (!is_numeric($oldPrice) || (float) $oldPrice < 0) {
                return $this->failure($row, 'old_price', 'Invalid old_price: ' . $row['old_price']);
            }
        }

        $saleStarts = trim((string) ($row['sale_starts_at'] ?? ''));
        $saleEnds = trim((string) ($row['sale_ends_at'] ?? ''));
        foreach (['sale_starts_at' => $saleStarts, 'sale_ends_at' => $saleEnds] as $field => $value) {
            if ($value !== '' && strtotime($value) === false) {
                return $this->failure($row, $field, "Invalid {$field} date format.");
            }
        }
        if ($saleStarts !== '' && $saleEnds !== '' && strtotime($saleEnds) < strtotime($saleStarts)) {
            return $this->failure($row, 'sale_ends_at', 'Sale end must be on or after sale start.');
        }

        $stockStatus = strtolower(trim((string) $row['stock_status']));
        if (!in_array($stockStatus, self::VALID_STOCK_STATUSES, true)) {
            return $this->failure($row, 'stock_status', 'Invalid stock_status: ' . $row['stock_status']);
        }

        if (!empty($row['image_url']) && !filter_var($row['image_url'], FILTER_VALIDATE_URL)) {
            return $this->failure($row, 'image_url', 'Invalid image_url.');
        }

        if (trim((string) ($row['variants'] ?? '')) !== '') {
            $decoded = json_decode($row['variants'], true);
            if (!is_array($decoded)) {
                return $this->failure($row, 'variants', 'Variants must be a valid JSON array.');
            }
            foreach ($decoded as $variant) {
                if (!is_array($variant) || trim((string) ($variant['name'] ?? '')) === '') {
                    return $this->failure($row, 'variants', 'Each variant needs a name.');
                }
                if (isset($variant['retail_price'])) {
                    $variantRetail = $this->sanitizeNumber($variant['retail_price']);
                    if (!is_numeric($variantRetail) || (float) $variantRetail < 0) {
                        return $this->failure($row, 'variants', 'Invalid variant retail_price: ' . $variant['retail_price']);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, true>
     */
    private function existingSkuSet(Store $store): array
    {
        return Product::where('store_id', $store->id)
            ->pluck('sku')
            ->map(fn($sku) => strtolower(trim((string) $sku)))
            ->flip()
            ->map(fn() => true)
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function brandCache(Store $store): array
    {
        return Brand::where('store_id', $store->id)
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Brand $brand) => [$this->nameKey($brand->name) => $brand->id])
            ->toArray();
    }

    /**
     * @return array<string, int>
     */
    private function categoryCache(Store $store): array
    {
        return Category::where('store_id', $store->id)
            ->get(['id', 'name'])
            ->mapWithKeys(fn(Category $category) => [$this->nameKey($category->name) => $category->id])
            ->toArray();
    }

    /**
     * @param array<string, int> $cache
     */
    private function resolveBrand(?string $name, Store $store, array &$cache): ?int
    {
        $normalizedName = trim((string) $name);
        if ($normalizedName === '') {
            return null;
        }

        $key = $this->nameKey($normalizedName);
        if (!isset($cache[$key])) {
            $brand = Brand::create([
                'store_id' => $store->id,
                'name' => $normalizedName,
                'slug' => $this->uniqueSlug(Brand::class, $store, $normalizedName),
            ]);
            $cache[$key] = $brand->id;
        }

        return $cache[$key];
    }

    /**
     * Resolve (creating when needed) a sub-category under an optional parent.
     * The legacy POS export's "Item_Type" column becomes the parent category
     * (e.g. Spare Part / Accessories / Electronic / CCTV) and "Category" the
     * sub-category. Re-imports move an existing sub-category under its new
     * parent instead of silently leaving a stale tree.
     *
     * @param array<string, int> $cache
     */
    private function resolveCategory(?string $name, ?string $parentName, Store $store, array &$cache): ?int
    {
        $normalizedName = trim((string) $name);
        if ($normalizedName === '') {
            return null;
        }

        $parentId = null;
        $normalizedParent = trim((string) $parentName);
        if ($normalizedParent !== '') {
            $parentKey = 'parent:' . $this->nameKey($normalizedParent);
            if (!isset($cache[$parentKey])) {
                $parent = Category::firstOrCreate(
                    ['store_id' => $store->id, 'name' => $normalizedParent],
                    ['slug' => $this->uniqueSlug(Category::class, $store, $normalizedParent)]
                );
                $cache[$parentKey] = $parent->id;
            }
            $parentId = $cache[$parentKey];
        }

        $key = $this->nameKey($normalizedName);
        if (!isset($cache[$key])) {
            $category = Category::create([
                'store_id' => $store->id,
                'name' => $normalizedName,
                'slug' => $this->uniqueSlug(Category::class, $store, $normalizedName),
                'parent_id' => $parentId,
            ]);
            $cache[$key] = $category->id;
        } elseif ($parentId !== null) {
            Category::where('id', $cache[$key])->where('parent_id', '!=', $parentId)->update(['parent_id' => $parentId]);
        }

        return $cache[$key];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function productPayload(array $row, Store $store, ?int $brandId, ?int $categoryId): array
    {
        return [
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'meta_description' => $row['meta_description'] ?? null,
            'retail_price' => (float) $this->sanitizeNumber($row['retail_price']),
            // The column is NOT NULL; a zero value means "no wholesale tier"
            // and the storefront falls back to the retail price.
            'wholesale_price' => (float) $this->sanitizeNumber($row['wholesale_price']),
            'old_price' => trim((string) ($row['old_price'] ?? '')) !== ''
                ? (float) $this->sanitizeNumber($row['old_price'])
                : null,
            'sale_starts_at' => $this->parseDate($row['sale_starts_at'] ?? null),
            'sale_ends_at' => $this->parseDate($row['sale_ends_at'] ?? null),
            'stock_status' => strtolower(trim((string) $row['stock_status'])),
            'warranty' => $row['warranty'] ?? null,
            'return_policy' => $row['return_policy'] ?? null,
            'image_path' => null,
            'is_featured' => $this->truthyFeatured($row['featured'] ?? null),
        ];
    }

    /**
     * Parse an import date cell into a DB datetime, tolerating the export's
     * "Y-m-d H:i" format and the form's "Y-m-d\TH:i" format.
     */
    private function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function truthyFeatured(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    /**
     * Decode the optional "variants" JSON column into variant rows.
     *
     * @return array<int, array<string, mixed>>
     */
    private function variantsFromRow(array $row): array
    {
        $raw = trim((string) ($row['variants'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $variants = [];
        foreach (array_values($decoded) as $i => $variant) {
            if (!is_array($variant) || trim((string) ($variant['name'] ?? '')) === '') {
                continue;
            }

            $variants[] = [
                'name' => trim((string) $variant['name']),
                'attributes' => $variant['attributes'] ?? null,
                'sku' => trim((string) ($variant['sku'] ?? '')) !== '' ? trim((string) $variant['sku']) : null,
                'retail_price' => (float) $this->sanitizeNumber($variant['retail_price'] ?? 0),
                'wholesale_price' => isset($variant['wholesale_price']) && trim((string) $variant['wholesale_price']) !== ''
                    ? (float) $this->sanitizeNumber($variant['wholesale_price'])
                    : null,
                'stock_status' => in_array($variant['stock_status'] ?? 'in_stock', self::VALID_STOCK_STATUSES, true)
                    ? $variant['stock_status']
                    : 'in_stock',
                'is_default' => (bool) ($variant['is_default'] ?? ($i === 0)),
                'sort_order' => $i,
            ];
        }

        return $variants;
    }

    /**
     * Replace a product's variants with the ones from the import row.
     */
    private function syncImportedVariants(Product $product, array $row): void
    {
        $variants = $this->variantsFromRow($row);
        if ($variants === []) {
            return;
        }

        $product->variants()->delete();
        foreach ($variants as $data) {
            $product->variants()->create($data);
        }
    }

    /**
     * Attach product images from the import row. Two sources are supported,
     * in order of preference:
     *   1. "images"    — storage-relative paths separated by "; " (the export's
     *                    "Images" column). Existing files are copied to fresh
     *                    paths so the import never reuses a shared file.
     *   2. "image_url" — a remote image URL, downloaded best-effort.
     * Only runs when the product is created; updates never touch images so a
     * price/name re-import cannot wipe an existing gallery.
     */
    private function attachImportedImages(Product $product, array $row): void
    {
        $paths = $this->importedImagePaths($row);

        if ($paths === []) {
            return;
        }

        $first = array_shift($paths);
        if ($first !== null && !$product->image_path) {
            $product->update(['image_path' => $first]);
        }

        foreach (array_slice($paths, 0, self::MAX_GALLERY_IMAGES) as $path) {
            $product->images()->create([
                'image_path' => $path,
                'is_primary' => false,
                'sort_order' => $product->images()->count() + 1,
            ]);
        }
    }

    /**
     * @return array<int, string> storage paths (fresh copies) to attach
     */
    private function importedImagePaths(array $row): array
    {
        $paths = [];

        $local = trim((string) ($row['images'] ?? ''));
        if ($local !== '') {
            $disk = Storage::disk('public');
            foreach (preg_split('/[;,]/', $local) ?: [] as $rawPath) {
                $path = trim($rawPath);
                if ($path === '' || !$disk->exists($path)) {
                    continue;
                }
                $copied = $this->copyImageToFreshPath($path);
                if ($copied !== null) {
                    $paths[] = $copied;
                }
            }
        }

        if ($paths === [] && !empty($row['image_url']) && filter_var($row['image_url'], FILTER_VALIDATE_URL)) {
            $downloaded = $this->downloadImage($row['image_url']);
            if ($downloaded !== null) {
                $paths[] = $downloaded;
            }
        }

        return $paths;
    }

    private function copyImageToFreshPath(string $path): ?string
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = 'products/' . Str::uuid()->toString() . '.' . $extension;
        $disk->copy($path, $newPath);

        return $newPath;
    }

    /**
     * Download a remote image best-effort. Failures (timeout, non-image
     * response) return null and the row still imports without an image.
     */
    private function downloadImage(string $url): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
            if (!$response->ok()) {
                return null;
            }

            $mime = $response->header('Content-Type');
            if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
                return null;
            }

            $extension = match (true) {
                str_contains($mime, 'png') => 'png',
                str_contains($mime, 'webp') => 'webp',
                str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
                default => 'jpg',
            };

            $path = 'products/' . Str::uuid()->toString() . '.' . $extension;
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    private function uniqueSlug(string $modelClass, Store $store, string $name): string
    {
        $base = Str::slug($name) ?: 'imported';
        $slug = $base;
        $counter = 2;

        while ($modelClass::where('store_id', $store->id)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function nameKey(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * Normalize a price cell for numeric parsing. The product export writes
     * prices with thousands separators ("5,578,000") and admins may type a
     * "Ks" prefix, so everything except digits, the decimal point and the
     * minus sign is stripped before validation.
     */
    private function sanitizeNumber(mixed $value): string
    {
        return trim(preg_replace('/[^0-9.\-]/', '', (string) $value));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function failure(array $row, string $field, string $message): array
    {
        $originalData = $row;
        unset($originalData['_row']);

        return [
            'row' => $row['_row'],
            'row_number' => $row['_row'],
            'sku' => $row['sku'] ?? null,
            'field' => $field,
            'reason' => $message,
            'error_message' => $message,
            'original_data' => json_encode($originalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $row
     */
    private function appendPreviewRow(array &$result, array $row, string $action): void
    {
        if (count($result['preview_rows']) >= 20) {
            return;
        }

        $result['preview_rows'][] = [
            'row' => $row['_row'],
            'sku' => $row['sku'],
            'name' => $row['name'],
            'brand' => $row['brand'] ?? null,
            'category' => $row['category'] ?? null,
            'retail_price' => $row['retail_price'] ?? null,
            'stock_status' => $row['stock_status'] ?? null,
            'action' => $action,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $failedRows
     */
    private function writeErrorFile(Store $store, string $type, array $failedRows): ?string
    {
        if (empty($failedRows)) {
            return null;
        }

        $path = 'import-errors/' . $store->id . '/' . $type . '-' . now()->format('YmdHis') . '-' . Str::random(8) . '.csv';
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['row_number', 'field', 'error_message', 'original_data']);

        foreach ($failedRows as $failedRow) {
            fputcsv($handle, [
                $failedRow['row_number'] ?? $failedRow['row'] ?? '',
                $failedRow['field'] ?? '',
                $failedRow['error_message'] ?? $failedRow['reason'] ?? '',
                $failedRow['original_data'] ?? '',
            ]);
        }

        rewind($handle);
        Storage::disk('local')->put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }
}
