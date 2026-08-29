<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GlassFinderItem;
use App\Models\HomeBanner;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\StorePaymentMethod;
use App\POS\Models\Branch;
use App\POS\Models\InventoryBalance;
use App\POS\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mobile Catalog SQL Seeder — Imports rich real-world catalog data from SQL dump
 * ===============================================================================
 *
 * Populates real mobile electronics, parts, accessories, brands, categories,
 * glass finder items, banners, and POS inventory into the target store (datapos-mobile).
 *
 * Usage:
 *   php artisan db:seed --class=MobileCatalogSqlSeeder
 */
class MobileCatalogSqlSeeder extends Seeder
{
    public function run(): void
    {
        $dumpPath = base_path('manual_2026-08-30_015910.mysql.sql');
        if (! file_exists($dumpPath)) {
            $this->command?->error("Dump file not found at: {$dumpPath}");
            return;
        }

        $store = Store::where('slug', 'datapos-mobile')->first()
            ?? Store::first();

        if (! $store) {
            $this->command?->error('No store found. Create a store first.');
            return;
        }

        $this->command?->info("🚀 Cleaning old sample data and importing SQL dump for store: [{$store->slug}] {$store->name} (ID: {$store->id})...");

        // Purge previous dummy/sample data for this store to keep it 100% clean
        $this->command?->info('  🧹 Purging old sample data for this store...');
        $productIds = Product::where('store_id', $store->id)->pluck('id')->toArray();

        // 1. Clear inventory balances and movements
        InventoryBalance::where('store_id', $store->id)->delete();
        DB::table('inventory_movements')->where('store_id', $store->id)->delete();

        // 2. Clear previous order items & orders for this store if any
        $orderIds = DB::table('orders')->where('store_id', $store->id)->pluck('id')->toArray();
        if (! empty($orderIds)) {
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->where('store_id', $store->id)->delete();
        }

        // 3. Clear product images & variants
        if (! empty($productIds)) {
            ProductImage::whereIn('product_id', $productIds)->delete();
            ProductVariant::whereIn('product_id', $productIds)->delete();
        }

        // 4. Clear products
        Product::where('store_id', $store->id)->delete();

        // 5. Clear categories (break hierarchy first to avoid self-FK constraint)
        Category::where('store_id', $store->id)->update(['parent_id' => null]);
        Category::where('store_id', $store->id)->delete();

        // 6. Clear brands, glass finder, banners, posts, payment methods
        Brand::where('store_id', $store->id)->delete();
        GlassFinderItem::where('store_id', $store->id)->delete();
        HomeBanner::where('store_id', $store->id)->delete();
        Post::where('store_id', $store->id)->delete();
        StorePaymentMethod::where('store_id', $store->id)->delete();

        $this->command?->info('  ✓ Old sample data cleared.');

        $sqlContent = file_get_contents($dumpPath);

        // Ensure default Branch and Warehouse exist for POS inventory
        $branch = Branch::firstOrCreate(
            ['store_id' => $store->id, 'is_default' => true],
            ['name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]
        );

        $warehouse = Warehouse::firstOrCreate(
            ['store_id' => $store->id, 'is_default' => true],
            ['branch_id' => $branch->id, 'name' => 'Main Warehouse', 'code' => 'WH-MAIN', 'is_active' => true]
        );

        // 1. Brands
        $this->command?->info('  📦 Importing Brands...');
        $brandRows = $this->extractInsertRows($sqlContent, 'brands');
        $brandMap = []; // old_id => new_id

        foreach ($brandRows as $b) {
            $slug = ! empty($b['slug']) ? $b['slug'] : Str::slug($b['name']);
            $brand = Brand::updateOrCreate(
                ['store_id' => $store->id, 'slug' => $slug],
                [
                    'name'      => $b['name'],
                    'logo_path' => $b['logo_path'] ?? null,
                ]
            );
            if (! empty($b['id'])) {
                $brandMap[$b['id']] = $brand->id;
            }
        }
        $this->command?->info('  ✓ Brands imported: ' . count($brandMap));

        // 2. Categories (Two-pass for parent_id hierarchy)
        $this->command?->info('  📁 Importing Categories...');
        $catRows = $this->extractInsertRows($sqlContent, 'categories');
        $catMap = []; // old_id => new_id

        // Pass 1: Create all categories without parent
        foreach ($catRows as $c) {
            $slug = ! empty($c['slug']) ? $c['slug'] : Str::slug($c['name']);
            $cat = Category::updateOrCreate(
                ['store_id' => $store->id, 'slug' => $slug],
                [
                    'name'        => $c['name'],
                    'description' => $c['description'] ?? null,
                    'image_path'  => $c['image_path'] ?? null,
                    'icon'        => $c['icon'] ?? null,
                ]
            );
            if (! empty($c['id'])) {
                $catMap[$c['id']] = $cat->id;
            }
        }

        // Pass 2: Link parent_id
        foreach ($catRows as $c) {
            if (! empty($c['parent_id']) && isset($catMap[$c['id']]) && isset($catMap[$c['parent_id']])) {
                Category::where('id', $catMap[$c['id']])->update([
                    'parent_id' => $catMap[$c['parent_id']],
                ]);
            }
        }
        $this->command?->info('  ✓ Categories imported: ' . count($catMap));

        // 3. Products
        $this->command?->info('  📱 Importing Products...');
        $prodRows = $this->extractInsertRows($sqlContent, 'products');
        $prodMap = []; // old_id => new_id
        $countProducts = 0;

        foreach ($prodRows as $p) {
            $sku = trim($p['sku'] ?? '');
            if (empty($sku)) {
                continue;
            }

            $slug = ! empty($p['slug']) ? $p['slug'] : Str::slug($p['name'] . '-' . $sku);
            $catId = ! empty($p['category_id']) ? ($catMap[$p['category_id']] ?? null) : null;
            $brandId = ! empty($p['brand_id']) ? ($brandMap[$p['brand_id']] ?? null) : null;

            $retailPrice = (float) ($p['retail_price'] ?? 0);
            $wholesalePrice = (float) ($p['wholesale_price'] ?? 0);
            $oldPrice = ! empty($p['old_price']) ? (float) $p['old_price'] : null;
            $purchaseCost = $wholesalePrice > 0 ? $wholesalePrice : ($retailPrice > 0 ? round($retailPrice * 0.7) : 0);

            $product = Product::updateOrCreate(
                ['store_id' => $store->id, 'sku' => $sku],
                [
                    'category_id'      => $catId,
                    'brand_id'         => $brandId,
                    'name'             => $p['name'],
                    'slug'             => $slug,
                    'description'      => $p['description'] ?? null,
                    'meta_description' => $p['meta_description'] ?? null,
                    'retail_price'     => $retailPrice,
                    'wholesale_price'  => $wholesalePrice,
                    'old_price'        => $oldPrice,
                    'purchase_cost'    => $purchaseCost,
                    'stock_status'     => 'in_stock',
                    'image_path'       => $p['image_path'] ?? null,
                    'warranty'         => $p['warranty'] ?? null,
                    'return_policy'    => $p['return_policy'] ?? null,
                    'is_featured'      => ! empty($p['is_featured']) ? 1 : 0,
                    'is_ecommerce'     => 1,
                    'product_type'     => 'standard',
                    'barcode'          => $sku,
                    'reorder_level'    => 5,
                    'created_at'       => $p['created_at'] ?? now(),
                    'updated_at'       => $p['updated_at'] ?? now(),
                ]
            );

            if (! empty($p['id'])) {
                $prodMap[$p['id']] = $product->id;
            }

            // Sync default inventory balance (exactly 10 units) for POS counter
            InventoryBalance::updateOrCreate(
                [
                    'store_id'           => $store->id,
                    'warehouse_id'       => $warehouse->id,
                    'product_id'         => $product->id,
                    'product_variant_id' => 0,
                ],
                [
                    'quantity_on_hand'   => 10,
                    'unit_cost_avg'      => $purchaseCost,
                ]
            );

            // Record initial opening stock in inventory_movements ledger
            DB::table('inventory_movements')->updateOrInsert(
                [
                    'store_id'              => $store->id,
                    'client_transaction_id' => "init-stock-prod-{$product->id}",
                ],
                [
                    'branch_id'             => $branch->id,
                    'warehouse_id'          => $warehouse->id,
                    'product_id'            => $product->id,
                    'product_variant_id'    => 0,
                    'movement_type'         => 'opening_balance',
                    'quantity_delta'        => 10,
                    'unit_cost'             => $purchaseCost,
                    'source_type'           => 'opening_stock',
                    'source_id'             => $product->id,
                    'occurred_at'           => now(),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]
            );

            $countProducts++;
        }
        $this->command?->info("  ✓ Products imported: {$countProducts}");

        // 4. Product Variants
        $this->command?->info('  🎨 Importing Product Variants...');
        $varRows = $this->extractInsertRows($sqlContent, 'product_variants');
        $countVariants = 0;

        foreach ($varRows as $v) {
            $oldProdId = $v['product_id'] ?? null;
            $newProdId = $prodMap[$oldProdId] ?? null;

            if (! $newProdId) {
                continue;
            }

            $sku = trim($v['sku'] ?? '');
            if (empty($sku)) {
                $sku = 'VAR-' . ($v['id'] ?? uniqid());
            }

            $attrs = $v['attributes'] ?? null;
            if (is_string($attrs)) {
                $decoded = json_decode($attrs, true);
                $attrs = is_array($decoded) ? $decoded : null;
            }

            $variant = ProductVariant::updateOrCreate(
                ['product_id' => $newProdId, 'sku' => $sku],
                [
                    'name'             => $v['name'],
                    'retail_price'     => (float) ($v['retail_price'] ?? 0),
                    'wholesale_price'  => (float) ($v['wholesale_price'] ?? 0),
                    'stock_status'     => 'in_stock',
                    'image_path'       => $v['image_path'] ?? null,
                    'is_default'       => ! empty($v['is_default']) ? 1 : 0,
                    'sort_order'       => (int) ($v['sort_order'] ?? 0),
                    'attributes'       => $attrs,
                    'quantity_on_hand' => 10,
                ]
            );

            // Sync variant inventory balance (exactly 10 units)
            InventoryBalance::updateOrCreate(
                [
                    'store_id'           => $store->id,
                    'warehouse_id'       => $warehouse->id,
                    'product_id'         => $newProdId,
                    'product_variant_id' => $variant->id,
                ],
                [
                    'quantity_on_hand'   => 10,
                    'unit_cost_avg'      => (float) ($v['wholesale_price'] ?? 0),
                ]
            );

            // Record variant initial opening stock in inventory_movements ledger
            DB::table('inventory_movements')->updateOrInsert(
                [
                    'store_id'              => $store->id,
                    'client_transaction_id' => "init-stock-var-{$variant->id}",
                ],
                [
                    'branch_id'             => $branch->id,
                    'warehouse_id'          => $warehouse->id,
                    'product_id'            => $newProdId,
                    'product_variant_id'    => $variant->id,
                    'movement_type'         => 'opening_balance',
                    'quantity_delta'        => 10,
                    'unit_cost'             => (float) ($v['wholesale_price'] ?? 0),
                    'source_type'           => 'opening_stock',
                    'source_id'             => $variant->id,
                    'occurred_at'           => now(),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]
            );

            $countVariants++;
        }
        $this->command?->info("  ✓ Product Variants imported: {$countVariants}");

        // 5. Product Images (Attach 4 high-quality mobile placeholder photos to each product)
        $this->command?->info('  🖼️ Generating & Attaching 4 Product Photos to every item...');
        $this->ensurePlaceholderSvgsExist();

        $placeholders = [
            'products/placeholder-mobile-1.svg', // Smartphone / Device
            'products/placeholder-mobile-2.svg', // Charger & Cable
            'products/placeholder-mobile-3.svg', // Audio & Earphones
            'products/placeholder-mobile-4.svg', // Glass & Protection
        ];

        $galleryInserts = [];
        $nowStr = now()->toDateTimeString();

        foreach ($prodMap as $oldId => $newProdId) {
            $product = Product::find($newProdId);
            if (! $product) {
                continue;
            }

            $catSlug = strtolower($product->category?->slug ?? '');

            if (str_contains($catSlug, 'audio') || str_contains($catSlug, 'earphone') || str_contains($catSlug, 'speaker') || str_contains($catSlug, 'microphone')) {
                $ordered = [$placeholders[2], $placeholders[0], $placeholders[1], $placeholders[3]];
            } elseif (str_contains($catSlug, 'charger') || str_contains($catSlug, 'cable') || str_contains($catSlug, 'power')) {
                $ordered = [$placeholders[1], $placeholders[0], $placeholders[2], $placeholders[3]];
            } elseif (str_contains($catSlug, 'glass') || str_contains($catSlug, 'screen') || str_contains($catSlug, 'cover') || str_contains($catSlug, 'back') || str_contains($catSlug, 'case')) {
                $ordered = [$placeholders[3], $placeholders[0], $placeholders[1], $placeholders[2]];
            } else {
                $ordered = [$placeholders[0], $placeholders[1], $placeholders[2], $placeholders[3]];
            }

            $product->update(['image_path' => $ordered[0]]);

            foreach ($ordered as $sortIdx => $imgPath) {
                $galleryInserts[] = [
                    'product_id' => $product->id,
                    'image_path' => $imgPath,
                    'is_primary' => $sortIdx === 0 ? 1 : 0,
                    'sort_order' => $sortIdx + 1,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
        }

        foreach (array_chunk($galleryInserts, 500) as $chunk) {
            DB::table('product_images')->insert($chunk);
        }
        $this->command?->info('  ✓ 4 Product Photos attached to all ' . count($prodMap) . ' items (' . count($galleryInserts) . ' total gallery images).');

        // 6. Glass Finder Items
        $this->command?->info('  🔍 Importing Glass Finder Items...');
        $glassRows = $this->extractInsertRows($sqlContent, 'glass_finder_items');
        $countGlass = 0;

        foreach ($glassRows as $g) {
            if (empty($g['phone_model']) || empty($g['normalized_glass_code'])) {
                continue;
            }

            GlassFinderItem::updateOrCreate(
                [
                    'store_id'              => $store->id,
                    'phone_model'           => $g['phone_model'],
                    'normalized_glass_code' => $g['normalized_glass_code'],
                ],
                [
                    'brand'                 => $g['brand'] ?? 'Universal',
                    'glass_code'            => $g['glass_code'] ?? $g['normalized_glass_code'],
                    'stock_status'          => in_array($g['stock_status'] ?? '', ['in_stock', 'out_of_stock']) ? $g['stock_status'] : 'in_stock',
                ]
            );
            $countGlass++;
        }
        $this->command?->info("  ✓ Glass Finder Items imported: {$countGlass}");

        // 7. Home Banners (High-quality modern SVG banners)
        $this->command?->info('  🚩 Configuring 8 High-Quality Modern Banners...');
        $bannersData = [
            [
                'page'        => 'home',
                'title'       => 'Mobile Phones & Accessories အသစ်များ',
                'description' => 'Original Brand အာမခံအပြည့်အစုံဖြင့် လက်လီ/လက်ကား အထူးဈေးနှုန်းများ',
                'image_path'  => 'banners/banner-mobile-accessories.svg',
                'link_url'    => '/store/' . $store->slug . '/catalog',
                'sort_order'  => 1,
                'is_active'   => 1,
            ],
            [
                'page'        => 'home',
                'title'       => 'CCTV & Security Systems',
                'description' => 'Dahua, Hikvision, Imou Smart Wi-Fi Cameras & Full Kits',
                'image_path'  => 'banners/banner-cctv-security.svg',
                'link_url'    => '/store/' . $store->slug . '/catalog?category=cctv-network',
                'sort_order'  => 2,
                'is_active'   => 1,
            ],
            [
                'page'        => 'home',
                'title'       => 'Computer, Laptop & Tech Gear',
                'description' => 'High-speed Accessories, Wireless Mouse, Storage & Gadgets',
                'image_path'  => 'banners/banner-computer-laptop.svg',
                'link_url'    => '/store/' . $store->slug . '/catalog?category=electronics-gadgets',
                'sort_order'  => 3,
                'is_active'   => 1,
            ],
            [
                'page'        => 'home',
                'title'       => 'Power Banks & Audio Gear',
                'description' => 'Hoco, Remax, Baivati 100W Fast Charging & Bass Sound',
                'image_path'  => 'banners/banner-power-audio.svg',
                'link_url'    => '/store/' . $store->slug . '/catalog?category=audio-earphones',
                'sort_order'  => 4,
                'is_active'   => 1,
            ],
            [
                'page'        => 'home',
                'title'       => 'One-Stop Mobile & Tech Hub',
                'description' => 'အွန်လိုင်းမှ အလွယ်တကူ မှာယူနိုင်ပြီး တစ်နိုင်ငံလုံး ပို့ဆောင်ပေးပါသည်',
                'image_path'  => 'banners/banner-one-stop-tech.svg',
                'link_url'    => '/store/' . $store->slug . '/catalog',
                'sort_order'  => 5,
                'is_active'   => 1,
            ],
            [
                'page'        => 'glass_finder',
                'title'       => 'ဖုန်းမော်ဒယ်အလိုက် Glass အမြန်ရှာရန်',
                'description' => 'မော်ဒယ် ၆၀၀ ကျော်အတွက် 9D, 11D, Matte, Privacy Glass များ',
                'image_path'  => 'banners/banner-glass-finder.svg',
                'link_url'    => '/store/' . $store->slug . '/glass-finder',
                'sort_order'  => 1,
                'is_active'   => 1,
            ],
            [
                'page'        => 'glass_finder',
                'title'       => 'တိကျသော Glass တပ်ဆင်ရေးအတွက်',
                'description' => 'လေခိုခြင်းမရှိ၊ ထောင့်စွန်းမလွတ်ဘဲ အံဝင်ခွင်ကျ အလွယ်တကူ တပ်ဆင်နိုင်ပါသည်',
                'image_path'  => 'banners/banner-glass-install.svg',
                'link_url'    => '/store/' . $store->slug . '/glass-finder',
                'sort_order'  => 2,
                'is_active'   => 1,
            ],
            [
                'page'        => 'glass_finder',
                'title'       => 'Glass Stock စုံစုံလင်လင်',
                'description' => 'ဖုန်းဆိုင်များ၊ ပြုပြင်ရေးသမားများအတွက် လက်ကားအထူးဈေးဖြင့် ရောင်းချပေးပါသည်',
                'image_path'  => 'banners/banner-glass-wholesale.svg',
                'link_url'    => '/store/' . $store->slug . '/glass-finder',
                'sort_order'  => 3,
                'is_active'   => 1,
            ],
        ];

        foreach ($bannersData as $bData) {
            HomeBanner::updateOrCreate(
                ['store_id' => $store->id, 'image_path' => $bData['image_path']],
                $bData
            );
        }
        $this->command?->info('  ✓ 8 High-Quality Modern Banners configured.');

        // 8. Store Payment Methods
        $this->command?->info('  💳 Importing Payment Methods...');
        $paymentRows = $this->extractInsertRows($sqlContent, 'store_payment_methods');
        $countPayments = 0;

        foreach ($paymentRows as $pm) {
            $name = $pm['name'] ?? ($pm['payment_type'] ?? 'Payment');
            $code = Str::slug($pm['code'] ?? ($pm['payment_type'] ?? $name));

            StorePaymentMethod::updateOrCreate(
                ['store_id' => $store->id, 'code' => $code],
                [
                    'name'                 => $name,
                    'type'                 => $pm['type'] ?? ($pm['payment_type'] ?? 'mobile_banking'),
                    'account_name'         => $pm['account_name'] ?? null,
                    'account_number'       => $pm['account_number'] ?? null,
                    'is_active'            => ! empty($pm['is_active']) ? 1 : 0,
                    'show_account_details' => 1,
                    'sort_order'           => (int) ($pm['sort_order'] ?? 0),
                ]
            );
            $countPayments++;
        }
        $this->command?->info("  ✓ Payment Methods imported: {$countPayments}");

        // 9. Posts
        $this->command?->info('  📰 Importing Blog & Guide Posts...');
        $postRows = $this->extractInsertRows($sqlContent, 'posts');
        $countPosts = 0;

        foreach ($postRows as $po) {
            if (empty($po['title'])) {
                continue;
            }

            $slug = ! empty($po['slug']) ? $po['slug'] : Str::slug($po['title']);

            Post::updateOrCreate(
                ['store_id' => $store->id, 'slug' => $slug],
                [
                    'title'        => $po['title'],
                    'excerpt'      => $po['excerpt'] ?? null,
                    'content'      => $po['content'] ?? ($po['body'] ?? ''),
                    'image_path'   => $po['image_path'] ?? ($po['cover_image'] ?? null),
                    'is_published' => ! empty($po['is_published']) ? 1 : 0,
                    'published_at' => $po['published_at'] ?? now(),
                    'category'     => $po['category'] ?? 'General',
                ]
            );
            $countPosts++;
        }
        $this->command?->info("  ✓ Posts imported: {$countPosts}");

        // 10. Flash Sales (20 Scheduled Deals for Home Storefront)
        $this->command?->info('  ⚡ Configuring 20 Flash Sale Deals with countdown timer...');
        $flashProds = Product::where('store_id', $store->id)
            ->where('retail_price', '>', 0)
            ->whereNotNull('image_path')
            ->take(20)
            ->get();

        if ($flashProds->count() < 20) {
            $extra = Product::where('store_id', $store->id)
                ->where('retail_price', '>', 0)
                ->whereNotIn('id', $flashProds->pluck('id'))
                ->take(20 - $flashProds->count())
                ->get();
            $flashProds = $flashProds->merge($extra);
        }

        $now = now();
        $saleEnds = now()->addDays(3)->setTime(23, 59, 59);

        foreach ($flashProds as $i => $fp) {
            $currentRetail = (float) $fp->retail_price;
            $discountPercent = 15 + ($i % 4) * 5; // 15%, 20%, 25%, 30%
            $oldPrice = round($currentRetail * (1 + ($discountPercent / 100)));
            if ($oldPrice <= $currentRetail) {
                $oldPrice = $currentRetail + 2000;
            }

            $fp->update([
                'old_price'      => $oldPrice,
                'sale_starts_at' => $now->copy()->subHours(2),
                'sale_ends_at'   => $saleEnds,
                'is_featured'    => true,
            ]);
        }
        $this->command?->info('  ✓ 20 Flash Sale Deals activated.');

        $this->command?->info("✨ Successfully seeded complete mobile store catalog from SQL dump into [{$store->slug}]!");
    }

    /**
     * Parse INSERT INTO `table` statements from a raw SQL dump string.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractInsertRows(string $sql, string $tableName): array
    {
        $rows = [];
        $pattern = '/INSERT INTO `' . preg_quote($tableName, '/') . '` \((.*?)\) VALUES \((.*?)\);/s';

        if (preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $cols = array_map(fn ($c) => trim($c, " `\t\n\r"), explode(',', $m[1]));
                $rawValues = $m[2];
                $values = str_getcsv($rawValues, ',', "'", '\\');

                $row = [];
                foreach ($cols as $idx => $col) {
                    $val = $values[$idx] ?? null;
                    if ($val === 'NULL' || $val === null) {
                        $row[$col] = null;
                    } else {
                        $row[$col] = trim($val);
                    }
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Ensure the 4 modern placeholder SVG files exist in public storage.
     */
    private function ensurePlaceholderSvgsExist(): void
    {
        $dir = storage_path('app/public/products');
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $svg1 = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="100%" height="100%">
  <defs>
    <linearGradient id="bgGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="50%" stop-color="#1e1b4b"/>
      <stop offset="100%" stop-color="#0284c7"/>
    </linearGradient>
    <linearGradient id="screenGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#38bdf8"/>
      <stop offset="50%" stop-color="#6366f1"/>
      <stop offset="100%" stop-color="#ec4899"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#bgGrad1)"/>
  <circle cx="400" cy="400" r="280" fill="#38bdf8" opacity="0.1"/>
  <g transform="translate(250, 120)">
    <rect x="0" y="0" width="300" height="560" rx="40" fill="#1e293b" stroke="#475569" stroke-width="6"/>
    <rect x="16" y="16" width="268" height="528" rx="28" fill="url(#screenGrad1)"/>
    <rect x="110" y="28" width="80" height="20" rx="10" fill="#0f172a"/>
    <circle cx="165" cy="38" r="3" fill="#334155"/>
    <circle cx="150" cy="220" r="50" fill="#ffffff" opacity="0.25"/>
    <path d="M135 220 L145 230 L165 210" stroke="#ffffff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
    <rect x="60" y="300" width="180" height="12" rx="6" fill="#ffffff" opacity="0.8"/>
    <rect x="80" y="325" width="140" height="8" rx="4" fill="#ffffff" opacity="0.5"/>
    <rect x="50" y="420" width="200" height="48" rx="24" fill="#ffffff" opacity="0.9"/>
    <text x="150" y="450" fill="#1e1b4b" font-family="system-ui, sans-serif" font-size="16" font-weight="bold" text-anchor="middle">DataPOS Mobile</text>
  </g>
  <text x="400" y="730" fill="#94a3b8" font-family="system-ui, sans-serif" font-size="22" font-weight="600" text-anchor="middle" letter-spacing="2">SMARTPHONE &amp; DEVICE</text>
</svg>
SVG;

        $svg2 = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="100%" height="100%">
  <defs>
    <linearGradient id="bgGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="50%" stop-color="#14532d"/>
      <stop offset="100%" stop-color="#059669"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#bgGrad2)"/>
  <circle cx="400" cy="380" r="240" fill="#10b981" opacity="0.12"/>
  <g transform="translate(240, 160)">
    <rect x="70" y="0" width="16" height="50" rx="4" fill="#94a3b8"/>
    <rect x="134" y="0" width="16" height="50" rx="4" fill="#94a3b8"/>
    <rect x="20" y="40" width="180" height="220" rx="28" fill="#1e293b" stroke="#334155" stroke-width="5"/>
    <circle cx="110" cy="130" r="45" fill="#10b981" opacity="0.2"/>
    <path d="M115 95 L95 135 L115 135 L105 165 L130 125 L110 125 Z" fill="#34d399"/>
    <rect x="80" y="220" width="60" height="18" rx="9" fill="#0f172a" stroke="#475569" stroke-width="2"/>
    <path d="M110 240 C 110 340, 320 280, 260 420 C 220 500, 360 480, 300 520" fill="none" stroke="#34d399" stroke-width="12" stroke-linecap="round"/>
    <rect x="280" y="490" width="40" height="60" rx="10" fill="#334155" transform="rotate(-20 300 520)"/>
  </g>
  <text x="400" y="730" fill="#86efac" font-family="system-ui, sans-serif" font-size="22" font-weight="600" text-anchor="middle" letter-spacing="2">FAST CHARGER &amp; CABLE</text>
</svg>
SVG;

        $svg3 = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="100%" height="100%">
  <defs>
    <linearGradient id="bgGrad3" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="50%" stop-color="#4c1d95"/>
      <stop offset="100%" stop-color="#8b5cf6"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#bgGrad3)"/>
  <circle cx="400" cy="380" r="250" fill="#a78bfa" opacity="0.12"/>
  <g transform="translate(180, 140)">
    <path d="M 60 280 A 160 160 0 0 1 380 280" fill="none" stroke="#cbd5e1" stroke-width="22" stroke-linecap="round"/>
    <path d="M 75 250 A 145 145 0 0 1 365 250" fill="none" stroke="#8b5cf6" stroke-width="8" stroke-linecap="round"/>
    <rect x="25" y="250" width="70" height="130" rx="35" fill="#1e293b" stroke="#8b5cf6" stroke-width="6"/>
    <rect x="75" y="270" width="20" height="90" rx="10" fill="#a78bfa" opacity="0.5"/>
    <rect x="345" y="250" width="70" height="130" rx="35" fill="#1e293b" stroke="#8b5cf6" stroke-width="6"/>
    <rect x="345" y="270" width="20" height="90" rx="10" fill="#a78bfa" opacity="0.5"/>
    <g transform="translate(160, 260)">
      <rect x="0" y="40" width="8" height="40" rx="4" fill="#c4b5fd"/>
      <rect x="20" y="20" width="8" height="80" rx="4" fill="#c4b5fd"/>
      <rect x="40" y="0" width="8" height="120" rx="4" fill="#8b5cf6"/>
      <rect x="60" y="10" width="8" height="100" rx="4" fill="#8b5cf6"/>
      <rect x="80" y="30" width="8" height="60" rx="4" fill="#c4b5fd"/>
      <rect x="100" y="45" width="8" height="30" rx="4" fill="#c4b5fd"/>
    </g>
  </g>
  <text x="400" y="730" fill="#d8b4fe" font-family="system-ui, sans-serif" font-size="22" font-weight="600" text-anchor="middle" letter-spacing="2">AUDIO &amp; EARPHONES</text>
</svg>
SVG;

        $svg4 = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="100%" height="100%">
  <defs>
    <linearGradient id="bgGrad4" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f172a"/>
      <stop offset="50%" stop-color="#701a75"/>
      <stop offset="100%" stop-color="#d946ef"/>
    </linearGradient>
    <linearGradient id="glassGrad4" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#fdf4ff" stop-opacity="0.6"/>
      <stop offset="50%" stop-color="#f0abfc" stop-opacity="0.2"/>
      <stop offset="100%" stop-color="#e879f9" stop-opacity="0.5"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#bgGrad4)"/>
  <circle cx="400" cy="380" r="250" fill="#e879f9" opacity="0.12"/>
  <g transform="translate(260, 130)">
    <path d="M 140 30 L 250 80 C 250 240, 140 340, 140 370 C 140 340, 30 240, 30 80 Z" fill="url(#glassGrad4)" stroke="#f5d0fe" stroke-width="8" stroke-linejoin="round"/>
    <rect x="55" y="110" width="170" height="310" rx="24" fill="#0f172a" opacity="0.75" stroke="#e879f9" stroke-width="4"/>
    <path d="M 70 130 L 190 350" stroke="#ffffff" stroke-width="6" stroke-linecap="round" opacity="0.5"/>
    <g transform="translate(95, 200)">
      <polygon points="45,0 90,30 45,60 0,30" fill="#f0abfc"/>
      <text x="45" y="38" fill="#4a044e" font-family="system-ui, sans-serif" font-size="20" font-weight="900" text-anchor="middle">9D</text>
    </g>
  </g>
  <text x="400" y="730" fill="#f5d0fe" font-family="system-ui, sans-serif" font-size="22" font-weight="600" text-anchor="middle" letter-spacing="2">TEMPERED GLASS &amp; ACCESSORIES</text>
</svg>
SVG;

        if (! file_exists("$dir/placeholder-mobile-1.svg")) {
            file_put_contents("$dir/placeholder-mobile-1.svg", $svg1);
        }
        if (! file_exists("$dir/placeholder-mobile-2.svg")) {
            file_put_contents("$dir/placeholder-mobile-2.svg", $svg2);
        }
        if (! file_exists("$dir/placeholder-mobile-3.svg")) {
            file_put_contents("$dir/placeholder-mobile-3.svg", $svg3);
        }
        if (! file_exists("$dir/placeholder-mobile-4.svg")) {
            file_put_contents("$dir/placeholder-mobile-4.svg", $svg4);
        }
    }
}
