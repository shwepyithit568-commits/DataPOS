<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\InventoryMovement;
use App\POS\Models\Warehouse;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreLocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoBusinessScenarioService
{
    public function __construct(
        private StoreLocationService $storeLocations,
        private InventoryService $inventory,
        private CustomerDebtService $debt,
        private DemoStorefrontAssetService $storefrontAssets,
    ) {
    }

    public function scenarios(): array
    {
        return [
            'mobile-accessories' => [
                'label' => 'Mobile & Accessories',
                'subtitle' => 'ဖုန်းနှင့် Accessories အရောင်းဆိုင်',
                'description' => 'Phone, cover, glass, charger, cable, earphone stock workflow စမ်းရန်။',
                'store_slug' => 'mobile-accessories-demo',
                'readiness' => 'Core-ready',
                'limitation' => null,
            ],
            'mobile-sale-service' => [
                'label' => 'Mobile & CCTV Sale & Service',
                'subtitle' => 'ဖုန်း၊ CCTV၊ ကွန်ရက်၊ ကွန်ပျူတာ အရောင်းနှင့် ပြုပြင်ရေး/တပ်ဆင်ရေးဆိုင်',
                'description' => 'ဖုန်း၊ CCTV ကင်မရာ၊ NVR၊ ကွန်ပျူတာ၊ ကွန်ရက်ပစ္စည်း၊ အပိုပစ္စည်း၊ Technician ပြုပြင်စရိတ်နှင့် တပ်ဆင်ရေး Package စာရင်းများ။',
                'store_slug' => 'mobile-sale-service-demo',
                'readiness' => 'Core-ready',
                'limitation' => null,
            ],
            'cctv-network-computer' => [
                'label' => 'CCTV + Network + Computer',
                'subtitle' => 'CCTV, Network, Computer အရောင်း/တပ်ဆင်ရေး',
                'description' => 'Project stock, showroom, service spare parts, installation package workflow စမ်းရန်။',
                'store_slug' => 'cctv-network-computer-demo',
                'readiness' => 'Core-ready',
                'limitation' => null,
            ],
            'pharmacy' => [
                'label' => 'Pharmacy',
                'subtitle' => 'ဆေးဆိုင်',
                'description' => 'Medicine, supplement, device, expiry/batch stock workflow စမ်းရန်။',
                'store_slug' => 'pharmacy-demo',
                'readiness' => 'Preview',
                'limitation' => 'Batch/expiry ကို demo metadata အဖြစ်သာပြထားပြီး production workflow မပြီးသေးပါ။',
            ],
            'restaurant' => [
                'label' => 'Restaurant',
                'subtitle' => 'စားသောက်ဆိုင်',
                'description' => 'Kitchen stock, counter drinks, ready-to-sell menu workflow စမ်းရန်။',
                'store_slug' => 'restaurant-demo',
                'readiness' => 'Preview',
                'limitation' => 'Table, KOT, modifier နှင့် recipe inventory မပါသေးသော catalog/POS demo ဖြစ်သည်။',
            ],
            'diamond-stone-agri' => [
                'label' => 'Diamond Stone',
                'subtitle' => 'စိုက်ပျိုးရေး မျိုးစေ့နှင့် ပိုးသတ်ဆေးအရောင်းဆိုင်',
                'description' => 'Seeds, fertilizer, pesticide, supplier credit, expiry/chemical stock workflow စမ်းရန်။',
                'store_slug' => 'diamond-stone-agri',
                'readiness' => 'Preview',
                'limitation' => 'Batch, expiry နှင့် UOM ကို production-grade workflow အဖြစ် မထောက်ပံ့သေးပါ။',
            ],
            'si-taw-gyi-food-bar' => [
                'label' => 'စည်တော်ကြီး',
                'subtitle' => 'စားသောက်ဆိုင် + အရက်/ဘီယာအရောင်းဆိုင်',
                'description' => 'Kitchen stock, bar stock, daily counter, food/drink sale workflow စမ်းရန်။',
                'store_slug' => 'si-taw-gyi-food-bar',
                'readiness' => 'Preview',
                'limitation' => 'Table/KOT မပါသေးသော counter sale နှင့် catalog demo ဖြစ်သည်။',
            ],
            'general-retail' => [
                'label' => 'General Retail / Mini Mart',
                'subtitle' => 'ကုန်စုံ၊ အိမ်သုံးပစ္စည်းနှင့် Mini Mart',
                'description' => 'Barcode POS, fast-moving stock, retail/wholesale price နှင့် daily closing workflow စမ်းရန်။',
                'store_slug' => 'general-retail-demo',
                'readiness' => 'Core-ready',
                'limitation' => null,
            ],
            'kl-fashion' => [
                'label' => 'KL Fashion & Tailoring',
                'subtitle' => 'စက်ချုပ်ဆိုင်၊ အဝတ်အထည်၊ ပိတ်စနှင့် စက်ချုပ်အပိုပစ္စည်း',
                'description' => 'စက်ချုပ်ဝန်ဆောင်မှု (Tailoring)၊ အသင့်ဝတ်အထည် (Garments)၊ ပိတ်စ (Fabrics) နှင့် စက်ချုပ်အပိုပစ္စည်း (Sewing Notions) ၄ မျိုးစုံ လုပ်ငန်းစနစ်။',
                'store_slug' => 'kl-fashion',
                'readiness' => 'Core-ready',
                'limitation' => null,
            ],
        ];
    }

    public function create(string $scenarioKey, User $actor): array
    {
        if (! app()->environment(['local', 'testing', 'uat']) || ! config('app.show_quick_login')) {
            throw new \RuntimeException('Demo business scenarios are available only in local/UAT quick-login mode.');
        }

        $scenario = $this->scenarioDefinition($scenarioKey);

        $result = DB::transaction(function () use ($scenarioKey, $scenario, $actor) {
            $store = Store::where('slug', $scenario['store']['slug'])->first();
            if (! $store && ! empty($scenario['legacy_slugs'])) {
                $store = Store::whereIn('slug', $scenario['legacy_slugs'])->first();
            }

            if ($store) {
                $store->fill($scenario['store'] + ['is_active' => true, 'is_primary' => false])->save();
            } else {
                $store = Store::create($scenario['store'] + ['is_active' => true, 'is_primary' => false]);
            }

            StorefrontSetting::updateOrCreate(
                ['store_id' => $store->id],
                $scenario['setting'] + ['store_id' => $store->id, 'default_language' => 'my']
            );

            // Automatically Seed Master Data Presets (Categories, Brands, Connectors, Colors, Shelves, Warranties, Return Policies, Variant Matrix)
            $masterDataType = match ($scenarioKey) {
                'kl-fashion', 'fashion-tailoring' => 'fashion',
                'general-retail' => 'general',
                'mobile-accessories', 'mobile-sale-service', 'cctv-network-computer', 'datapos-mobile' => 'tech',
                default => null,
            };
            if ($masterDataType !== null) {
                app(\Database\Seeders\MasterDataSeedImporter::class)->importForStore(
                    $store,
                    ['brands', 'categories', 'connectors', 'colors', 'shelves', 'warranties', 'return_policies', 'variant_presets'],
                    $masterDataType
                );
            }

            // Bootstrap Staff Roles
            StaffRole::bootstrapDefaultRoles($store);

            $locations = $this->storeLocations->ensureDefaults($store);
            $this->attachUser($store, $actor, 'store_owner', 'store_owner');

            foreach ($scenario['users'] as $user) {
                $this->attachUser(
                    $store,
                    $this->user($user),
                    $user['store_role'] ?? 'staff',
                    $user['staff_role'] ?? ($user['store_role'] ?? null)
                );
            }

            $warehouses = [
                'MAIN' => $locations['warehouse'],
            ];

            foreach ($scenario['warehouses'] as $warehouse) {
                $warehouses[$warehouse['code']] = Warehouse::updateOrCreate(
                    ['store_id' => $store->id, 'name' => $warehouse['name']],
                    [
                        'branch_id' => $locations['branch']->id,
                        'code' => $warehouse['code'],
                        'is_default' => false,
                        'is_active' => true,
                    ]
                );
            }

            $categories = [];
            foreach ($scenario['categories'] as $category) {
                $categoryModel = null;
                if (! empty($category['code'])) {
                    $categoryModel = Category::where('store_id', $store->id)->where('code', $category['code'])->first();
                }
                if (! $categoryModel && ! empty($category['name'])) {
                    $categoryModel = Category::where('store_id', $store->id)->where('name', $category['name'])->first();
                }
                if (! $categoryModel && ! empty($category['slug'])) {
                    $categoryModel = Category::where('store_id', $store->id)->where('slug', $category['slug'])->first();
                }
                if (! $categoryModel) {
                    $catSlug = ! empty($category['slug']) ? $category['slug'] : (Str::slug($category['name']) . '-' . $store->id);
                    $categoryModel = Category::updateOrCreate(
                        ['store_id' => $store->id, 'slug' => $catSlug],
                        [
                            'name' => $category['name'],
                            'code' => $category['code'] ?? strtoupper(str_replace('-', '_', $category['slug'] ?? Str::slug($category['name']))),
                            'description' => $category['description'] ?? null,
                            'icon' => $category['icon'] ?? null,
                        ]
                    );
                }
                $categories[$category['key']] = $categoryModel;
            }

            $brands = [];
            foreach ($scenario['brands'] as $brand) {
                $brandModel = null;
                if (! empty($brand['code'])) {
                    $brandModel = Brand::where('store_id', $store->id)->where('code', $brand['code'])->first();
                }
                if (! $brandModel && ! empty($brand['name'])) {
                    $brandModel = Brand::where('store_id', $store->id)->where('name', $brand['name'])->first();
                }
                if (! $brandModel && ! empty($brand['slug'])) {
                    $brandModel = Brand::where('store_id', $store->id)->where('slug', $brand['slug'])->first();
                }
                if (! $brandModel && ! empty($brand['legacy_slugs'])) {
                    $brandModel = Brand::where('store_id', $store->id)->whereIn('slug', $brand['legacy_slugs'])->first();
                }

                if (! $brandModel) {
                    $brandSlug = ! empty($brand['slug']) ? $brand['slug'] : (Str::slug($brand['name']) . '-' . $store->id);
                    $brandModel = Brand::create([
                        'store_id' => $store->id,
                        'name' => $brand['name'],
                        'slug' => $brandSlug,
                        'code' => $brand['code'] ?? strtoupper(str_replace('-', '_', $brand['slug'] ?? Str::slug($brand['name']))),
                    ]);
                }

                $brands[$brand['key']] = $brandModel;
            }

            $suppliers = [];
            foreach ($scenario['suppliers'] as $supplier) {
                $suppliers[$supplier['key']] = Supplier::updateOrCreate(
                    ['store_id' => $store->id, 'phone' => $supplier['phone']],
                    [
                        'name' => $supplier['name'],
                        'contact_person' => $supplier['contact_person'] ?? null,
                        'address' => $supplier['address'] ?? null,
                        'notes' => $supplier['notes'] ?? null,
                    ]
                );
            }

            $productsCreated = 0;
            foreach ($scenario['products'] as $item) {
                $product = Product::updateOrCreate(
                    ['store_id' => $store->id, 'sku' => $item['sku']],
                    [
                        'category_id' => $categories[$item['category_key']]->id,
                        'brand_id' => $brands[$item['brand_key']]->id,
                        'supplier_id' => $suppliers[$item['supplier_key']]->id,
                        'warehouse_id' => $warehouses[$item['warehouse_code']]->id,
                        'product_type' => $item['product_type'] ?? 'standard',
                        'barcode' => $item['barcode'] ?? null,
                        'name' => $item['name'],
                        'slug' => Str::slug($item['sku']),
                        'description' => $item['description'] ?? null,
                        'retail_price' => $item['retail_price'],
                        'old_price' => $item['old_price'] ?? null,
                        'sale_starts_at' => $item['sale_starts_at'] ?? null,
                        'sale_ends_at' => $item['sale_ends_at'] ?? null,
                        'wholesale_price' => $item['wholesale_price'] ?? $item['retail_price'],
                        'purchase_cost' => $item['purchase_cost'] ?? null,
                        'reorder_level' => $item['reorder_level'] ?? 5,
                        'stock_status' => 'in_stock',
                        'shelf_location' => $item['shelf_location'] ?? null,
                        'service_duration' => $item['service_duration'] ?? null,
                        'digital_delivery_method' => $item['digital_delivery_method'] ?? null,
                        'is_featured' => $item['is_featured'] ?? false,
                        'is_ecommerce' => $item['is_ecommerce'] ?? true,
                        'warranty' => $item['warranty'] ?? null,
                        'return_policy' => $item['return_policy'] ?? 'Demo return policy only.',
                        'specs' => $item['specs'] ?? null,
                    ]
                );

                // Seed child variant matrix records if product_type is variant
                if (($item['product_type'] ?? 'standard') === 'variant' && !empty($item['variants'])) {
                    foreach ($item['variants'] as $vIndex => $vData) {
                        \App\Models\ProductVariant::updateOrCreate(
                            ['product_id' => $product->id, 'sku' => $vData['sku']],
                            [
                                'name' => $vData['name'],
                                'attributes' => $vData['attributes'] ?? [],
                                'retail_price' => $vData['retail_price'] ?? $item['retail_price'],
                                'wholesale_price' => $vData['wholesale_price'] ?? ($vData['retail_price'] ?? $item['retail_price']),
                                'stock_status' => 'in_stock',
                                'quantity_on_hand' => $vData['quantity_on_hand'] ?? 10,
                                'is_default' => $vData['is_default'] ?? ($vIndex === 0),
                                'sort_order' => $vIndex,
                            ]
                        );
                    }
                    $product->update(['stock_status' => 'in_stock']);
                }

                $hasOpeningMovement = InventoryMovement::query()
                    ->where('store_id', $store->id)
                    ->where('source_type', 'demo_scenario')
                    ->where('source_id', $store->id)
                    ->where('product_id', $product->id)
                    ->exists();

                if (! $hasOpeningMovement) {
                    $this->inventory->postMovement([
                        'store' => $store,
                        'warehouse_id' => $warehouses[$item['warehouse_code']]->id,
                        'branch_id' => $locations['branch']->id,
                        'product_id' => $product->id,
                        'movement_type' => InventoryMovementType::OpeningBalance->value,
                        'quantity_delta' => (string) $item['opening_stock'],
                        'unit_cost' => $item['purchase_cost'] ?? null,
                        'source_type' => 'demo_scenario',
                        'source_id' => $store->id,
                        'client_transaction_id' => "demo:{$scenarioKey}:opening:{$item['sku']}",
                        'metadata' => ['scenario' => $scenarioKey],
                    ]);
                }

                $productsCreated++;
            }

            // Automatically Seed Storefront Blog Articles (Myanmar Buying Guides & Tips)
            \Database\Seeders\BlogSeeder::seedForStore($store);

            return [
                'store' => $store,
                'products' => $productsCreated,
                'featured_products' => collect($scenario['products'])->where('is_featured', true)->count(),
                'timed_promotions' => collect($scenario['products'])->whereNotNull('old_price')->count(),
                'warehouses' => count($warehouses),
                'users' => count($scenario['users']) + 1,
            ];
        });

        return $this->attachStorefrontAssets($result, $scenarioKey);
    }

    /**
     * Seed scenario data directly into an existing store with optional clearing of old test records.
     */
    public function seedIntoStore(
        Store $store,
        string $scenarioKey,
        User $actor,
        bool $cleanOldData = false,
        bool $applyStoreIdentity = false
    ): array
    {
        if (! app()->environment(['local', 'testing', 'uat'])) {
            throw new \RuntimeException('Demo business scenarios are available only in local/testing/uat environments.');
        }

        $scenario = $this->scenarioDefinition($scenarioKey);

        $result = DB::transaction(function () use ($store, $scenarioKey, $scenario, $actor, $cleanOldData, $applyStoreIdentity) {
            if ($cleanOldData) {
                $this->cleanStoreData($store);
            }

            if ($applyStoreIdentity) {
                $store->fill([
                    'name' => $scenario['store']['name'],
                    'business_type' => $scenario['store']['business_type'],
                    'viber_number' => $scenario['store']['viber_number'] ?? null,
                    'telegram_username' => $scenario['store']['telegram_username'] ?? null,
                ])->save();

                StorefrontSetting::updateOrCreate(
                    ['store_id' => $store->id],
                    $scenario['setting'] + ['store_id' => $store->id, 'default_language' => 'my']
                );
            }

            // Automatically Seed Master Data Presets (Categories, Brands, Connectors, Colors, Shelves, Warranties, Return Policies, Variant Matrix)
            $masterDataType = match ($scenarioKey) {
                'kl-fashion', 'fashion-tailoring' => 'fashion',
                'general-retail' => 'general',
                'mobile-accessories', 'mobile-sale-service', 'cctv-network-computer', 'datapos-mobile' => 'tech',
                default => null,
            };
            if ($masterDataType !== null) {
                app(\Database\Seeders\MasterDataSeedImporter::class)->importForStore(
                    $store,
                    ['brands', 'categories', 'connectors', 'colors', 'shelves', 'warranties', 'return_policies', 'variant_presets'],
                    $masterDataType
                );
            }

            // Bootstrap Staff Roles
            StaffRole::bootstrapDefaultRoles($store);

            $locations = $this->storeLocations->ensureDefaults($store);
            $this->attachUser($store, $actor, 'store_owner', 'store_owner');

            foreach ($scenario['users'] as $user) {
                $this->attachUser(
                    $store,
                    $this->user($user),
                    $user['store_role'] ?? 'staff',
                    $user['staff_role'] ?? ($user['store_role'] ?? null)
                );
            }

            $warehouses = [
                'MAIN' => $locations['warehouse'],
            ];

            foreach ($scenario['warehouses'] as $warehouse) {
                $warehouses[$warehouse['code']] = Warehouse::updateOrCreate(
                    ['store_id' => $store->id, 'name' => $warehouse['name']],
                    [
                        'branch_id' => $locations['branch']->id,
                        'code' => $warehouse['code'],
                        'is_default' => false,
                        'is_active' => true,
                    ]
                );
            }

            $categories = [];
            foreach ($scenario['categories'] as $category) {
                $categoryModel = null;
                if (! empty($category['code'])) {
                    $categoryModel = Category::where('store_id', $store->id)->where('code', $category['code'])->first();
                }
                if (! $categoryModel && ! empty($category['name'])) {
                    $categoryModel = Category::where('store_id', $store->id)->where('name', $category['name'])->first();
                }
                if (! $categoryModel && ! empty($category['slug'])) {
                    $categoryModel = Category::where('store_id', $store->id)->where('slug', $category['slug'])->first();
                }
                if (! $categoryModel) {
                    $catSlug = ! empty($category['slug']) ? $category['slug'] : (Str::slug($category['name']) . '-' . $store->id);
                    $categoryModel = Category::updateOrCreate(
                        ['store_id' => $store->id, 'slug' => $catSlug],
                        [
                            'name' => $category['name'],
                            'code' => $category['code'] ?? strtoupper(str_replace('-', '_', $category['slug'] ?? Str::slug($category['name']))),
                            'description' => $category['description'] ?? null,
                            'icon' => $category['icon'] ?? null,
                        ]
                    );
                }
                $categories[$category['key']] = $categoryModel;
            }

            $brands = [];
            foreach ($scenario['brands'] as $brand) {
                $brandModel = null;
                if (! empty($brand['code'])) {
                    $brandModel = Brand::where('store_id', $store->id)->where('code', $brand['code'])->first();
                }
                if (! $brandModel && ! empty($brand['name'])) {
                    $brandModel = Brand::where('store_id', $store->id)->where('name', $brand['name'])->first();
                }
                if (! $brandModel && ! empty($brand['slug'])) {
                    $brandModel = Brand::where('store_id', $store->id)->where('slug', $brand['slug'])->first();
                }
                if (! $brandModel && ! empty($brand['legacy_slugs'])) {
                    $brandModel = Brand::where('store_id', $store->id)->whereIn('slug', $brand['legacy_slugs'])->first();
                }

                if (! $brandModel) {
                    $brandSlug = ! empty($brand['slug']) ? $brand['slug'] : (Str::slug($brand['name']) . '-' . $store->id);
                    $brandModel = Brand::create([
                        'store_id' => $store->id,
                        'name' => $brand['name'],
                        'slug' => $brandSlug,
                        'code' => $brand['code'] ?? strtoupper(str_replace('-', '_', $brand['slug'] ?? Str::slug($brand['name']))),
                    ]);
                }

                $brands[$brand['key']] = $brandModel;
            }

            $suppliers = [];
            foreach ($scenario['suppliers'] as $supplier) {
                $suppliers[$supplier['key']] = Supplier::updateOrCreate(
                    ['store_id' => $store->id, 'phone' => $supplier['phone']],
                    [
                        'name' => $supplier['name'],
                        'contact_person' => $supplier['contact_person'] ?? null,
                        'address' => $supplier['address'] ?? null,
                        'notes' => $supplier['notes'] ?? null,
                    ]
                );
            }

            $productsCreated = 0;
            foreach ($scenario['products'] as $item) {
                $product = Product::updateOrCreate(
                    ['store_id' => $store->id, 'sku' => $item['sku']],
                    [
                        'category_id' => $categories[$item['category_key']]->id,
                        'brand_id' => $brands[$item['brand_key']]->id,
                        'supplier_id' => $suppliers[$item['supplier_key']]->id,
                        'warehouse_id' => $warehouses[$item['warehouse_code']]->id,
                        'product_type' => $item['product_type'] ?? 'standard',
                        'barcode' => $item['barcode'] ?? null,
                        'name' => $item['name'],
                        'slug' => Str::slug($item['sku']),
                        'description' => $item['description'] ?? null,
                        'retail_price' => $item['retail_price'],
                        'old_price' => $item['old_price'] ?? null,
                        'sale_starts_at' => $item['sale_starts_at'] ?? null,
                        'sale_ends_at' => $item['sale_ends_at'] ?? null,
                        'wholesale_price' => $item['wholesale_price'] ?? $item['retail_price'],
                        'purchase_cost' => $item['purchase_cost'] ?? null,
                        'reorder_level' => $item['reorder_level'] ?? 5,
                        'stock_status' => 'in_stock',
                        'shelf_location' => $item['shelf_location'] ?? null,
                        'service_duration' => $item['service_duration'] ?? null,
                        'digital_delivery_method' => $item['digital_delivery_method'] ?? null,
                        'is_featured' => $item['is_featured'] ?? false,
                        'is_ecommerce' => $item['is_ecommerce'] ?? true,
                        'warranty' => $item['warranty'] ?? null,
                        'return_policy' => $item['return_policy'] ?? 'Demo return policy only.',
                        'specs' => $item['specs'] ?? null,
                    ]
                );

                // Seed child variant matrix records if product_type is variant
                if (($item['product_type'] ?? 'standard') === 'variant' && !empty($item['variants'])) {
                    foreach ($item['variants'] as $vIndex => $vData) {
                        \App\Models\ProductVariant::updateOrCreate(
                            ['product_id' => $product->id, 'sku' => $vData['sku']],
                            [
                                'name' => $vData['name'],
                                'attributes' => $vData['attributes'] ?? [],
                                'retail_price' => $vData['retail_price'] ?? $item['retail_price'],
                                'wholesale_price' => $vData['wholesale_price'] ?? ($vData['retail_price'] ?? $item['retail_price']),
                                'stock_status' => 'in_stock',
                                'quantity_on_hand' => $vData['quantity_on_hand'] ?? 10,
                                'is_default' => $vData['is_default'] ?? ($vIndex === 0),
                                'sort_order' => $vIndex,
                            ]
                        );
                    }
                    $product->update(['stock_status' => 'in_stock']);
                }

                $hasOpeningMovement = InventoryMovement::query()
                    ->where('store_id', $store->id)
                    ->where('source_type', 'demo_scenario')
                    ->where('product_id', $product->id)
                    ->exists();

                if (! $hasOpeningMovement) {
                    $this->inventory->postMovement([
                        'store' => $store,
                        'warehouse_id' => $warehouses[$item['warehouse_code']]->id,
                        'branch_id' => $locations['branch']->id,
                        'product_id' => $product->id,
                        'movement_type' => InventoryMovementType::OpeningBalance->value,
                        'quantity_delta' => (string) $item['opening_stock'],
                        'unit_cost' => $item['purchase_cost'] ?? null,
                        'source_type' => 'demo_scenario',
                        'source_id' => $store->id,
                        'client_transaction_id' => "demo:{$scenarioKey}:opening:{$item['sku']}:" . $store->id,
                        'metadata' => ['scenario' => $scenarioKey],
                    ]);
                }

                $productsCreated++;
            }

            // Seed Sample Customers & Debts
            $this->seedSampleCustomersAndDebts($store, $actor);

            // Automatically Seed Storefront Blog Articles (Myanmar Buying Guides & Tips)
            \Database\Seeders\BlogSeeder::seedForStore($store);

            return [
                'store' => $store,
                'products' => $productsCreated,
                'featured_products' => collect($scenario['products'])->where('is_featured', true)->count(),
                'timed_promotions' => collect($scenario['products'])->whereNotNull('old_price')->count(),
                'warehouses' => count($warehouses),
                'users' => count($scenario['users']) + 1,
                'identity_updated' => $applyStoreIdentity,
            ];
        });

        return $this->attachStorefrontAssets($result, $scenarioKey);
    }

    public function purgeStorefrontAssets(Store $store): void
    {
        $this->storefrontAssets->purge($store);
    }

    private function attachStorefrontAssets(array $result, string $scenarioKey): array
    {
        try {
            $result['assets'] = $this->storefrontAssets->generate($result['store'], $scenarioKey);
            $result['asset_warning'] = null;
        } catch (\Throwable $exception) {
            Log::warning('Demo data seeded but storefront asset generation failed.', [
                'store_id' => $result['store']->id,
                'scenario' => $scenarioKey,
                'exception' => $exception->getMessage(),
            ]);
            $result['assets'] = ['products' => 0, 'categories' => 0, 'banners' => 0, 'skipped' => 0];
            $result['asset_warning'] = $exception->getMessage();
        }

        return $result;
    }

    /**
     * Clean old test data (orders, sales, inventory, debts, service jobs, products, categories, brands) for a store.
     */
    public function cleanStoreData(Store $store): void
    {
        // 1. Delete online orders and order items
        if (Schema::hasTable('orders')) {
            if (Schema::hasTable('order_items')) {
                DB::table('order_items')->whereIn('order_id', function ($query) use ($store) {
                    $query->select('id')->from('orders')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('orders')->where('store_id', $store->id)->delete();
        }

        // 2. Delete wholesale applications, reviews & blog posts
        if (Schema::hasTable('wholesale_applications')) {
            DB::table('wholesale_applications')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('reviews')) {
            DB::table('reviews')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('posts')) {
            DB::table('posts')->where('store_id', $store->id)->delete();
        }

        // 3. Delete POS sales, items, returns, shifts, and daily closings
        if (Schema::hasTable('pos_sales')) {
            if (Schema::hasTable('pos_sale_items')) {
                DB::table('pos_sale_items')->whereIn('sale_id', function ($query) use ($store) {
                    $query->select('id')->from('pos_sales')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('pos_sales')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('pos_returns')) {
            if (Schema::hasTable('pos_return_items')) {
                DB::table('pos_return_items')->whereIn('return_id', function ($query) use ($store) {
                    $query->select('id')->from('pos_returns')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('pos_returns')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('cashier_shifts')) {
            if (Schema::hasTable('cash_events')) {
                DB::table('cash_events')->where('store_id', $store->id)->delete();
            }
            DB::table('cashier_shifts')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('daily_closings')) {
            DB::table('daily_closings')->where('store_id', $store->id)->delete();
        }

        // 4. Delete purchases, purchase orders, goods receipts, returns, and buybacks
        if (Schema::hasTable('purchases')) {
            if (Schema::hasTable('purchase_items')) {
                DB::table('purchase_items')->whereIn('purchase_id', function ($query) use ($store) {
                    $query->select('id')->from('purchases')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('purchases')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('purchase_orders')) {
            if (Schema::hasTable('purchase_order_items')) {
                DB::table('purchase_order_items')->whereIn('purchase_order_id', function ($query) use ($store) {
                    $query->select('id')->from('purchase_orders')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('purchase_orders')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('goods_receipts')) {
            if (Schema::hasTable('goods_receipt_items')) {
                DB::table('goods_receipt_items')->whereIn('goods_receipt_id', function ($query) use ($store) {
                    $query->select('id')->from('goods_receipts')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('goods_receipts')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('buy_backs')) {
            DB::table('buy_backs')->where('store_id', $store->id)->delete();
        }

        // 5. Delete stock counts, transfers, adjustments, movements, and balances
        if (Schema::hasTable('stock_counts')) {
            if (Schema::hasTable('stock_count_lines')) {
                DB::table('stock_count_lines')->whereIn('stock_count_id', function ($query) use ($store) {
                    $query->select('id')->from('stock_counts')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('stock_counts')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('stock_transfers')) {
            if (Schema::hasTable('stock_transfer_items')) {
                DB::table('stock_transfer_items')->whereIn('stock_transfer_id', function ($query) use ($store) {
                    $query->select('id')->from('stock_transfers')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('stock_transfers')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('inventory_adjustments')) {
            if (Schema::hasTable('inventory_adjustment_items')) {
                DB::table('inventory_adjustment_items')->whereIn('inventory_adjustment_id', function ($query) use ($store) {
                    $query->select('id')->from('inventory_adjustments')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('inventory_adjustments')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('inventory_movements')) {
            DB::table('inventory_movements')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('inventory_balances')) {
            DB::table('inventory_balances')->where('store_id', $store->id)->delete();
        }

        // 6. Delete service jobs, parts, device warranties, expenses, and debts
        if (Schema::hasTable('service_jobs')) {
            if (Schema::hasTable('service_job_parts')) {
                DB::table('service_job_parts')->whereIn('service_job_id', function ($query) use ($store) {
                    $query->select('id')->from('service_jobs')->where('store_id', $store->id);
                })->delete();
            }
            DB::table('service_jobs')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('device_warranties')) {
            DB::table('device_warranties')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('expenses')) {
            DB::table('expenses')->where('store_id', $store->id)->delete();
        }
        if (Schema::hasTable('customer_ledger_entries')) {
            DB::table('customer_ledger_entries')->where('store_id', $store->id)->delete();
        }

        // 7. Delete product images, variants, products, categories, brands, and suppliers
        if (Schema::hasTable('product_images')) {
            DB::table('product_images')->whereIn('product_id', function ($query) use ($store) {
                $query->select('id')->from('products')->where('store_id', $store->id);
            })->delete();
        }
        if (Schema::hasTable('product_variants')) {
            DB::table('product_variants')->whereIn('product_id', function ($query) use ($store) {
                $query->select('id')->from('products')->where('store_id', $store->id);
            })->delete();
        }
        Product::where('store_id', $store->id)->delete();
        Category::where('store_id', $store->id)->delete();
        Brand::where('store_id', $store->id)->delete();
        Supplier::where('store_id', $store->id)->delete();
    }

    private function seedSampleCustomersAndDebts(Store $store, User $actor): void
    {
        $customersData = [
            ['name' => 'ကိုကျော်စွာ (CCTV & Tech Wholesale Partner)', 'phone' => '09988776655', 'debt' => '185000', 'notes' => 'လုံခြုံရေးကင်မရာနှင့် ကွန်ရက်ပစ္စည်း လက်ကားဝယ်ယူသူ', 'role' => 'wholesale_customer'],
            ['name' => 'ဒေါ်နန်းခင်ခင် (Phone & Accessories Retail)', 'phone' => '09776655443', 'debt' => '45000', 'notes' => 'ဖုန်းအပိုပစ္စည်းနှင့် အားသွင်းကြိုး ပုံမှန်ဝယ်ယူသူ', 'role' => 'retail_customer'],
            ['name' => 'ကိုအောင်ကို (Regular Cash Customer)', 'phone' => '09665544332', 'debt' => '0', 'notes' => 'Cash Customer', 'role' => 'retail_customer'],
        ];

        foreach ($customersData as $c) {
            $user = User::updateOrCreate(
                ['phone' => $c['phone']],
                [
                    'name' => $c['name'],
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                ]
            );

            $this->attachUser($store, $user, $c['role'] ?? 'retail_customer');

            if (bccomp($c['debt'], '0', 2) > 0) {
                try {
                    $this->debt->recordOpeningBalance(
                        $store,
                        $user->id,
                        $c['debt'],
                        $actor,
                        $c['notes'],
                        "demo:debt:{$user->id}:{$store->id}"
                    );
                } catch (\Throwable) {
                    // Ignore if already posted
                }
            }
        }
    }

    private function scenarioDefinition(string $scenarioKey): array
    {
        $scenario = match ($scenarioKey) {
            'mobile-accessories' => $this->mobileAccessories(),
            'mobile-sale-service' => $this->mobileSaleService(),
            'cctv-network-computer' => $this->cctvNetworkComputer(),
            'pharmacy' => $this->pharmacy(),
            'restaurant' => $this->restaurant(),
            'diamond-stone-agri', 'diamon-stone-agri' => $this->diamondStoneAgri(),
            'si-taw-gyi-food-bar' => $this->siTawGyiFoodBar(),
            'general-retail' => $this->generalRetail(),
            'kl-fashion', 'fashion-tailoring' => $this->klFashionTailoring(),
            default => throw new \InvalidArgumentException('Unknown demo scenario.'),
        };

        return $this->enrichScenario($scenarioKey, $scenario);
    }

    private function user(array $data): User
    {
        return User::updateOrCreate(
            ['phone' => $data['phone']],
            [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'password' => Hash::make('password'),
                'pos_pin' => isset($data['pos_pin']) ? Hash::make($data['pos_pin']) : null,
                'role' => 'customer',
            ]
        );
    }

    private function attachUser(Store $store, User $user, string $role, ?string $staffRoleSlug = null): void
    {
        $staffRoleId = null;
        if ($staffRoleSlug) {
            $staffRoleId = StaffRole::where('store_id', $store->id)->where('slug', $staffRoleSlug)->value('id');
        } elseif (in_array($role, ['store_owner', 'store_manager', 'staff'])) {
            $staffRoleId = StaffRole::where('store_id', $store->id)->where('slug', $role)->value('id');
        }

        $normalizedRole = match ($role) {
            'customer' => 'retail_customer',
            default => in_array($role, ['store_owner', 'store_manager', 'staff', 'wholesale_customer', 'retail_customer']) ? $role : 'staff',
        };

        $pivotData = [
            'role' => $normalizedRole,
            'status' => 'active',
            'updated_at' => now(),
        ];

        if ($staffRoleId) {
            $pivotData['staff_role_id'] = $staffRoleId;
        }

        $store->users()->syncWithoutDetaching([
            $user->id => $pivotData,
        ]);
    }

    private function mobileAccessories(): array
    {
        return $this->scenario(
            store: ['name' => 'DataPOS Mobile & Accessories', 'business_type' => 'mobile_accessories', 'slug' => 'datapos-mobile', 'viber_number' => '09150000001', 'telegram_username' => 'datapos_mobile_demo'],
            setting: ['store_name' => 'DataPOS Mobile & Accessories', 'tagline' => 'ဖုန်း၊ ကာဗာ၊ မှန်ကပ်၊ Charger၊ နားကြပ်နှင့် ဆက်စပ်ပစ္စည်း အရောင်းဆိုင်', 'phone' => '09150000001'],
            users: [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'Mobile Shop Manager', 'phone' => '09150000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Front Counter Cashier', 'phone' => '09160000003', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'Master Hardware Technician', 'phone' => '09160000002', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'Warehouse & Stock Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'Finance & Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'Daw Aye (Wholesale Partner)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'Ma Su (Retail Customer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            warehouses: [
                ['name' => 'Front Shop Showroom', 'code' => 'FRONT'],
                ['name' => 'Back Stock Storage', 'code' => 'BACK'],
                ['name' => 'Damaged / Return', 'code' => 'RETURN'],
            ],
            categories: [
                ['key' => 'phones', 'name' => 'Smartphones & Tablets', 'code' => 'PHN', 'slug' => 'smartphones-tablets'],
                ['key' => 'charging', 'name' => 'Cable & Charger', 'code' => 'CBCH', 'slug' => 'cable-charger'],
                ['key' => 'power', 'name' => 'Power & Storage', 'code' => 'PWR', 'slug' => 'power-storage'],
                ['key' => 'audio', 'name' => 'Audio & Sound', 'code' => 'AUD', 'slug' => 'audio-sound'],
                ['key' => 'cases', 'name' => 'Phone Case & Cover', 'code' => 'ACC', 'slug' => 'phone-case-cover'],
                ['key' => 'glass', 'name' => 'Screen & LCD', 'code' => 'SCR', 'slug' => 'screen-lcd'],
                ['key' => 'stands', 'name' => 'Phone Stand & Holder', 'code' => 'HLD_GRP', 'slug' => 'phone-stand-holder'],
            ],
            brands: [
                ['key' => 'apple', 'name' => 'Apple / iPhone', 'code' => 'APL', 'slug' => 'apple-iphone', 'legacy_slugs' => ['apple']],
                ['key' => 'samsung', 'name' => 'Samsung', 'code' => 'SAM', 'slug' => 'samsung'],
                ['key' => 'xiaomi', 'name' => 'Xiaomi / Redmi', 'code' => 'RM', 'slug' => 'xiaomi-redmi', 'legacy_slugs' => ['xiaomi']],
                ['key' => 'anker', 'name' => 'Anker', 'code' => 'ANKER', 'slug' => 'anker'],
                ['key' => 'baseus', 'name' => 'Baseus', 'code' => 'BASEUS', 'slug' => 'baseus'],
                ['key' => 'remax', 'name' => 'Remax', 'code' => 'REMAX', 'slug' => 'remax'],
                ['key' => 'hoco', 'name' => 'Hoco', 'code' => 'HOCO', 'slug' => 'hoco'],
                ['key' => 'joyroom', 'name' => 'Joyroom', 'code' => 'JOYROOM', 'slug' => 'joyroom'],
                ['key' => 'kingston', 'name' => 'Kingston', 'code' => 'KST', 'slug' => 'kingston'],
                ['key' => 'sandisk', 'name' => 'SanDisk', 'code' => 'SDK', 'slug' => 'sandisk'],
            ],
            suppliers: [
                ['key' => 'yangon_mobile', 'name' => 'Yangon Mobile Wholesale Hub (ဆူးလေ/ပန်းဆိုးတန်း)', 'phone' => '09250000001', 'address' => 'Pansodan Road, Kyauktada, Yangon'],
                ['key' => 'accessory_supplier', 'name' => 'Mingalar Mobile Accessories Wholesale (မင်္ဂလာဈေး)', 'phone' => '09250000002', 'address' => 'Mingalar Market 3rd Floor, Yangon'],
                ['key' => 'mandalay_tech', 'name' => 'Mandalay 78 Road Tech Mart (မန္တလေး ၇၈ လမ်း)', 'phone' => '09250000003', 'address' => '78th Street, Mandalay'],
            ],
            products: [
                [
                    'sku' => 'MA-IP15-128-BLK',
                    'barcode' => '8935212300018',
                    'name' => 'iPhone 15 128GB Black (Official Company Warranty)',
                    'category_key' => 'phones',
                    'brand_key' => 'apple',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 2650000,
                    'wholesale_price' => 2580000,
                    'purchase_cost' => 2480000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A1',
                    'warranty' => '1 Year Official Apple Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'iPhone 15', 'color' => 'Black', 'capacity' => '128GB', 'connector' => 'Type-C'],
                    'description' => 'iPhone 15 128GB Black official company warranty ပါဝင်ပြီး Dynamic Island နှင့် 48MP Main Camera တပ်ဆင်ထားပါသည်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MA-SAM-A15-128',
                    'barcode' => '8935212300025',
                    'name' => 'Samsung Galaxy A15 8/128GB (Light Blue)',
                    'category_key' => 'phones',
                    'brand_key' => 'samsung',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 520000,
                    'wholesale_price' => 495000,
                    'purchase_cost' => 465000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A2',
                    'warranty' => '1 Year Official Samsung Myanmar Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'Galaxy A15', 'color' => 'Light Blue', 'ram_rom' => '8GB/128GB', 'screen' => '6.5 inch Super AMOLED 90Hz'],
                    'description' => 'Samsung Galaxy A15 8GB RAM / 128GB ROM, Super AMOLED 90Hz Display, 5000mAh Battery ပါဝင်သော မြန်မာတရားဝင်အာမခံ ဖုန်းအသစ်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MA-RDM-13C-128',
                    'barcode' => '8935212300032',
                    'name' => 'Redmi 13C 6/128GB (Midnight Black)',
                    'category_key' => 'phones',
                    'brand_key' => 'xiaomi',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 420000,
                    'wholesale_price' => 398000,
                    'purchase_cost' => 365000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A3',
                    'warranty' => '1 Year Official MI Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'Redmi 13C', 'color' => 'Midnight Black', 'ram_rom' => '6GB/128GB', 'battery' => '5000mAh'],
                    'description' => 'Xiaomi Redmi 13C 6GB RAM, 128GB ROM, 50MP AI Triple Camera, 18W Fast Charging ဖုန်းအသစ်။',
                ],
                [
                    'sku' => 'MA-ANK-20W-WHT',
                    'barcode' => '8935212300049',
                    'name' => 'Anker PowerPort III 20W PD Fast Charger (White)',
                    'category_key' => 'charging',
                    'brand_key' => 'anker',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 45000,
                    'wholesale_price' => 39000,
                    'purchase_cost' => 32000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CHG',
                    'warranty' => '18 Months Official Anker Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['wattage' => '20W PD Fast Charge', 'connector' => 'Type-C', 'color' => 'White', 'compatibility' => 'iPhone 15 / 14 / iPad / Samsung'],
                    'description' => 'Anker 20W PowerPort III Cube Type-C Fast Charger ခေါင်း အသေးစား ပေါ့ပါးပြီး အပူထိန်းစနစ်ပါဝင်သည်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MA-BAS-65W-GAN',
                    'barcode' => '8935212300056',
                    'name' => 'Baseus GaN5 Pro 65W Fast Charger (2C+1U) Black',
                    'category_key' => 'charging',
                    'brand_key' => 'baseus',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 78000,
                    'wholesale_price' => 69000,
                    'purchase_cost' => 58000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CHG',
                    'warranty' => '6 Months Replacement Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['wattage' => '65W GaN5 Pro', 'ports' => '2x Type-C + 1x USB-A', 'color' => 'Black', 'support' => 'Laptop / MacBook / Phone'],
                    'description' => 'Baseus 65W GaN Fast Charger ခေါင်း Laptop နှင့် ဖုန်းများကို တစ်ပြိုင်နက် မြန်ဆန်စွာ အားသွင်းနိုင်သည်။',
                ],
                [
                    'sku' => 'MA-RMX-PB-20K',
                    'barcode' => '8935212300063',
                    'name' => 'Remax RPP-296 20000mAh 22.5W Fast Power Bank',
                    'category_key' => 'power',
                    'brand_key' => 'remax',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 58000,
                    'wholesale_price' => 49000,
                    'purchase_cost' => 41000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-PB',
                    'warranty' => '6 Months Replacement Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['capacity' => '20000mAh', 'output' => '22.5W / PD 20W', 'ports' => 'USB + Type-C Dual Out', 'color' => 'Black'],
                    'description' => 'Remax 20000mAh Fast Charging Power Bank LED Battery Display ပါဝင်ပြီး လေယာဉ်ပေါ်သယ်ဆောင်ခွင့်ရှိသည်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MA-RMX-CC-100W',
                    'barcode' => '8935212300070',
                    'name' => 'Remax RC-C008 100W Type-C to Type-C Braided Cable (1.2m)',
                    'category_key' => 'charging',
                    'brand_key' => 'remax',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 12500,
                    'wholesale_price' => 9500,
                    'purchase_cost' => 6800,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CBL',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['connector' => 'Type-C to Type-C', 'power' => '100W PD Fast Charge', 'length' => '1.2m', 'material' => 'Nylon Braided'],
                    'description' => 'Remax 100W နိုင်လွန်ကြိုးကျစ် အကြမ်းခံ Type-C to Type-C Fast Charge & Data Cable။',
                ],
                [
                    'sku' => 'MA-HOCO-W35-SLV',
                    'barcode' => '8935212300087',
                    'name' => 'Hoco W35 Wireless Bluetooth ANC Headphone (Silver)',
                    'category_key' => 'audio',
                    'brand_key' => 'hoco',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 42000,
                    'wholesale_price' => 35000,
                    'purchase_cost' => 28000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-AUD',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => 'Defective Exchange within 7 Days',
                    'specs' => ['bluetooth' => 'Bluetooth 5.3', 'battery_life' => '40 Hours Playtime', 'color' => 'Silver Metal Finish', 'aux' => 'AUX / SD Card Support'],
                    'description' => 'Hoco W35 Metallic Design Bluetooth Headphone အသံကြည်လင်ပြီး Battery အသုံးခံသော နားကြပ်ကြီး။',
                ],
                [
                    'sku' => 'MA-HOCO-EW42-TWS',
                    'barcode' => '8935212300094',
                    'name' => 'Hoco EW42 True Wireless Bluetooth Earbuds (TWS)',
                    'category_key' => 'audio',
                    'brand_key' => 'hoco',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 32000,
                    'wholesale_price' => 26000,
                    'purchase_cost' => 20000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-AUD',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => 'Defective Exchange within 7 Days',
                    'specs' => ['bluetooth' => 'Bluetooth 5.3 TWS', 'battery' => '7 Hours Single / 30 Hours Case', 'touch' => 'Touch Sensor Control'],
                    'description' => 'Hoco EW42 TWS Earbuds အသံထွက်ကောင်းပြီး Bass သံကောင်းသော ကြိုးမဲ့နားကြပ်။',
                ],
                [
                    'sku' => 'MA-BAS-GLS-IP15',
                    'barcode' => '8935212300100',
                    'name' => 'Baseus Crystal 9H Full Coverage Tempered Glass (iPhone 15)',
                    'category_key' => 'glass',
                    'brand_key' => 'baseus',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 12000,
                    'wholesale_price' => 8500,
                    'purchase_cost' => 4800,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-GLS',
                    'warranty' => 'Screen Protector No Warranty (မျက်နှာပြင်ကာ — အာမခံမပါ)',
                    'return_policy' => 'Touch LCD / Screen — Test Before Purchase',
                    'specs' => ['hardness' => '9H Diamond Grade', 'type' => 'Full Cover HD Clear', 'model' => 'iPhone 15 / 15 Pro'],
                    'description' => 'Baseus Crystal 9H Full Cover Tempered Glass ဖုန်းမျက်နှာပြင် အစင်းထင်ခြင်းနှင့် ကွဲအက်ခြင်းကို ကာကွယ်ပေးသည်။',
                ],
                [
                    'sku' => 'MA-JOY-MAG-CAR',
                    'barcode' => '8935212300117',
                    'name' => 'Joyroom MagSafe Wireless 15W Car Charger Air Vent Mount',
                    'category_key' => 'stands',
                    'brand_key' => 'joyroom',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 38000,
                    'wholesale_price' => 31000,
                    'purchase_cost' => 24000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-STND',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => 'Defective Exchange within 7 Days',
                    'specs' => ['power' => '15W Fast Wireless Charge', 'mount' => 'Air Vent Clip & Magnetic', 'compatibility' => 'iPhone 12-15 Series'],
                    'description' => 'Joyroom ကားလေအေးပေါက်တပ် MagSafe 15W Wireless Fast Charger သံလိုက်ဓာတ် အားကောင်းသော ဖုန်းဒေါက်တိုင်။',
                ],
                [
                    'sku' => 'MA-SAM-CASE-A15',
                    'barcode' => '8935212300124',
                    'name' => 'Samsung Galaxy A15 Shockproof Airbag Clear Armor Case',
                    'category_key' => 'cases',
                    'brand_key' => 'samsung',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 7500,
                    'wholesale_price' => 5000,
                    'purchase_cost' => 2800,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CASE',
                    'warranty' => 'Check upon purchase',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['material' => 'High Quality TPU + Acrylic', 'feature' => '4 Corner Airbags Anti-Drop', 'model' => 'Samsung A15'],
                    'description' => 'Samsung A15 လေးထောင့်ဒေါင့် အကြမ်းခံ လေအိတ်ပါ ပွင့်လင်းကြည်လင် ဖုန်းကာဗာ။',
                ],
                [
                    'sku' => 'MA-KST-SD-64GB',
                    'barcode' => '8935212300131',
                    'name' => 'Kingston Canvas Select Plus 64GB MicroSD Class 10 (100MB/s)',
                    'category_key' => 'power',
                    'brand_key' => 'kingston',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 22000,
                    'wholesale_price' => 18500,
                    'purchase_cost' => 15000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-SD',
                    'warranty' => 'Lifetime Official Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['capacity' => '64GB', 'speed' => 'Up to 100MB/s Read', 'speed_class' => 'Class 10 UHS-I U1 V10'],
                    'description' => 'Kingston 64GB MicroSD Card ဖုန်းနှင့် ကင်မရာများအတွက် အထူးသင့်လျော်သော မမိုရီကတ် မူရင်းတရားဝင်။',
                ],
                [
                    'sku' => 'MA-SDK-DUAL-128',
                    'barcode' => '8935212300148',
                    'name' => 'SanDisk Ultra Dual Drive Go 128GB USB Type-C & Type-A',
                    'category_key' => 'power',
                    'brand_key' => 'sandisk',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 45000,
                    'wholesale_price' => 39000,
                    'purchase_cost' => 33000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-SD',
                    'warranty' => '5 Years Official Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['capacity' => '128GB', 'interface' => 'USB 3.1 Gen 1 (Type-C + Type-A)', 'speed' => 'Up to 150MB/s Read'],
                    'description' => 'SanDisk 128GB Type-C နှင့် USB ပေါက် ၂ မျိုးပါ ဖုန်းနှင့် ကွန်ပျူတာ အပြန်အလှန် ဒေတာကူးယူနိုင်သော OTG Drive။',
                ],
                [
                    'sku' => 'MA-JOY-DSK-STND',
                    'barcode' => '8935212300155',
                    'name' => 'Joyroom Foldable Aluminum Desktop Phone & Tablet Stand',
                    'category_key' => 'stands',
                    'brand_key' => 'joyroom',
                    'supplier_key' => 'accessory_supplier',
                    'warehouse_code' => 'FRONT',
                    'retail_price' => 16500,
                    'wholesale_price' => 13000,
                    'purchase_cost' => 9500,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-STND',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['material' => 'Full Aluminum Alloy + Non-slip Silicone', 'angle' => '0-120 Degree Multi-Angle Foldable'],
                    'description' => 'Joyroom အလူမီနီယမ် အကြမ်းခံ စားပွဲတင် ဖုန်းနှင့် တက်ဘလက် ထောက်တိုင် ခေါက်သိမ်းရလွယ်ကူသည်။',
                ],
            ],
        );
    }

    private function mobileSaleService(): array
    {
        return $this->scenario(
            store: ['name' => 'DataPOS Mobile Sale & Service', 'business_type' => 'mobile_sale_service', 'slug' => 'mobile-sale-service', 'viber_number' => '09160000001', 'telegram_username' => 'mobile_service_demo'],
            setting: ['store_name' => 'DataPOS Mobile Sale & Service', 'tagline' => 'စမတ်ဖုန်း၊ CCTV လုံခြုံရေးကင်မရာ၊ ကွန်ပျူတာ/ကွန်ရက်ပစ္စည်း အရောင်းနှင့် ပြုပြင်ရေး စင်တာ', 'phone' => '09160000001'],
            users: [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'Service Center Manager', 'phone' => '09160000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Master Hardware Technician', 'phone' => '09160000002', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'Front Counter Cashier', 'phone' => '09160000003', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'Parts & Stock Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'Finance & Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'Daw Aye (Wholesale Partner)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'Ma Su (Retail Customer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            warehouses: [
                ['name' => 'Showroom & Front Counter', 'code' => 'SHOW'],
                ['name' => 'Technician Spare Parts Cabinet', 'code' => 'SPARE'],
                ['name' => 'Repair Intake & Testing Desk', 'code' => 'INTAKE'],
                ['name' => 'CCTV & Project Stock Warehouse', 'code' => 'PROJECT'],
            ],
            categories: [
                ['key' => 'phones', 'name' => 'Smartphones & Tablets', 'code' => 'PHN', 'slug' => 'smartphones-tablets', 'legacy_slugs' => ['smartphones-devices']],
                ['key' => 'charging', 'name' => 'Cable & Charger', 'code' => 'CBCH', 'slug' => 'cable-chargers'],
                ['key' => 'power', 'name' => 'Power & Storage', 'code' => 'PWR', 'slug' => 'power-banks'],
                ['key' => 'audio', 'name' => 'Audio & Sound', 'code' => 'AUD', 'slug' => 'audio-sound'],
                ['key' => 'cases_glass', 'name' => 'Phone Case & Cover', 'code' => 'ACC', 'slug' => 'cases-tempered-glass'],
                ['key' => 'digital_codes', 'name' => 'Digital & Gift Cards', 'code' => 'DIG', 'slug' => 'digital-codes-topup'],
                ['key' => 'lcd_screens', 'name' => 'Screen & LCD', 'code' => 'SCR', 'slug' => 'touch-lcd-screens'],
                ['key' => 'batteries', 'name' => 'Battery (ဘတ်ထရီ)', 'code' => 'BT_GRP', 'slug' => 'phone-batteries'],
                ['key' => 'parts_boards', 'name' => 'Phone Spare Parts', 'code' => 'PRT', 'slug' => 'charging-ports-boards'],
                ['key' => 'repair_tools', 'name' => 'Phone Spare Parts', 'code' => 'PRT', 'slug' => 'technician-tools'],
                ['key' => 'services', 'name' => 'Service & Repair', 'code' => 'SVC', 'slug' => 'repair-service-fees'],
                ['key' => 'cctv', 'name' => 'CCTV & Security', 'code' => 'CCTV', 'slug' => 'cctv-security', 'legacy_slugs' => ['cctv-cameras']],
                ['key' => 'network', 'name' => 'Network & Connectivity', 'code' => 'NET', 'slug' => 'network-connectivity', 'legacy_slugs' => ['network-devices']],
                ['key' => 'computer', 'name' => 'Computer Peripherals', 'code' => 'ELEC', 'slug' => 'computer-peripherals', 'legacy_slugs' => ['computers']],
            ],
            brands: [
                ['key' => 'apple', 'name' => 'Apple / iPhone', 'code' => 'APL', 'slug' => 'apple-iphone', 'legacy_slugs' => ['apple']],
                ['key' => 'samsung', 'name' => 'Samsung', 'code' => 'SAM', 'slug' => 'samsung'],
                ['key' => 'xiaomi', 'name' => 'Xiaomi / Redmi', 'code' => 'RM', 'slug' => 'xiaomi-redmi', 'legacy_slugs' => ['xiaomi']],
                ['key' => 'vivo', 'name' => 'Vivo', 'code' => 'VV', 'slug' => 'vivo'],
                ['key' => 'oppo', 'name' => 'OPPO', 'code' => 'OP', 'slug' => 'oppo'],
                ['key' => 'realme', 'name' => 'Realme', 'code' => 'RL', 'slug' => 'realme'],
                ['key' => 'anker', 'name' => 'Anker', 'code' => 'ANKER', 'slug' => 'anker'],
                ['key' => 'remax', 'name' => 'Remax', 'code' => 'REMAX', 'slug' => 'remax'],
                ['key' => 'hoco', 'name' => 'Hoco', 'code' => 'HOCO', 'slug' => 'hoco'],
                ['key' => 'baseus', 'name' => 'Baseus', 'code' => 'BASEUS', 'slug' => 'baseus'],
                ['key' => 'joyroom', 'name' => 'Joyroom', 'code' => 'JOYROOM', 'slug' => 'joyroom'],
                ['key' => 'kingston', 'name' => 'Kingston', 'code' => 'KST', 'slug' => 'kingston'],
                ['key' => 'apple_gift', 'name' => 'Apple ID / iTunes', 'code' => 'APL_ID', 'slug' => 'apple-id-itunes', 'legacy_slugs' => ['apple-gift-card']],
                ['key' => 'google_play', 'name' => 'Google Play', 'code' => 'GGL_PLAY', 'slug' => 'google-play'],
                ['key' => 'moonton', 'name' => 'Mobile Legends (Moonton)', 'code' => 'MLBB', 'slug' => 'mobile-legends-moonton', 'legacy_slugs' => ['moonton-mlbb']],
                ['key' => 'pubg', 'name' => 'PUBG Mobile', 'code' => 'PUBG', 'slug' => 'pubg-mobile'],
                ['key' => 'mpt', 'name' => 'MPT Telecom', 'code' => 'MPT', 'slug' => 'mpt-telecom'],
                ['key' => 'atom', 'name' => 'ATOM Myanmar', 'code' => 'ATOM', 'slug' => 'atom-myanmar'],
                ['key' => 'kaspersky', 'name' => 'Kaspersky Lab', 'code' => 'KASP', 'slug' => 'kaspersky-lab', 'legacy_slugs' => ['kaspersky']],
                ['key' => 'service_center', 'name' => 'DataPOS Service Center', 'code' => 'SVC_CTR', 'slug' => 'datapos-service-center'],
                ['key' => 'hikvision', 'name' => 'Hikvision', 'code' => 'HIK', 'slug' => 'hikvision'],
                ['key' => 'dahua', 'name' => 'Dahua', 'code' => 'DAHUA', 'slug' => 'dahua'],
                ['key' => 'imou', 'name' => 'Imou', 'code' => 'IMOU', 'slug' => 'imou'],
                ['key' => 'tplink', 'name' => 'TP-Link', 'code' => 'TPLINK', 'slug' => 'tp-link'],
                ['key' => 'lenovo', 'name' => 'Lenovo', 'code' => 'LNV', 'slug' => 'lenovo'],
                ['key' => 'dell', 'name' => 'Dell', 'code' => 'DELL', 'slug' => 'dell'],
                ['key' => 'house', 'name' => 'Project Service', 'code' => 'PRJ_SVC', 'slug' => 'project-service'],
            ],
            suppliers: [
                ['key' => 'yangon_mobile', 'name' => 'Yangon Mobile Wholesale Hub (ရန်ကုန် မိုဘိုင်း လက်ကား)', 'phone' => '09260000001', 'address' => 'Pansodan Road, Yangon'],
                ['key' => 'mingalar_parts', 'name' => 'Mingalar Phone Parts Wholesale (မင်္ဂလာဈေး ဖုန်းအပိုပစ္စည်း)', 'phone' => '09260000002', 'address' => 'Mingalar Market, Yangon'],
                ['key' => 'mandalay_tech', 'name' => 'Mandalay Tech Wholesale (မန္တလေး အီလက်ထရောနစ်)', 'phone' => '09260000003', 'address' => '78th Road, Mandalay'],
                ['key' => 'digital_distributor', 'name' => 'Global Digital PIN & Top-Up Distributor (ဒစ်ဂျစ်တယ်ကုတ် လက်ကား)', 'phone' => '09260000004', 'address' => 'Yangon Cyber Hub'],
                ['key' => 'security_supplier', 'name' => 'Security & CCTV Wholesale Hub (လုံခြုံရေးနှင့် CCTV လက်ကား)', 'phone' => '09270000001', 'address' => 'Seikkantha Street, Kyauktada, Yangon'],
                ['key' => 'computer_supplier', 'name' => 'Computer & Network Wholesale (ကွန်ပျူတာနှင့် ကွန်ရက် လက်ကား)', 'phone' => '09270000002', 'address' => 'Anawrahta Road, Yangon'],
            ],
            products: [
                [
                    'sku' => 'MSS-IP15PM-256',
                    'barcode' => '8935212301015',
                    'name' => 'iPhone 15 Pro Max 256GB Natural Titanium (Official)',
                    'category_key' => 'phones',
                    'brand_key' => 'apple',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 4450000,
                    'wholesale_price' => 4380000,
                    'purchase_cost' => 4250000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A1',
                    'warranty' => '1 Year Official Apple Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'iPhone 15 Pro Max', 'color' => 'Natural Titanium', 'capacity' => '256GB', 'chip' => 'A17 Pro'],
                    'description' => 'Apple iPhone 15 Pro Max 256GB Titanium Grade Design, Action Button, 5X Telephoto Camera ပါဝင်သော official ဖုန်းအသစ်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-SAM-A55-256',
                    'barcode' => '8806095123012',
                    'name' => 'Samsung Galaxy A55 5G 8/256GB (Awesome Navy)',
                    'category_key' => 'phones',
                    'brand_key' => 'samsung',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 1380000,
                    'wholesale_price' => 1320000,
                    'purchase_cost' => 1250000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A2',
                    'warranty' => '1 Year Official Samsung Myanmar Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'Galaxy A55 5G', 'color' => 'Awesome Navy', 'ram_rom' => '8GB/256GB', 'screen' => '120Hz Super AMOLED'],
                    'description' => 'Samsung Galaxy A55 5G Metal Frame, Exynos 1480 Processor, 50MP OIS Camera, IP67 ရေစိုခံစနစ်ပါဝင်သည်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-RDM-N13P-256',
                    'barcode' => '6941812754321',
                    'name' => 'Redmi Note 13 Pro 8/256GB (Midnight Black)',
                    'category_key' => 'phones',
                    'brand_key' => 'xiaomi',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 820000,
                    'wholesale_price' => 785000,
                    'purchase_cost' => 740000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A3',
                    'warranty' => '1 Year Official MI Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'Redmi Note 13 Pro', 'color' => 'Midnight Black', 'ram_rom' => '8GB/256GB', 'camera' => '200MP OIS'],
                    'description' => 'Xiaomi Redmi Note 13 Pro 200MP Ultra-Clear Camera, 67W Turbo Charge, 120Hz AMOLED Display ပါဝင်သည်။',
                ],
                [
                    'sku' => 'MSS-VIVO-Y27S',
                    'barcode' => '6935117823419',
                    'name' => 'Vivo Y27s 8/128GB (Burgundy Black)',
                    'category_key' => 'phones',
                    'brand_key' => 'vivo',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 590000,
                    'wholesale_price' => 565000,
                    'purchase_cost' => 530000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A4',
                    'warranty' => '1 Year Official Vivo Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'Vivo Y27s', 'color' => 'Burgundy Black', 'ram_rom' => '8GB/128GB', 'charging' => '44W FlashCharge'],
                    'description' => 'Vivo Y27s 8GB RAM (+8GB Extended RAM), 44W FlashCharge, Snapdragon 680 Processor ပါဝင်သော စမတ်ဖုန်း။',
                ],
                [
                    'sku' => 'MSS-OPPO-A58',
                    'barcode' => '6943289012345',
                    'name' => 'OPPO A58 8/128GB (Glowing Black)',
                    'category_key' => 'phones',
                    'brand_key' => 'oppo',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 610000,
                    'wholesale_price' => 585000,
                    'purchase_cost' => 545000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'CAB-A4',
                    'warranty' => '1 Year Official OPPO Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['model' => 'OPPO A58', 'color' => 'Glowing Black', 'ram_rom' => '8GB/128GB', 'battery' => '5000mAh / 33W SUPERVOOC'],
                    'description' => 'OPPO A58 33W SUPERVOOC Fast Charge, Dual Stereo Speakers, 50MP AI Camera တပ်ဆင်ထားသည်။',
                ],
                [
                    'sku' => 'MSS-ANK-20W-CUBE',
                    'barcode' => '8480610543218',
                    'name' => 'Anker PowerPort III 20W Fast Charger (Cube White)',
                    'category_key' => 'charging',
                    'brand_key' => 'anker',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 45000,
                    'wholesale_price' => 39000,
                    'purchase_cost' => 32000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CHG',
                    'warranty' => '18 Months Official Anker Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['wattage' => '20W PD Fast Charge', 'connector' => 'Type-C', 'color' => 'White'],
                    'description' => 'Anker 20W PowerPort III Cube အကြမ်းခံ အရည်အသွေးမြင့် Fast Charger ခေါင်း။',
                ],
                [
                    'sku' => 'MSS-BAS-65W-GAN',
                    'barcode' => '6953156289012',
                    'name' => 'Baseus 65W GaN Fast Charger Multi-Port (2C+1U)',
                    'category_key' => 'charging',
                    'brand_key' => 'baseus',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 78000,
                    'wholesale_price' => 69000,
                    'purchase_cost' => 58000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CHG',
                    'warranty' => '6 Months Replacement Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['wattage' => '65W GaN', 'ports' => '2x Type-C + 1x USB-A', 'color' => 'Black'],
                    'description' => 'Baseus 65W GaN Multi-Port Fast Charger Laptop နှင့် ဖုန်းများကို တစ်ပြိုင်နက် အားသွင်းနိုင်သည်။',
                ],
                [
                    'sku' => 'MSS-RMX-CC-100W',
                    'barcode' => '6954851239014',
                    'name' => 'Remax RC-C008 100W Type-C to Type-C Braided Cable (1.2m)',
                    'category_key' => 'charging',
                    'brand_key' => 'remax',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 12500,
                    'wholesale_price' => 9500,
                    'purchase_cost' => 6800,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CBL',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => 'Test on purchase',
                    'specs' => ['connector' => 'Type-C to Type-C', 'power' => '100W PD', 'length' => '1.2m'],
                    'description' => 'Remax 100W အကြမ်းခံ နိုင်လွန်ကျစ်ကြိုး Type-C to Type-C Fast Charging Cable။',
                ],
                [
                    'sku' => 'MSS-RMX-PB-20K',
                    'barcode' => '6954851239999',
                    'name' => 'Remax RPP-296 20000mAh 22.5W Fast Charging Power Bank',
                    'category_key' => 'power',
                    'brand_key' => 'remax',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 58000,
                    'wholesale_price' => 49000,
                    'purchase_cost' => 41000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-PB',
                    'warranty' => '6 Months Warranty',
                    'return_policy' => 'Defective Exchange within 7 Days',
                    'specs' => ['capacity' => '20000mAh', 'output' => '22.5W / PD 20W', 'color' => 'Black'],
                    'description' => 'Remax 20000mAh 22.5W Fast Charge Power Bank LED Display ပါဝင်သည်။',
                ],
                [
                    'sku' => 'MSS-HOCO-W35',
                    'barcode' => '6957531023456',
                    'name' => 'Hoco W35 Wireless Bluetooth ANC Headphone (Silver)',
                    'category_key' => 'audio',
                    'brand_key' => 'hoco',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 42000,
                    'wholesale_price' => 35000,
                    'purchase_cost' => 28000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-AUD',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => 'Defective Exchange within 7 Days',
                    'specs' => ['type' => 'Wireless Bluetooth Over-Ear', 'battery' => '40 Hours Playtime', 'color' => 'Silver'],
                    'description' => 'Hoco W35 Metallic Design Bluetooth Headphone အသံကြည်လင်ပြီး Battery အသုံးခံသော နားကြပ်ကြီး။',
                ],
                [
                    'sku' => 'MSS-BAS-GLS-IP15P',
                    'barcode' => '6953156281115',
                    'name' => 'Baseus 9H Full Coverage Privacy Tempered Glass (iPhone 15 Pro)',
                    'category_key' => 'cases_glass',
                    'brand_key' => 'baseus',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 14000,
                    'wholesale_price' => 9500,
                    'purchase_cost' => 5500,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-GLS',
                    'warranty' => 'Check upon installation',
                    'return_policy' => 'Free installation at counter',
                    'specs' => ['hardness' => '9H Anti-Peeping Privacy', 'model' => 'iPhone 15 Pro'],
                    'description' => 'Baseus Privacy 9H ဘေးလူမမြင်ရသော ကာဗာမှန်ကပ် အကြမ်းခံ။',
                ],
                [
                    'sku' => 'MSS-LCD-IP13PM-AAA',
                    'barcode' => '8935212309131',
                    'name' => 'iPhone 13 Pro Max OLED Screen Assembly (Original AAA+)',
                    'category_key' => 'lcd_screens',
                    'brand_key' => 'apple',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'SPARE',
                    'retail_price' => 385000,
                    'wholesale_price' => 350000,
                    'purchase_cost' => 310000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SPARE-LCD',
                    'warranty' => 'Touch Screen Test Before Install Warranty',
                    'return_policy' => 'Spare Parts — Test Before Take (စစ်ဆေးပြီးမှ ယူပါ)',
                    'specs' => ['model' => 'iPhone 13 Pro Max', 'display_type' => 'Super Retina XDR OLED', 'quality' => 'AAA+ Premium IC'],
                    'description' => 'iPhone 13 Pro Max မူရင်း အဆင့်မှီ 120Hz ProMotion ထောက်ပံ့ပေးသော OLED Touch Screen အပိုပစ္စည်း။',
                ],
                [
                    'sku' => 'MSS-LCD-SAM-A15',
                    'barcode' => '8935212309155',
                    'name' => 'Samsung Galaxy A15 / A25 LCD Touch Screen Assembly',
                    'category_key' => 'lcd_screens',
                    'brand_key' => 'samsung',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'SPARE',
                    'retail_price' => 85000,
                    'wholesale_price' => 75000,
                    'purchase_cost' => 62000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SPARE-LCD',
                    'warranty' => 'Touch Test Before Install',
                    'return_policy' => 'Spare Parts — Test Before Take (စစ်ဆေးပြီးမှ ယူပါ)',
                    'specs' => ['model' => 'Samsung Galaxy A15/A25', 'quality' => 'Original Grade LCD Panel'],
                    'description' => 'Samsung Galaxy A15 LCD Touch Assembly မူရင်း အရောင်အသွေးတောက်ပသော မျက်နှာပြင်။',
                ],
                [
                    'sku' => 'MSS-BAT-IP11-3110',
                    'barcode' => '8935212309117',
                    'name' => 'iPhone 11 Replacement Battery (High Capacity TI Chip)',
                    'category_key' => 'batteries',
                    'brand_key' => 'apple',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'SPARE',
                    'retail_price' => 55000,
                    'wholesale_price' => 46000,
                    'purchase_cost' => 36000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SPARE-BAT',
                    'warranty' => '6 Months Battery Health Warranty',
                    'return_policy' => 'Defective Exchange within 6 Months',
                    'specs' => ['capacity' => '3110mAh', 'chip' => 'Texas Instruments Original IC', 'cycles' => '0 Cycles New'],
                    'description' => 'iPhone 11 Battery Replacement မူရင်း Texas Instruments IC Chip ပါဝင်ပြီး Battery Health 100% ပြသသည်။',
                ],
                [
                    'sku' => 'MSS-PRT-RDM-N13',
                    'barcode' => '8935212309124',
                    'name' => 'Redmi Note 12 / 13 Pro Fast Charging Port PCB Board',
                    'category_key' => 'parts_boards',
                    'brand_key' => 'xiaomi',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'SPARE',
                    'retail_price' => 18000,
                    'wholesale_price' => 14000,
                    'purchase_cost' => 9500,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SPARE-PRT',
                    'warranty' => '1 Month Replacement Warranty',
                    'return_policy' => 'Spare Parts — Test Before Take',
                    'specs' => ['model' => 'Redmi Note 12 / 13 Pro', 'connector' => 'Type-C Sub Board + Mic'],
                    'description' => 'Redmi Note 12/13 Pro အားသွင်းပေါက်နှင့် မိုက်ခရိုဖုန်းပါဝင်သော Charging Sub-board အပိုပစ္စည်း။',
                ],
                [
                    'sku' => 'MSS-SVC-LCD-INSTALL',
                    'barcode' => '8935212308001',
                    'name' => 'Screen / LCD Replacement Service Fee (ဖုန်းမျက်နှာပြင် လဲလှယ်ခ)',
                    'category_key' => 'services',
                    'brand_key' => 'service_center',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'INTAKE',
                    'retail_price' => 25000,
                    'wholesale_price' => 25000,
                    'purchase_cost' => 0,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SERVICE-DESK',
                    'product_type' => 'service',
                    'service_duration' => '45 mins',
                    'warranty' => '3 Months Workmanship Warranty',
                    'return_policy' => 'Service Satisfaction Guaranteed',
                    'description' => 'ကျွမ်းကျင် Technician မှ ဖုန်းမျက်နှာပြင် LCD/Touch Screen အသစ်လဲလှယ်တပ်ဆင်ပေးခြင်း ဝန်ဆောင်မှု။',
                    'is_ecommerce' => false,
                ],
                [
                    'sku' => 'MSS-SVC-BAT-INSTALL',
                    'barcode' => '8935212308002',
                    'name' => 'Battery Replacement Service Fee (ဘက်ထရီ လဲလှယ်ခ)',
                    'category_key' => 'services',
                    'brand_key' => 'service_center',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'INTAKE',
                    'retail_price' => 15000,
                    'wholesale_price' => 15000,
                    'purchase_cost' => 0,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SERVICE-DESK',
                    'product_type' => 'service',
                    'service_duration' => '25 mins',
                    'warranty' => '3 Months Workmanship Warranty',
                    'return_policy' => 'Service Satisfaction Guaranteed',
                    'description' => 'ဖုန်းဘက်ထရီ အသစ်လဲလှယ်ခြင်းနှင့် ဘက်ထရီကျန်းမာရေး စစ်ဆေးပေးခြင်း ဝန်ဆောင်မှု။',
                    'is_ecommerce' => false,
                ],
                [
                    'sku' => 'MSS-SVC-MB-IC',
                    'barcode' => '8935212308003',
                    'name' => 'Motherboard IC Repair & Water Damage Service Fee (ဘုတ်လိုင်း ပြုပြင်ခ)',
                    'category_key' => 'services',
                    'brand_key' => 'service_center',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'INTAKE',
                    'retail_price' => 65000,
                    'wholesale_price' => 65000,
                    'purchase_cost' => 0,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SERVICE-DESK',
                    'product_type' => 'service',
                    'service_duration' => '2 hours',
                    'warranty' => '1 Month Workmanship Warranty',
                    'return_policy' => 'Service Satisfaction Guaranteed',
                    'description' => 'ရေဝင်ဖုန်းများ၊ ပါဝါမတက်သော ဖုန်းများ၏ Motherboard IC လိုင်း ပြုပြင်ပေးခြင်း ဝန်ဆောင်မှု။',
                    'is_ecommerce' => false,
                ],
                [
                    'sku' => 'MSS-SVC-SOFT-FLASH',
                    'barcode' => '8935212308004',
                    'name' => 'Mobile Phone Software Flash / Unlock Service (ဆော့ဖ်ဝဲလ် တင်ခ/လော့ခ်ဖြေခ)',
                    'category_key' => 'services',
                    'brand_key' => 'service_center',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'INTAKE',
                    'retail_price' => 18000,
                    'wholesale_price' => 18000,
                    'purchase_cost' => 0,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SERVICE-DESK',
                    'product_type' => 'service',
                    'service_duration' => '30 mins',
                    'warranty' => '7 Days Support Warranty',
                    'return_policy' => 'Software Support Guaranteed',
                    'description' => 'Logo ရပ်နေသော ဖုန်းများ Firmware တင်ခြင်း၊ Pattern Lock / Passcode ဖြေရှင်းခြင်း ဝန်ဆောင်မှု။',
                    'is_ecommerce' => false,
                ],
                [
                    'sku' => 'MSS-TOOL-24IN1',
                    'barcode' => '8935212307001',
                    'name' => 'Professional 24-in-1 Precision Phone Repair Screwdriver Kit',
                    'category_key' => 'repair_tools',
                    'brand_key' => 'remax',
                    'supplier_key' => 'mingalar_parts',
                    'warehouse_code' => 'SPARE',
                    'retail_price' => 28000,
                    'wholesale_price' => 23000,
                    'purchase_cost' => 17000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'SPARE-TOOL',
                    'warranty' => '6 Months Quality Warranty',
                    'return_policy' => 'Defective exchange within 7 days',
                    'specs' => ['bits' => '24 Magnetic Precision Bits', 'material' => 'S2 Alloy Steel', 'case' => 'Aluminum Magnetic Box'],
                    'description' => 'ဖုန်းနှင့် အီလက်ထရောနစ် ပစ္စည်းများ ပြုပြင်ရန် S2 သံမဏိ သံလိုက်ခေါင်း ၂၄ မျိုးပါ ဝက်အူလှည့်ဘူး။',
                ],

                // =========================================================================
                // CCTV, NETWORK, COMPUTER & INSTALLATION SERVICES (AlinnThit / Myanmar Tech)
                // =========================================================================
                [
                    'sku' => 'MSS-CCTV-HIK-2MP-BLT',
                    'barcode' => '6941234500011',
                    'name' => 'Hikvision 2MP Outdoor IP Bullet Camera (DS-2CD1023G0-I)',
                    'category_key' => 'cctv',
                    'brand_key' => 'hikvision',
                    'supplier_key' => 'security_supplier',
                    'warehouse_code' => 'PROJECT',
                    'retail_price' => 88000,
                    'wholesale_price' => 81000,
                    'purchase_cost' => 69000,
                    'opening_stock' => 20,
                    'reorder_level' => 5,
                    'shelf_location' => 'WH-MAIN',
                    'warranty' => 'CCTV 2 Years Warranty (CCTV ၂ နှစ် အာမခံ)',
                    'return_policy' => 'CCTV — Installation No Refund (CCTV တပ်ဆင်ပြီး ငွေမပြန်ပေးပါ)',
                    'specs' => ['resolution' => '2MP (1920x1080)', 'ir_distance' => '30m Night Vision', 'weatherproof' => 'IP67 Waterproof', 'poe' => 'PoE Supported'],
                    'description' => 'Hikvision 2MP Outdoor IP Bullet ကင်မရာ၊ ညဘက် 30m အထိ ရုပ်ထွက်ကြည်လင်သော Smart IR နှင့် ရေစိုဒဏ်ခံ IP67 အပြည့်အဝ ပါဝင်သည်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-CCTV-DAH-4MP-FULL',
                    'barcode' => '6941234500028',
                    'name' => 'Dahua 4MP Full-Color Smart Dual Light Eyeball Camera (Built-in Mic)',
                    'category_key' => 'cctv',
                    'brand_key' => 'dahua',
                    'supplier_key' => 'security_supplier',
                    'warehouse_code' => 'PROJECT',
                    'retail_price' => 115000,
                    'wholesale_price' => 105000,
                    'purchase_cost' => 91000,
                    'opening_stock' => 15,
                    'reorder_level' => 4,
                    'shelf_location' => 'WH-MAIN',
                    'warranty' => 'CCTV 2 Years Warranty (CCTV ၂ နှစ် အာမခံ)',
                    'return_policy' => 'CCTV — Installation No Refund (CCTV တပ်ဆင်ပြီး ငွေမပြန်ပေးပါ)',
                    'specs' => ['resolution' => '4MP Full-Color', 'audio' => 'Built-in Mic', 'smart_light' => 'Warm LED + IR 30m', 'ai' => 'Human Detection'],
                    'description' => 'Dahua 4MP ညဘက် ကာလာအစစ်ရရှိသော Full-Color အသံဖမ်း မိုက်ခရိုဖုန်းပါ ကင်မရာ။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-CCTV-DAH-NVR-8CH',
                    'barcode' => '6941234500035',
                    'name' => 'Dahua 8-Channel 4K Network Video Recorder (NVR4108HS-4KS2)',
                    'category_key' => 'cctv',
                    'brand_key' => 'dahua',
                    'supplier_key' => 'security_supplier',
                    'warehouse_code' => 'PROJECT',
                    'retail_price' => 195000,
                    'wholesale_price' => 182000,
                    'purchase_cost' => 155000,
                    'opening_stock' => 10,
                    'reorder_level' => 2,
                    'shelf_location' => 'WH-MAIN',
                    'warranty' => 'CCTV 2 Years Warranty (CCTV ၂ နှစ် အာမခံ)',
                    'return_policy' => 'CCTV — Installation No Refund (CCTV တပ်ဆင်ပြီး ငွေမပြန်ပေးပါ)',
                    'specs' => ['channels' => '8 Channels IP Video', 'resolution' => 'Up to 4K Ultra HD', 'hdd_support' => '1 SATA HDD up to 10TB', 'h265' => 'Smart H.265+ Codec'],
                    'description' => 'Dahua 8 လိုင်း 4K ရုပ်ထွက်ထောက်ခံသော Network Recorder စက်၊ ဖုန်းဖြင့် အချိန်မရွေး တိုက်ရိုက် ကြည့်ရှုနိုင်သော P2P Cloud စနစ် ပါဝင်သည်။',
                ],
                [
                    'sku' => 'MSS-CCTV-IMOU-CRUISER',
                    'barcode' => '6941234500042',
                    'name' => 'Imou Cruiser 2 5MP Outdoor 360° PTZ Smart WiFi Camera',
                    'category_key' => 'cctv',
                    'brand_key' => 'imou',
                    'supplier_key' => 'security_supplier',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 125000,
                    'wholesale_price' => 112000,
                    'purchase_cost' => 98000,
                    'opening_stock' => 15,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-01',
                    'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['resolution' => '3K 5MP', 'ptz' => '360 Panoramic View', 'night_vision' => 'Smart Full-Color', 'ai' => 'Vehicle & Human Tracking'],
                    'description' => 'Imou Cruiser 2 5MP ပြင်ပသုံး ၃၆၀ ဒီဂရီ လှည့်နိုင်သော စမတ် WiFi ကင်မရာ၊ လူနှင့်ကား အလိုအလျောက် ခြေရာခံခြင်း ပါဝင်သည်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-CCTV-WD-PURPLE-2TB',
                    'barcode' => '718037855011',
                    'name' => 'Western Digital Purple 2TB Surveillance Hard Drive (24/7 CCTV)',
                    'category_key' => 'power',
                    'brand_key' => 'house',
                    'supplier_key' => 'security_supplier',
                    'warehouse_code' => 'PROJECT',
                    'retail_price' => 195000,
                    'wholesale_price' => 185000,
                    'purchase_cost' => 165000,
                    'opening_stock' => 12,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-SD',
                    'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['capacity' => '2TB', 'interface' => 'SATA 6 Gb/s', 'workload' => '180 TB/year 24/7 DVR/NVR', 'rpm' => '5400 RPM'],
                    'description' => 'WD Purple 2TB ၂၄ နာရီ မနားတမ်း CCTV မှတ်တမ်းတင်ရန် သီးသန့်ထုတ်လုပ်ထားသော AllFrame နည်းပညာသုံး Hard Drive။',
                ],
                [
                    'sku' => 'MSS-NET-TPL-ARCHER-C6',
                    'barcode' => '6935364084516',
                    'name' => 'TP-Link Archer C6 AC1200 Gigabit Dual-Band WiFi Router',
                    'category_key' => 'network',
                    'brand_key' => 'tplink',
                    'supplier_key' => 'computer_supplier',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 92000,
                    'wholesale_price' => 85000,
                    'purchase_cost' => 74000,
                    'opening_stock' => 20,
                    'reorder_level' => 5,
                    'shelf_location' => 'RACK-CBL',
                    'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['wifi' => 'AC1200 (867Mbps at 5GHz + 300Mbps at 2.4GHz)', 'ports' => 'Full Gigabit Ports (1 WAN + 4 LAN)', 'antennas' => '4 External High Gain Antennas'],
                    'description' => 'TP-Link Archer C6 Dual-Band Gigabit WiFi Router၊ MU-MIMO စနစ်ဖြင့် ဖုန်းနှင့် ကွန်ပျူတာများစွာ တစ်ပြိုင်နက် သုံးနိုင်သော စွမ်းဆောင်ရည်မြင့် ရောက်တာ။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-NET-TPL-SG108-8P',
                    'barcode' => '6935364020125',
                    'name' => 'TP-Link TL-SG108 8-Port Gigabit Metal Desktop Switch',
                    'category_key' => 'network',
                    'brand_key' => 'tplink',
                    'supplier_key' => 'computer_supplier',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 58000,
                    'wholesale_price' => 51000,
                    'purchase_cost' => 43000,
                    'opening_stock' => 15,
                    'reorder_level' => 4,
                    'shelf_location' => 'RACK-CBL',
                    'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['ports' => '8 x 10/100/1000Mbps Auto-Negotiation RJ45 Ports', 'case' => 'Durable Metal Casing', 'feature' => 'Plug and Play, Traffic Optimization (QoS)'],
                    'description' => 'TP-Link 8-Port Gigabit သံကိုယ်ထည် Switch၊ အင်တာနက်လိုင်း ခွဲဝေရန်နှင့် ကင်မရာလိုင်းများ ချိတ်ဆက်ရန် အကြမ်းခံ အရည်အသွေးမြင့် ကိရိယာ။',
                ],
                [
                    'sku' => 'MSS-NET-CAT6-305M',
                    'barcode' => '8935212390305',
                    'name' => 'Cat6 Pure Copper UTP Indoor Network Cable Box 305m',
                    'category_key' => 'charging',
                    'brand_key' => 'house',
                    'supplier_key' => 'security_supplier',
                    'warehouse_code' => 'PROJECT',
                    'retail_price' => 175000,
                    'wholesale_price' => 162000,
                    'purchase_cost' => 138000,
                    'opening_stock' => 10,
                    'reorder_level' => 2,
                    'shelf_location' => 'WH-MAIN',
                    'warranty' => 'ရောင်းချပြီး ချက်ချင်း စစ်ဆေးပေးပါ (No Warranty)',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['category' => 'Cat6 UTP', 'conductor' => '0.57mm Pure Solid Copper 100%', 'length' => '305 Meters / 1000 Feet Box'],
                    'description' => 'Cat6 ကြေးနီစစ်စစ် ၁၀၀% ၃၀၅ မီတာ အခွေလိုက်ဘူး၊ CCTV ကင်မရာနှင့် Gigabit ရုံးသုံး ကွန်ရက်လိုင်း ဆွဲရန် အထူးသင့်လျော်သည်။',
                ],
                [
                    'sku' => 'MSS-COM-LENOVO-M70Q',
                    'barcode' => '195892012345',
                    'name' => 'Lenovo ThinkCentre M70q Tiny Desktop PC (Core i5, 8GB RAM, 256GB SSD)',
                    'category_key' => 'computer',
                    'brand_key' => 'lenovo',
                    'supplier_key' => 'computer_supplier',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 1180000,
                    'wholesale_price' => 1120000,
                    'purchase_cost' => 990000,
                    'opening_stock' => 5,
                    'reorder_level' => 1,
                    'shelf_location' => 'CAB-01',
                    'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['cpu' => 'Intel Core i5-10400T', 'ram' => '8GB DDR4 3200MHz', 'storage' => '256GB NVMe M.2 SSD', 'os' => 'Windows 11 Pro Genuine'],
                    'description' => 'Lenovo ThinkCentre နေရာယူမှုအလွန်နည်းသော Mini Desktop PC၊ အရောင်းကောင်တာ POS စနစ်နှင့် ရုံးသုံး လုပ်ငန်းသုံးအတွက် အကြမ်းခံ စွမ်းဆောင်ရည်မြင့်မားသည်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-COM-KST-SSD-512G',
                    'barcode' => '740617302456',
                    'name' => 'Kingston KC600 512GB 2.5" SATA III High Performance SSD',
                    'category_key' => 'power',
                    'brand_key' => 'kingston',
                    'supplier_key' => 'computer_supplier',
                    'warehouse_code' => 'SPARE',
                    'retail_price' => 115000,
                    'wholesale_price' => 102000,
                    'purchase_cost' => 88000,
                    'opening_stock' => 20,
                    'reorder_level' => 4,
                    'shelf_location' => 'CAB-SD',
                    'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['capacity' => '512GB', 'read_speed' => 'Up to 550 MB/s', 'write_speed' => 'Up to 520 MB/s', 'controller' => 'SM2259 Hardware Encryption'],
                    'description' => 'Kingston 512GB 2.5" SATA SSD ကွန်ပျူတာ/လက်ပ်တော့ အဟောင်းများ အမြန်နှုန်း မြှင့်တင်ရန် သင့်လျော်သည်။',
                ],
                [
                    'sku' => 'MSS-SVC-CCTV-4CAM-PKG',
                    'barcode' => '8935212308010',
                    'name' => 'CCTV 4-Camera Complete Installation & Wiring Service Package (၄ လုံးတပ် ဆင်ခ)',
                    'category_key' => 'services',
                    'brand_key' => 'house',
                    'supplier_key' => 'security_supplier',
                    'warehouse_code' => 'PROJECT',
                    'retail_price' => 180000,
                    'wholesale_price' => 180000,
                    'purchase_cost' => 0,
                    'opening_stock' => 1,
                    'reorder_level' => 0,
                    'shelf_location' => 'SERVICE-DESK',
                    'product_type' => 'service',
                    'service_duration' => '1 day',
                    'warranty' => '3 Months Workmanship Warranty',
                    'return_policy' => 'Service Satisfaction Guaranteed (စိတ်တိုင်းမကျပါက ငွေပြန်အမ်း)',
                    'description' => 'ကျွမ်းကျင် CCTV Technician များမှ အိမ်/ဆိုင် အရောက် ကင်မရာ ၄ လုံး ကြိုးသွယ်တန်းခြင်း၊ တပ်ဆင်ခြင်းနှင့် ဖုန်းဆော့ဖ်ဝဲ ချိတ်ဆက်ပေးခြင်း ဝန်ဆောင်မှု Package။',
                    'is_ecommerce' => false,
                ],
                [
                    'sku' => 'MSS-SVC-WIFI-SETUP',
                    'barcode' => '8935212308011',
                    'name' => 'Office / Shop WiFi Router & Network Configuration Service (ကွန်ရက် စနစ်ချိတ်ဆက်ခ)',
                    'category_key' => 'services',
                    'brand_key' => 'house',
                    'supplier_key' => 'computer_supplier',
                    'warehouse_code' => 'PROJECT',
                    'retail_price' => 45000,
                    'wholesale_price' => 45000,
                    'purchase_cost' => 0,
                    'opening_stock' => 1,
                    'reorder_level' => 0,
                    'shelf_location' => 'SERVICE-DESK',
                    'product_type' => 'service',
                    'service_duration' => '2 hours',
                    'warranty' => '1 Month Free Service (၁ လ လက်ခမဲ့ ဝန်ဆောင်မှု)',
                    'return_policy' => 'Service Satisfaction Guaranteed (စိတ်တိုင်းမကျပါက ငွေပြန်အမ်း)',
                    'description' => 'WiFi Router ချိတ်ဆက်ခြင်း၊ Access Point များ တပ်ဆင်ခြင်း၊ Network IP လိုင်းခွဲခြင်းနှင့် လုံခြုံရေး Password သတ်မှတ်ပေးခြင်း ဝန်ဆောင်မှု။',
                    'is_ecommerce' => false,
                ],

                // =========================================================================
                // 1. VARIANT MATRIX PRODUCTS (10 Distinct Types, Stock: 10 each, Multi-Attributes)
                // =========================================================================
                [
                    'sku' => 'MSS-VAR-IP15PM',
                    'barcode' => '8935212301099',
                    'name' => 'iPhone 15 Pro Max (Titanium Matrix Series)',
                    'category_key' => 'phones',
                    'brand_key' => 'apple',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 4450000,
                    'wholesale_price' => 4380000,
                    'purchase_cost' => 4250000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-A1',
                    'product_type' => 'variant',
                    'warranty' => '1 Year Official Apple Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['series' => 'iPhone 15 Pro Max', 'body' => 'Grade 5 Titanium', 'chip' => 'Apple A17 Pro 3nm'],
                    'description' => 'Apple iPhone 15 Pro Max Titanium Frame စမတ်ဖုန်း (Storage နှင့် Color ရွေးချယ်နိုင်သော Variant Matrix ပစ္စည်းဖြစ်ပါသည်)။',
                    'is_featured' => true,
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-IP15PM-256-NT',
                            'name' => '256GB / Natural Titanium',
                            'attributes' => ['Storage' => '256GB', 'Color' => 'Natural Titanium'],
                            'retail_price' => 4450000,
                            'wholesale_price' => 4380000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-IP15PM-256-BT',
                            'name' => '256GB / Blue Titanium',
                            'attributes' => ['Storage' => '256GB', 'Color' => 'Blue Titanium'],
                            'retail_price' => 4450000,
                            'wholesale_price' => 4380000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-IP15PM-512-NT',
                            'name' => '512GB / Natural Titanium',
                            'attributes' => ['Storage' => '512GB', 'Color' => 'Natural Titanium'],
                            'retail_price' => 5150000,
                            'wholesale_price' => 5050000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-IP15PM-1TB-NT',
                            'name' => '1TB / Natural Titanium',
                            'attributes' => ['Storage' => '1TB', 'Color' => 'Natural Titanium'],
                            'retail_price' => 5850000,
                            'wholesale_price' => 5750000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-S24U',
                    'barcode' => '8806095123999',
                    'name' => 'Samsung Galaxy S24 Ultra 5G (Galaxy AI Matrix)',
                    'category_key' => 'phones',
                    'brand_key' => 'samsung',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 3850000,
                    'wholesale_price' => 3750000,
                    'purchase_cost' => 3650000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-A2',
                    'product_type' => 'variant',
                    'warranty' => '1 Year Official Samsung Myanmar Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['series' => 'Galaxy S24 Ultra', 'ai' => 'Galaxy AI Built-in', 'spen' => 'Built-in S-Pen'],
                    'description' => 'Samsung Galaxy S24 Ultra 200MP Camera နှင့် Galaxy AI ပါဝင်သော flagship ဖုန်း (Multi-RAM & Storage Variants)။',
                    'is_featured' => true,
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-S24U-256-GRY',
                            'name' => '12GB+256GB / Titanium Gray',
                            'attributes' => ['RAM/Storage' => '12GB/256GB', 'Color' => 'Titanium Gray'],
                            'retail_price' => 3850000,
                            'wholesale_price' => 3750000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-S24U-512-GRY',
                            'name' => '12GB+512GB / Titanium Gray',
                            'attributes' => ['RAM/Storage' => '12GB/512GB', 'Color' => 'Titanium Gray'],
                            'retail_price' => 4450000,
                            'wholesale_price' => 4350000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-S24U-512-VLT',
                            'name' => '12GB+512GB / Titanium Violet',
                            'attributes' => ['RAM/Storage' => '12GB/512GB', 'Color' => 'Titanium Violet'],
                            'retail_price' => 4450000,
                            'wholesale_price' => 4350000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-MI14U',
                    'barcode' => '6934177723999',
                    'name' => 'Xiaomi 14 Ultra 5G (Leica Photography Matrix)',
                    'category_key' => 'phones',
                    'brand_key' => 'xiaomi',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 3450000,
                    'wholesale_price' => 3350000,
                    'purchase_cost' => 3200000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-A3',
                    'product_type' => 'variant',
                    'warranty' => '1 Year Official MI Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['series' => 'Xiaomi 14 Ultra', 'camera' => 'Leica Quad Camera 1-inch Sensor', 'chip' => 'Snapdragon 8 Gen 3'],
                    'description' => 'Xiaomi 14 Ultra Leica Pro Optical Camera ဖုန်းအသစ် (RAM/Storage & Color Variant Matrix)။',
                    'is_featured' => true,
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-MI14U-512-BLK',
                            'name' => '16GB+512GB / Vegan Leather Black',
                            'attributes' => ['RAM/Storage' => '16GB/512GB', 'Color' => 'Black'],
                            'retail_price' => 3450000,
                            'wholesale_price' => 3350000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-MI14U-512-WHT',
                            'name' => '16GB+512GB / Vegan Leather White',
                            'attributes' => ['RAM/Storage' => '16GB/512GB', 'Color' => 'White'],
                            'retail_price' => 3450000,
                            'wholesale_price' => 3350000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-MI14U-1TB-TI',
                            'name' => '16GB+1TB / Titanium Edition',
                            'attributes' => ['RAM/Storage' => '16GB/1TB', 'Color' => 'Titanium'],
                            'retail_price' => 3950000,
                            'wholesale_price' => 3850000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-IPAD-AIR-M2',
                    'barcode' => '8935212301122',
                    'name' => 'Apple iPad Air 11-inch M2 (Storage & Network Matrix)',
                    'category_key' => 'phones',
                    'brand_key' => 'apple',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 2150000,
                    'wholesale_price' => 2050000,
                    'purchase_cost' => 1950000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-T1',
                    'product_type' => 'variant',
                    'warranty' => '1 Year Official Apple Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['series' => 'iPad Air 11 M2', 'screen' => '11-inch Liquid Retina', 'chip' => 'Apple M2 Chip'],
                    'description' => 'Apple iPad Air 11-inch M2 Chip အသစ်စက်စက် (Storage နှင့် Wi-Fi/Cellular Variants)။',
                    'is_featured' => true,
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-IPAD-128-WF-GRY',
                            'name' => '128GB Wi-Fi / Space Gray',
                            'attributes' => ['Storage' => '128GB', 'Connectivity' => 'Wi-Fi', 'Color' => 'Space Gray'],
                            'retail_price' => 2150000,
                            'wholesale_price' => 2050000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-IPAD-128-WF-SLV',
                            'name' => '128GB Wi-Fi / Starlight',
                            'attributes' => ['Storage' => '128GB', 'Connectivity' => 'Wi-Fi', 'Color' => 'Starlight'],
                            'retail_price' => 2150000,
                            'wholesale_price' => 2050000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-IPAD-256-CEL-GRY',
                            'name' => '256GB Wi-Fi + Cellular / Space Gray',
                            'attributes' => ['Storage' => '256GB', 'Connectivity' => 'Wi-Fi + Cellular 5G', 'Color' => 'Space Gray'],
                            'retail_price' => 2850000,
                            'wholesale_price' => 2750000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-AW-S9',
                    'barcode' => '8935212301133',
                    'name' => 'Apple Watch Series 9 GPS (Size & Band Color Matrix)',
                    'category_key' => 'audio',
                    'brand_key' => 'apple',
                    'supplier_key' => 'yangon_mobile',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 1350000,
                    'wholesale_price' => 1280000,
                    'purchase_cost' => 1190000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-W1',
                    'product_type' => 'variant',
                    'warranty' => '1 Year Official Apple Warranty',
                    'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                    'specs' => ['series' => 'Apple Watch Series 9', 'features' => 'Double Tap Gesture, ECG, Blood Oxygen'],
                    'description' => 'Apple Watch Series 9 GPS Smart Watch (အရွယ်အစား 41mm/45mm နှင့် ကြိုးအရောင် Variants)။',
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-AW9-41-MDN',
                            'name' => '41mm GPS / Midnight Sport Band',
                            'attributes' => ['Size' => '41mm', 'Color' => 'Midnight'],
                            'retail_price' => 1350000,
                            'wholesale_price' => 1280000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-AW9-41-STL',
                            'name' => '41mm GPS / Starlight Sport Band',
                            'attributes' => ['Size' => '41mm', 'Color' => 'Starlight'],
                            'retail_price' => 1350000,
                            'wholesale_price' => 1280000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-AW9-45-MDN',
                            'name' => '45mm GPS / Midnight Sport Band',
                            'attributes' => ['Size' => '45mm', 'Color' => 'Midnight'],
                            'retail_price' => 1480000,
                            'wholesale_price' => 1400000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-ANK-MAGGO',
                    'barcode' => '8480610549999',
                    'name' => 'Anker MagGo Qi2 15W Magnetic Wireless Power Bank',
                    'category_key' => 'power',
                    'brand_key' => 'anker',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 115000,
                    'wholesale_price' => 98000,
                    'purchase_cost' => 82000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'RACK-PB',
                    'product_type' => 'variant',
                    'warranty' => '18 Months Official Anker Warranty',
                    'return_policy' => 'Defective Exchange within Warranty',
                    'specs' => ['standard' => 'Qi2 Certified 15W MagSafe', 'display' => 'Smart Digital Display'],
                    'description' => 'Anker MagGo Qi2 15W Ultra-Fast Magnetic Wireless Power Bank (Capacity & Color Multi-Variants)။',
                    'is_featured' => true,
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-MAGGO-5K-BLK',
                            'name' => '5,000mAh Slim / Matte Black',
                            'attributes' => ['Capacity' => '5000mAh', 'Color' => 'Matte Black'],
                            'retail_price' => 115000,
                            'wholesale_price' => 98000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-MAGGO-5K-WHT',
                            'name' => '5,000mAh Slim / Shell White',
                            'attributes' => ['Capacity' => '5000mAh', 'Color' => 'Shell White'],
                            'retail_price' => 115000,
                            'wholesale_price' => 98000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-MAGGO-10K-BLK',
                            'name' => '10,000mAh Fast / Matte Black',
                            'attributes' => ['Capacity' => '10000mAh', 'Color' => 'Matte Black'],
                            'retail_price' => 165000,
                            'wholesale_price' => 145000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-RMX-CABLE',
                    'barcode' => '6954851239988',
                    'name' => 'Remax PD Armor Braided Fast Cable (Length & Port Matrix)',
                    'category_key' => 'charging',
                    'brand_key' => 'remax',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 12500,
                    'wholesale_price' => 9500,
                    'purchase_cost' => 6800,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'RACK-CBL',
                    'product_type' => 'variant',
                    'warranty' => '3 Months Replacement Warranty',
                    'return_policy' => 'Test on purchase',
                    'specs' => ['material' => 'Military Grade Nylon Braided', 'smart_chip' => 'E-Marker Chip'],
                    'description' => 'Remax Armor နိုင်လွန်ကျစ်ကြိုး Fast Charging & High Speed Data Transfer Cable (အလျားနှင့် ခေါင်းရွေးချယ်စရာ Variants)။',
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-CBL-1M-TC',
                            'name' => '1.0 Meter / Type-C to Type-C 100W',
                            'attributes' => ['Length' => '1.0m', 'Connector' => 'Type-C to Type-C'],
                            'retail_price' => 12500,
                            'wholesale_price' => 9500,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-CBL-1M-CL',
                            'name' => '1.0 Meter / Type-C to Lightning 30W',
                            'attributes' => ['Length' => '1.0m', 'Connector' => 'Type-C to Lightning'],
                            'retail_price' => 13500,
                            'wholesale_price' => 10500,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-CBL-2M-TC',
                            'name' => '2.0 Meter / Type-C to Type-C 100W',
                            'attributes' => ['Length' => '2.0m', 'Connector' => 'Type-C to Type-C'],
                            'retail_price' => 16500,
                            'wholesale_price' => 13000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-HOCO-CASE',
                    'barcode' => '6957531029988',
                    'name' => 'Hoco Liquid Silicone Magnetic Case (Multi-Model Matrix)',
                    'category_key' => 'cases_glass',
                    'brand_key' => 'hoco',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 18000,
                    'wholesale_price' => 14000,
                    'purchase_cost' => 9500,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'RACK-CASE',
                    'product_type' => 'variant',
                    'warranty' => 'Quality Verified',
                    'return_policy' => 'Test fitting on purchase',
                    'specs' => ['material' => 'Food Grade Liquid Silicone + Microfiber Lining', 'magsafe' => 'N52 Strong Magnets'],
                    'description' => 'Hoco မာကျောစေးပိုင် Liquid Silicone MagSafe ကာဗာ (Phone Model နှင့် အရောင် ရွေးချယ်နိုင်သော Matrix ပစ္စည်း)။',
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-CASE-IP15-BLU',
                            'name' => 'iPhone 15 / Deep Blue',
                            'attributes' => ['Model' => 'iPhone 15', 'Color' => 'Deep Blue'],
                            'retail_price' => 18000,
                            'wholesale_price' => 14000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-CASE-IP15-BLK',
                            'name' => 'iPhone 15 / Matte Black',
                            'attributes' => ['Model' => 'iPhone 15', 'Color' => 'Matte Black'],
                            'retail_price' => 18000,
                            'wholesale_price' => 14000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-CASE-IP15PM-BLU',
                            'name' => 'iPhone 15 Pro Max / Deep Blue',
                            'attributes' => ['Model' => 'iPhone 15 Pro Max', 'Color' => 'Deep Blue'],
                            'retail_price' => 20000,
                            'wholesale_price' => 15000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-CASE-IP15PM-BLK',
                            'name' => 'iPhone 15 Pro Max / Matte Black',
                            'attributes' => ['Model' => 'iPhone 15 Pro Max', 'Color' => 'Matte Black'],
                            'retail_price' => 20000,
                            'wholesale_price' => 15000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-JOY-MOUNT',
                    'barcode' => '6957531029999',
                    'name' => 'Joyroom MagSafe Fast Car Mount & Charger Matrix',
                    'category_key' => 'charging',
                    'brand_key' => 'joyroom',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 38000,
                    'wholesale_price' => 32000,
                    'purchase_cost' => 24000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'RACK-STND',
                    'product_type' => 'variant',
                    'warranty' => '6 Months Replacement Warranty',
                    'return_policy' => 'Defective exchange within 7 days',
                    'specs' => ['standard' => '15W MagSafe Fast Wireless Car Charger', 'rotation' => '360 Degree Ball Joint'],
                    'description' => 'Joyroom ကားတင် MagSafe 15W Fast Wireless Charger (Dashboard / Air Vent စွဲမော်ဒယ် Variants)။',
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-JOY-VENT-GRY',
                            'name' => 'Air Vent Hook Clip / Titanium Gray',
                            'attributes' => ['Mount Type' => 'Air Vent Hook Clip', 'Color' => 'Titanium Gray'],
                            'retail_price' => 38000,
                            'wholesale_price' => 32000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-JOY-DASH-GRY',
                            'name' => 'Dashboard Suction Base / Titanium Gray',
                            'attributes' => ['Mount Type' => 'Dashboard Suction Base', 'Color' => 'Titanium Gray'],
                            'retail_price' => 42000,
                            'wholesale_price' => 35000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],
                [
                    'sku' => 'MSS-VAR-KNG-SD',
                    'barcode' => '740617309999',
                    'name' => 'Kingston Canvas React Plus High-Speed MicroSD Matrix',
                    'category_key' => 'power',
                    'brand_key' => 'kingston',
                    'supplier_key' => 'mandalay_tech',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 45000,
                    'wholesale_price' => 39000,
                    'purchase_cost' => 32000,
                    'opening_stock' => 10,
                    'reorder_level' => 3,
                    'shelf_location' => 'CAB-SD',
                    'product_type' => 'variant',
                    'warranty' => 'Lifetime Official Warranty',
                    'return_policy' => 'Lifetime Warranty for Data / Card replacement',
                    'specs' => ['speed' => 'UHS-II V90 Up to 280MB/s', 'support' => '4K / 8K Video Recording, Mobile, Drone'],
                    'description' => 'Kingston Canvas React Plus UHS-II အလွန်မြန် မန်မိုရီကတ် (Capacity Variants 64GB / 128GB / 256GB)။',
                    'variants' => [
                        [
                            'sku' => 'MSS-VAR-KNG-64GB',
                            'name' => '64GB UHS-II V90 (Up to 280MB/s)',
                            'attributes' => ['Capacity' => '64GB', 'Speed' => '280MB/s V90'],
                            'retail_price' => 45000,
                            'wholesale_price' => 39000,
                            'quantity_on_hand' => 10,
                            'is_default' => true,
                        ],
                        [
                            'sku' => 'MSS-VAR-KNG-128GB',
                            'name' => '128GB UHS-II V90 (Up to 280MB/s)',
                            'attributes' => ['Capacity' => '128GB', 'Speed' => '280MB/s V90'],
                            'retail_price' => 78000,
                            'wholesale_price' => 69000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                        [
                            'sku' => 'MSS-VAR-KNG-256GB',
                            'name' => '256GB UHS-II V90 (Up to 280MB/s)',
                            'attributes' => ['Capacity' => '256GB', 'Speed' => '280MB/s V90'],
                            'retail_price' => 145000,
                            'wholesale_price' => 130000,
                            'quantity_on_hand' => 10,
                            'is_default' => false,
                        ],
                    ],
                ],

                // =========================================================================
                // 2. DIGITAL PRODUCTS & CODES (Delivery Method: SMS, Viber, E-Pin, Key)
                // =========================================================================
                [
                    'sku' => 'MSS-DIG-ITUNES-10',
                    'barcode' => '0190199123010',
                    'name' => 'Apple iTunes & App Store $10 Gift Card (US Region Digital Code)',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'apple_gift',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 42000,
                    'wholesale_price' => 40000,
                    'purchase_cost' => 38500,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant SMS / Viber',
                    'warranty' => '100% Genuine Digital Code Guarantee',
                    'return_policy' => 'Non-refundable once code is redeemed',
                    'specs' => ['region' => 'United States (US)', 'denomination' => '$10 USD', 'delivery' => 'Digital PIN Code'],
                    'description' => 'US Apple ID များတွင် App, Games, iCloud+ နှင့် Apple Music ဝယ်ယူရန် တရားဝင် $10 ဒစ်ဂျစ်တယ်ကုတ်။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-DIG-ITUNES-25',
                    'barcode' => '0190199123025',
                    'name' => 'Apple iTunes & App Store $25 Gift Card (US Region Digital Code)',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'apple_gift',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 105000,
                    'wholesale_price' => 100000,
                    'purchase_cost' => 96000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant SMS / Viber',
                    'warranty' => '100% Genuine Digital Code Guarantee',
                    'return_policy' => 'Non-refundable once code is redeemed',
                    'specs' => ['region' => 'United States (US)', 'denomination' => '$25 USD', 'delivery' => 'Digital PIN Code'],
                    'description' => 'US Apple ID များတွင် အသုံးပြုနိုင်သော တရားဝင် $25 ဒစ်ဂျစ်တယ် လက်ဆောင်ကတ်ကုတ်။',
                ],
                [
                    'sku' => 'MSS-DIG-GPLAY-10',
                    'barcode' => '0810014123010',
                    'name' => 'Google Play $10 Gift Card (US Region Digital Code)',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'google_play',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 42000,
                    'wholesale_price' => 40000,
                    'purchase_cost' => 38500,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant SMS / Viber',
                    'warranty' => '100% Genuine Digital Code Guarantee',
                    'return_policy' => 'Non-refundable once code is redeemed',
                    'specs' => ['region' => 'United States (US)', 'denomination' => '$10 USD', 'delivery' => 'Digital Redeem Code'],
                    'description' => 'Google Play Store တွင် Games, In-app purchases, Movies ဝယ်ယူရန် တရားဝင် $10 ဒစ်ဂျစ်တယ်ကုတ်။',
                ],
                [
                    'sku' => 'MSS-DIG-MLBB-706',
                    'barcode' => '8935212399706',
                    'name' => 'Mobile Legends: Bang Bang 706 Diamonds (Direct ID Top-Up / Pin)',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'moonton',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 38000,
                    'wholesale_price' => 36000,
                    'purchase_cost' => 34500,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant Direct Topup / SMS',
                    'warranty' => 'Instant Official Diamond Top-Up',
                    'return_policy' => 'Direct in-game credit - Final Sale',
                    'specs' => ['game' => 'Mobile Legends: Bang Bang', 'amount' => '706 Diamonds (625+81 Bonus)', 'type' => 'User ID + Zone ID Recharge'],
                    'description' => 'MLBB 706 Diamonds အကောင့်အတွင်းသို့ တိုက်ရိုက်ဖြည့်သွင်းခြင်း (သို့မဟုတ်) Redeem PIN Code ပေးပို့ခြင်း။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-DIG-MLBB-2195',
                    'barcode' => '8935212392195',
                    'name' => 'Mobile Legends: Bang Bang 2195 Diamonds (Direct ID Top-Up / Pin)',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'moonton',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 115000,
                    'wholesale_price' => 109000,
                    'purchase_cost' => 105000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant Direct Topup / SMS',
                    'warranty' => 'Instant Official Diamond Top-Up',
                    'return_policy' => 'Direct in-game credit - Final Sale',
                    'specs' => ['game' => 'Mobile Legends: Bang Bang', 'amount' => '2195 Diamonds (1860+335 Bonus)', 'type' => 'User ID + Zone ID Recharge'],
                    'description' => 'MLBB 2195 Diamonds အကောင့်အတွင်းသို့ ချက်ချင်းတိုက်ရိုက်ဖြည့်သွင်းခြင်း ဝန်ဆောင်မှု။',
                ],
                [
                    'sku' => 'MSS-DIG-PUBG-660',
                    'barcode' => '8935212390660',
                    'name' => 'PUBG Mobile 660 UC (Global / Myanmar Direct Code)',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'pubg',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 36000,
                    'wholesale_price' => 34000,
                    'purchase_cost' => 32500,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant SMS / Viber',
                    'warranty' => 'Official PUBG UC Voucher',
                    'return_policy' => 'Non-refundable once code is generated',
                    'specs' => ['game' => 'PUBG Mobile', 'amount' => '600 + 60 Bonus UC (660 Total)', 'type' => 'Midasbuy Redeem Code'],
                    'description' => 'PUBG Mobile Royale Pass ဝယ်ယူရန်နှင့် သေနတ် Skin များ ဖောက်ရန် 660 UC တရားဝင် ဒစ်ဂျစ်တယ်ကုတ်။',
                ],
                [
                    'sku' => 'MSS-DIG-MPT-10K',
                    'barcode' => '8935212391001',
                    'name' => 'MPT 10,000 MMK Mobile Top-up E-Pin Digital Code',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'mpt',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 10000,
                    'wholesale_price' => 9850,
                    'purchase_cost' => 9700,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant SMS / POS Printed Slip',
                    'warranty' => '100% Genuine Operator Code',
                    'return_policy' => 'Instant balance top-up',
                    'specs' => ['operator' => 'MPT Myanmar', 'denomination' => '10,000 MMK', 'type' => '16-Digit E-Pin / Direct Top-up'],
                    'description' => 'MPT ဖုန်းလိုင်းများအတွက် ၁၀,၀၀၀ ကျပ်တန် ဒစ်ဂျစ်တယ် ငွေဖြည့်ကုတ် (POS Print / SMS ဖြင့် ပေးပို့နိုင်သည်)။',
                    'is_featured' => true,
                ],
                [
                    'sku' => 'MSS-DIG-ATOM-10K',
                    'barcode' => '8935212391002',
                    'name' => 'ATOM 10,000 MMK Mobile Top-up E-Pin Digital Code',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'atom',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 10000,
                    'wholesale_price' => 9850,
                    'purchase_cost' => 9700,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant SMS / POS Printed Slip',
                    'warranty' => '100% Genuine Operator Code',
                    'return_policy' => 'Instant balance top-up',
                    'specs' => ['operator' => 'ATOM Myanmar', 'denomination' => '10,000 MMK', 'type' => '16-Digit E-Pin / Direct Top-up'],
                    'description' => 'ATOM ဖုန်းလိုင်းများအတွက် ၁၀,၀၀၀ ကျပ်တန် ဒစ်ဂျစ်တယ် ငွေဖြည့်ကုတ်။',
                ],
                [
                    'sku' => 'MSS-DIG-ICLOUD-50G',
                    'barcode' => '8935212395000',
                    'name' => 'iCloud+ 50GB 1-Month Subscription Digital Voucher',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'apple_gift',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 5500,
                    'wholesale_price' => 5000,
                    'purchase_cost' => 4800,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant Viber / Email',
                    'warranty' => 'Official Apple iCloud Plan',
                    'return_policy' => 'Digital activation guaranteed',
                    'specs' => ['storage' => '50GB Cloud Storage', 'duration' => '1 Month', 'features' => 'iCloud Private Relay, Hide My Email'],
                    'description' => 'iPhone/iPad Storage မလောက်သူများအတွက် iCloud+ 50GB ၁ လစာ အသုံးပြုခွင့် ဒစ်ဂျစ်တယ်ဘောက်ချာ။',
                ],
                [
                    'sku' => 'MSS-DIG-KASP-1Y',
                    'barcode' => '8935212399001',
                    'name' => 'Kaspersky Internet Security for Android (1-Device 1-Year License Key)',
                    'category_key' => 'digital_codes',
                    'brand_key' => 'kaspersky',
                    'supplier_key' => 'digital_distributor',
                    'warehouse_code' => 'SHOW',
                    'retail_price' => 25000,
                    'wholesale_price' => 21000,
                    'purchase_cost' => 18000,
                    'opening_stock' => 30,
                    'reorder_level' => 5,
                    'shelf_location' => 'DIGITAL-VAULT',
                    'product_type' => 'digital',
                    'digital_delivery_method' => 'Instant Email / SMS Key',
                    'warranty' => '1 Year Official Antivirus License',
                    'return_policy' => 'Activation Key Warranty',
                    'specs' => ['platform' => 'Android Phone & Tablet', 'duration' => '365 Days License', 'features' => 'Real-time Antivirus, Anti-Phishing, App Lock'],
                    'description' => 'Android ဖုန်းများအတွက် ဗိုင်းရပ်စ်နှင့် ငွေကြေးဆိုင်ရာ အချက်အလက် ကာကွယ်ရေး Kaspersky ၁ နှစ်စာ တရားဝင် License Key။',
                ],
            ],
        );
    }

    private function cctvNetworkComputer(): array
    {
        return $this->scenario(
            store: ['name' => 'CCTV Network Computer Demo', 'business_type' => 'cctv_network_computer', 'slug' => 'cctv-network-computer-demo', 'viber_number' => '09170000001', 'telegram_username' => 'cctv_network_demo'],
            setting: ['store_name' => 'CCTV Network Computer Demo', 'tagline' => 'CCTV, Network, Computer Sales & Installation', 'phone' => '09170000001'],
            users: [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'CCTV Network Manager', 'phone' => '09170000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Installation Technician', 'phone' => '09170000002', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'CCTV Sales Cashier', 'phone' => '09160000003', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'CCTV Stock Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'CCTV Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'Daw Aye (Wholesale Partner)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'Ma Su (Retail Customer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            warehouses: [
                ['name' => 'Showroom', 'code' => 'SHOW'],
                ['name' => 'Project Stock', 'code' => 'PROJECT'],
                ['name' => 'Service Spare Parts', 'code' => 'SPARE'],
            ],
            categories: [
                ['key' => 'cctv', 'name' => 'CCTV & Security', 'code' => 'CCTV', 'slug' => 'cctv-security', 'legacy_slugs' => ['cctv-cameras']],
                ['key' => 'network', 'name' => 'Network & Connectivity', 'code' => 'NET', 'slug' => 'network-connectivity', 'legacy_slugs' => ['network-devices']],
                ['key' => 'computer', 'name' => 'Computer Peripherals', 'code' => 'ELEC', 'slug' => 'computer-peripherals', 'legacy_slugs' => ['computers']],
                ['key' => 'cables', 'name' => 'Cable & Charger', 'code' => 'CBCH', 'slug' => 'cable-charger', 'legacy_slugs' => ['cables-accessories']],
                ['key' => 'service', 'name' => 'Service & Repair', 'code' => 'SVC', 'slug' => 'service-repair', 'legacy_slugs' => ['installation-services']],
            ],
            brands: [
                ['key' => 'hikvision', 'name' => 'Hikvision', 'code' => 'HIK', 'slug' => 'hikvision'],
                ['key' => 'dahua', 'name' => 'Dahua', 'code' => 'DAHUA', 'slug' => 'dahua'],
                ['key' => 'tplink', 'name' => 'TP-Link', 'code' => 'TPLINK', 'slug' => 'tp-link'],
                ['key' => 'lenovo', 'name' => 'Lenovo', 'code' => 'LNV', 'slug' => 'lenovo'],
                ['key' => 'house', 'name' => 'Project Service', 'code' => 'PRJ_SVC', 'slug' => 'project-service'],
            ],
            suppliers: [
                ['key' => 'security_supplier', 'name' => 'Security System Wholesale', 'phone' => '09270000001'],
                ['key' => 'computer_supplier', 'name' => 'Computer & Network Distributor', 'phone' => '09270000002'],
            ],
            products: [
                ['sku' => 'CNC-HIK-2MP-DOME', 'name' => 'Hikvision 2MP Dome Camera', 'category_key' => 'cctv', 'brand_key' => 'hikvision', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 85000, 'wholesale_price' => 79000, 'purchase_cost' => 65000, 'opening_stock' => 20, 'reorder_level' => 6, 'shelf_location' => 'WH-MAIN', 'warranty' => 'CCTV 2 Years Warranty (CCTV ၂ နှစ် အာမခံ)', 'return_policy' => 'CCTV — Installation No Refund (CCTV တပ်ဆင်ပြီး ငွေမပြန်ပေးပါ)'],
                ['sku' => 'CNC-DAH-NVR-8CH', 'name' => 'Dahua 8CH NVR', 'category_key' => 'cctv', 'brand_key' => 'dahua', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 185000, 'wholesale_price' => 175000, 'purchase_cost' => 148000, 'opening_stock' => 8, 'reorder_level' => 2, 'shelf_location' => 'WH-MAIN', 'warranty' => 'CCTV 2 Years Warranty (CCTV ၂ နှစ် အာမခံ)', 'return_policy' => 'CCTV — Installation No Refund (CCTV တပ်ဆင်ပြီး ငွေမပြန်ပေးပါ)'],
                ['sku' => 'CNC-TPL-ARCHER-C6', 'name' => 'TP-Link Archer C6 Router', 'category_key' => 'network', 'brand_key' => 'tplink', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'SHOW', 'retail_price' => 88000, 'wholesale_price' => 83000, 'purchase_cost' => 70000, 'opening_stock' => 15, 'reorder_level' => 4, 'shelf_location' => 'WH-MAIN', 'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)', 'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)'],
                ['sku' => 'CNC-CAT6-305M', 'name' => 'CAT6 Cable Box 305m', 'category_key' => 'cables', 'brand_key' => 'house', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 165000, 'wholesale_price' => 155000, 'purchase_cost' => 130000, 'opening_stock' => 10, 'reorder_level' => 3, 'shelf_location' => 'WH-MAIN', 'warranty' => 'ရောင်းချပြီး ချက်ချင်း စစ်ဆေးပေးပါ (No Warranty)', 'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)'],
                ['sku' => 'CNC-LEN-I5-DESKTOP', 'name' => 'Lenovo i5 Desktop Set', 'category_key' => 'computer', 'brand_key' => 'lenovo', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'SHOW', 'retail_price' => 1250000, 'wholesale_price' => 1190000, 'purchase_cost' => 1080000, 'opening_stock' => 5, 'reorder_level' => 1, 'shelf_location' => 'WH-MAIN', 'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)', 'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)'],
                ['sku' => 'CNC-SSD-512-SATA', 'name' => '512GB SATA SSD', 'category_key' => 'computer', 'brand_key' => 'house', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'SPARE', 'retail_price' => 95000, 'wholesale_price' => 90000, 'purchase_cost' => 76000, 'opening_stock' => 18, 'reorder_level' => 5, 'shelf_location' => 'CAB-SD', 'warranty' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)', 'return_policy' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)'],
                ['sku' => 'CNC-SVC-CCTV-4CAM', 'name' => '4 Camera Installation Package', 'category_key' => 'service', 'brand_key' => 'house', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 250000, 'wholesale_price' => 250000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'service_duration' => '1 day', 'shelf_location' => 'SERVICE-DESK', 'warranty' => '3 Months Workmanship Warranty', 'return_policy' => 'Service Satisfaction Guaranteed', 'is_ecommerce' => false],
                ['sku' => 'CNC-SVC-WIFI-SETUP', 'name' => 'WiFi Network Setup Service', 'category_key' => 'service', 'brand_key' => 'house', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 60000, 'wholesale_price' => 60000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'service_duration' => '3 hr', 'shelf_location' => 'SERVICE-DESK', 'warranty' => '3 Months Workmanship Warranty', 'return_policy' => 'Service Satisfaction Guaranteed', 'is_ecommerce' => false],
            ],
        );
    }

    private function pharmacy(): array
    {
        return $this->scenario(
            store: ['name' => 'Pharmacy Demo', 'business_type' => 'pharmacy', 'slug' => 'pharmacy-demo', 'viber_number' => '09180000001', 'telegram_username' => 'pharmacy_demo'],
            setting: ['store_name' => 'Pharmacy Demo', 'tagline' => 'ဆေးဝါး၊ Supplement နှင့် Medical Device', 'phone' => '09180000001'],
            users: [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'Pharmacy Manager', 'phone' => '09180000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Pharmacy Staff & Pharmacist', 'phone' => '09180000002', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'Pharmacy Cashier', 'phone' => '09160000003', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'Pharmacy Stock Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'Pharmacy Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'Daw Aye (Wholesale Clinic)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'Ma Su (Retail Customer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            warehouses: [
                ['name' => 'Main Shelf', 'code' => 'SHELF'],
                ['name' => 'Cold / Controlled Stock', 'code' => 'COLD'],
                ['name' => 'Expired / Quarantine', 'code' => 'QUAR'],
            ],
            categories: [
                ['key' => 'medicine', 'name' => 'Medicine', 'slug' => 'medicine'],
                ['key' => 'supplement', 'name' => 'Supplements', 'slug' => 'supplements'],
                ['key' => 'device', 'name' => 'Medical Devices', 'slug' => 'medical-devices'],
                ['key' => 'personal', 'name' => 'Personal Care', 'slug' => 'personal-care'],
            ],
            brands: [
                ['key' => 'demo_pharma', 'name' => 'Demo Pharma', 'slug' => 'demo-pharma'],
                ['key' => 'health_plus', 'name' => 'Health Plus', 'slug' => 'health-plus'],
                ['key' => 'care', 'name' => 'Care', 'slug' => 'care'],
            ],
            suppliers: [
                ['key' => 'medicine_supplier', 'name' => 'Medicine Wholesale Demo', 'phone' => '09280000001'],
                ['key' => 'device_supplier', 'name' => 'Medical Device Supplier', 'phone' => '09280000002'],
            ],
            products: [
                ['sku' => 'PHA-PARA-500', 'name' => 'Paracetamol 500mg Strip', 'category_key' => 'medicine', 'brand_key' => 'demo_pharma', 'supplier_key' => 'medicine_supplier', 'warehouse_code' => 'SHELF', 'retail_price' => 1200, 'wholesale_price' => 1000, 'purchase_cost' => 650, 'opening_stock' => 300, 'reorder_level' => 80, 'specs' => ['batch' => 'PHA-DEMO-01', 'expiry' => '2028-03-31']],
                ['sku' => 'PHA-ORS-SACHET', 'name' => 'ORS Sachet', 'category_key' => 'medicine', 'brand_key' => 'health_plus', 'supplier_key' => 'medicine_supplier', 'warehouse_code' => 'SHELF', 'retail_price' => 500, 'wholesale_price' => 420, 'purchase_cost' => 250, 'opening_stock' => 500, 'reorder_level' => 120, 'specs' => ['batch' => 'PHA-DEMO-02', 'expiry' => '2028-08-31']],
                ['sku' => 'PHA-VITC-100', 'name' => 'Vitamin C 100 Tablets', 'category_key' => 'supplement', 'brand_key' => 'health_plus', 'supplier_key' => 'medicine_supplier', 'warehouse_code' => 'SHELF', 'retail_price' => 18500, 'wholesale_price' => 17000, 'purchase_cost' => 13200, 'opening_stock' => 45, 'reorder_level' => 10],
                ['sku' => 'PHA-THERMO-DIGI', 'name' => 'Digital Thermometer', 'category_key' => 'device', 'brand_key' => 'care', 'supplier_key' => 'device_supplier', 'warehouse_code' => 'SHELF', 'retail_price' => 14500, 'wholesale_price' => 13200, 'purchase_cost' => 9500, 'opening_stock' => 25, 'reorder_level' => 6],
                ['sku' => 'PHA-BP-MONITOR', 'name' => 'Blood Pressure Monitor', 'category_key' => 'device', 'brand_key' => 'care', 'supplier_key' => 'device_supplier', 'warehouse_code' => 'MAIN', 'retail_price' => 95000, 'wholesale_price' => 89000, 'purchase_cost' => 72000, 'opening_stock' => 8, 'reorder_level' => 2],
                ['sku' => 'PHA-INSULIN-COOL', 'name' => 'Cold Storage Medicine Demo', 'category_key' => 'medicine', 'brand_key' => 'demo_pharma', 'supplier_key' => 'medicine_supplier', 'warehouse_code' => 'COLD', 'retail_price' => 32000, 'wholesale_price' => 30000, 'purchase_cost' => 25000, 'opening_stock' => 16, 'reorder_level' => 4, 'specs' => ['storage' => 'Cold stock demo', 'expiry' => '2027-12-31']],
                ['sku' => 'PHA-MASK-50', 'name' => 'Face Mask Box 50 pcs', 'category_key' => 'personal', 'brand_key' => 'care', 'supplier_key' => 'device_supplier', 'warehouse_code' => 'MAIN', 'retail_price' => 6500, 'wholesale_price' => 5800, 'purchase_cost' => 3900, 'opening_stock' => 60, 'reorder_level' => 15],
                ['sku' => 'PHA-HANDSAN-500', 'name' => 'Hand Sanitizer 500ml', 'category_key' => 'personal', 'brand_key' => 'care', 'supplier_key' => 'medicine_supplier', 'warehouse_code' => 'SHELF', 'retail_price' => 4500, 'wholesale_price' => 3900, 'purchase_cost' => 2500, 'opening_stock' => 70, 'reorder_level' => 20],
            ],
        );
    }

    private function restaurant(): array
    {
        return $this->scenario(
            store: ['name' => 'Restaurant Demo', 'business_type' => 'restaurant', 'slug' => 'restaurant-demo', 'viber_number' => '09190000001', 'telegram_username' => 'restaurant_demo'],
            setting: ['store_name' => 'Restaurant Demo', 'tagline' => 'စားသောက်ဆိုင် POS နှင့် Kitchen Stock', 'phone' => '09190000001'],
            users: [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'Restaurant Manager', 'phone' => '09190000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Restaurant Head Chef', 'phone' => '09190000003', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'Restaurant Cashier', 'phone' => '09190000002', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'Kitchen Stock Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'Restaurant Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'Daw Aye (Catering Wholesale)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'Ma Su (Dine-in Customer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            warehouses: [
                ['name' => 'Kitchen Stock', 'code' => 'KITCHEN'],
                ['name' => 'Dry Store', 'code' => 'DRY'],
                ['name' => 'Daily Counter', 'code' => 'COUNTER'],
            ],
            categories: [
                ['key' => 'rice', 'name' => 'Rice & Curry', 'slug' => 'rice-curry'],
                ['key' => 'noodle', 'name' => 'Noodles', 'slug' => 'noodles'],
                ['key' => 'drink', 'name' => 'Drinks', 'slug' => 'restaurant-drinks'],
                ['key' => 'ingredient', 'name' => 'Ingredients', 'slug' => 'ingredients'],
            ],
            brands: [
                ['key' => 'house', 'name' => 'House Menu', 'slug' => 'house-menu'],
                ['key' => 'fresh', 'name' => 'Fresh Market', 'slug' => 'fresh-market'],
                ['key' => 'drink', 'name' => 'Drink Supplier', 'slug' => 'drink-supplier'],
            ],
            suppliers: [
                ['key' => 'fresh_supplier', 'name' => 'Daily Fresh Market', 'phone' => '09290000001'],
                ['key' => 'dry_supplier', 'name' => 'Dry Goods Supplier', 'phone' => '09290000002'],
            ],
            products: [
                ['sku' => 'RES-MENU-CHICKEN-RICE', 'name' => 'Chicken Rice Plate', 'category_key' => 'rice', 'brand_key' => 'house', 'supplier_key' => 'fresh_supplier', 'warehouse_code' => 'KITCHEN', 'retail_price' => 5500, 'wholesale_price' => 5500, 'purchase_cost' => 3200, 'opening_stock' => 35, 'reorder_level' => 10, 'product_type' => 'service', 'service_duration' => '12 min'],
                ['sku' => 'RES-MENU-PORK-CURRY', 'name' => 'Pork Curry Rice', 'category_key' => 'rice', 'brand_key' => 'house', 'supplier_key' => 'fresh_supplier', 'warehouse_code' => 'KITCHEN', 'retail_price' => 6500, 'wholesale_price' => 6500, 'purchase_cost' => 4000, 'opening_stock' => 28, 'reorder_level' => 8, 'product_type' => 'service', 'service_duration' => '15 min'],
                ['sku' => 'RES-MENU-SHAN-NOODLE', 'name' => 'Shan Noodle', 'category_key' => 'noodle', 'brand_key' => 'house', 'supplier_key' => 'fresh_supplier', 'warehouse_code' => 'KITCHEN', 'retail_price' => 4500, 'wholesale_price' => 4500, 'purchase_cost' => 2600, 'opening_stock' => 40, 'reorder_level' => 10, 'product_type' => 'service', 'service_duration' => '10 min'],
                ['sku' => 'RES-ING-RICE-50KG', 'name' => 'Rice Bag 50kg', 'category_key' => 'ingredient', 'brand_key' => 'dry_supplier', 'supplier_key' => 'dry_supplier', 'warehouse_code' => 'DRY', 'retail_price' => 145000, 'wholesale_price' => 140000, 'purchase_cost' => 128000, 'opening_stock' => 8, 'reorder_level' => 2, 'is_ecommerce' => false],
                ['sku' => 'RES-ING-OIL-10L', 'name' => 'Cooking Oil 10L', 'category_key' => 'ingredient', 'brand_key' => 'dry_supplier', 'supplier_key' => 'dry_supplier', 'warehouse_code' => 'DRY', 'retail_price' => 52000, 'wholesale_price' => 50000, 'purchase_cost' => 43000, 'opening_stock' => 12, 'reorder_level' => 3, 'is_ecommerce' => false],
                ['sku' => 'RES-DRINK-WATER', 'name' => 'Drinking Water Bottle', 'category_key' => 'drink', 'brand_key' => 'drink', 'supplier_key' => 'dry_supplier', 'warehouse_code' => 'COUNTER', 'retail_price' => 700, 'wholesale_price' => 600, 'purchase_cost' => 350, 'opening_stock' => 240, 'reorder_level' => 60],
                ['sku' => 'RES-DRINK-COLA', 'name' => 'Cola Bottle', 'category_key' => 'drink', 'brand_key' => 'drink', 'supplier_key' => 'dry_supplier', 'warehouse_code' => 'COUNTER', 'retail_price' => 1800, 'wholesale_price' => 1600, 'purchase_cost' => 1100, 'opening_stock' => 120, 'reorder_level' => 30],
                ['sku' => 'RES-MENU-TEA', 'name' => 'Myanmar Tea Cup', 'category_key' => 'drink', 'brand_key' => 'house', 'supplier_key' => 'fresh_supplier', 'warehouse_code' => 'COUNTER', 'retail_price' => 1000, 'wholesale_price' => 1000, 'purchase_cost' => 450, 'opening_stock' => 80, 'reorder_level' => 20, 'product_type' => 'service', 'service_duration' => '3 min'],
            ],
        );
    }

    private function scenario(array $store, array $setting, array $users, array $warehouses, array $categories, array $brands, array $suppliers, array $products): array
    {
        return [
            'store' => $store,
            'setting' => [
                'address' => 'Demo City, Myanmar',
                'viber_number' => $store['viber_number'],
                'telegram_username' => $store['telegram_username'],
                'opening_hours' => 'Mon - Sat: 9:00 AM - 6:00 PM',
                'delivery_info' => 'Local delivery and pickup demo.',
                'payment_info' => 'Cash, KBZ Pay, Wave Pay.',
            ] + $setting,
            'users' => $users,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'brands' => $brands,
            'suppliers' => $suppliers,
            'products' => $products,
        ];
    }

    private function generalRetail(): array
    {
        return $this->scenario(
            store: [
                'name' => 'ရွှေမြန်မာ မီနီမတ်',
                'business_type' => 'general_retail',
                'slug' => 'general-retail-demo',
                'viber_number' => '09200000001',
                'telegram_username' => 'shwemyanmar_minimart',
            ],
            setting: [
                'store_name' => 'ရွှေမြန်မာ မီနီမတ်',
                'tagline' => 'နေ့စဉ်လိုအပ်သမျှ တစ်နေရာတည်းမှာ ဝယ်ယူနိုင်သော အိမ်နီးချင်း Mini Mart',
                'phone' => '09200000001',
                'address' => 'အမှတ် (၁၂၃)၊ ဗိုလ်ချုပ်လမ်း၊ မန္တလေးမြို့',
                'opening_hours' => 'နေ့စဉ် မနက် 7:00 မှ ည 9:00 အထိ',
                'delivery_info' => 'မြို့တွင်းအိမ်အရောက်ပို့နှင့် ဆိုင်တွင်လာယူနိုင်ပါသည်။',
                'payment_info' => 'Cash, KBZ Pay, Wave Pay နှင့် MMQR လက်ခံပါသည်။',
            ],
            users: [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'ဒေါ်ခင်မာ (ဆိုင်မန်နေဂျာ)', 'phone' => '09200000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'မနွယ်နီ (အရောင်းစာရေး/Cashier)', 'phone' => '09200000002', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'ကိုမောင်မောင် (စတော့မှူး)', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'ဒေါ်နီလာ (စာရင်းကိုင်)', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'ဦးဘိုဘို (လက်ကားဖောက်သည်)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'ဒေါ်သန်းသန်းနွယ် (လက်လီဝယ်သူ)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            warehouses: [
                ['name' => 'အရောင်းစင်', 'code' => 'FRONT'],
                ['name' => 'နောက်ဂိုဒေါင်', 'code' => 'BACK'],
                ['name' => 'ပျက်စီး/ပြန်ပို့', 'code' => 'RETURN'],
            ],
            categories: [
                ['key' => 'grocery', 'name' => 'အခြေခံစားသောက်ကုန်', 'slug' => 'daily-grocery', 'icon' => '🛒'],
                ['key' => 'snacks', 'name' => 'မုန့်နှင့် အဆာပြေ', 'slug' => 'snacks', 'icon' => '🍪'],
                ['key' => 'drinks', 'name' => 'အချိုရည်နှင့် သောက်ရေ', 'slug' => 'beverages', 'icon' => '🥤'],
                ['key' => 'personal', 'name' => 'တစ်ကိုယ်ရေသုံးပစ္စည်း', 'slug' => 'personal-care-retail', 'icon' => '🧴'],
                ['key' => 'household', 'name' => 'အိမ်သုံးနှင့် သန့်ရှင်းရေး', 'slug' => 'household-cleaning', 'icon' => '🧹'],
            ],
            brands: [
                ['key' => 'shwe', 'name' => 'ရွှေမြန်မာ', 'slug' => 'shwe-myanmar'],
                ['key' => 'daily', 'name' => 'Daily Choice', 'slug' => 'daily-choice'],
                ['key' => 'fresh', 'name' => 'Fresh Myanmar', 'slug' => 'fresh-myanmar'],
                ['key' => 'home', 'name' => 'Happy Home', 'slug' => 'happy-home'],
            ],
            suppliers: [
                ['key' => 'grocery_supplier', 'name' => 'မန္တလေး အထွေထွေကုန်စုံ လက်ကား', 'phone' => '09300000001', 'contact_person' => 'ဦးအောင်မင်း', 'address' => 'ဇေယျာဈေး၊ မန္တလေး'],
                ['key' => 'household_supplier', 'name' => 'မြန်မာ အိမ်သုံးပစ္စည်း ဖြန့်ချိရေး', 'phone' => '09300000002', 'contact_person' => 'ဒေါ်မိုးမိုး', 'address' => 'ရန်ကုန်မြို့'],
            ],
            products: [
                ['sku' => 'GR-RICE-PAWSAN-5KG', 'name' => 'ပေါ်ဆန်းမွှေးဆန် 5 Kg', 'category_key' => 'grocery', 'brand_key' => 'shwe', 'supplier_key' => 'grocery_supplier', 'warehouse_code' => 'BACK', 'retail_price' => 24500, 'wholesale_price' => 23000, 'purchase_cost' => 20500, 'opening_stock' => 35, 'reorder_level' => 8],
                ['sku' => 'GR-OIL-PEANUT-1L', 'name' => 'မြေပဲဆီသန့် 1 L', 'category_key' => 'grocery', 'brand_key' => 'fresh', 'supplier_key' => 'grocery_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 12500, 'wholesale_price' => 11500, 'purchase_cost' => 9800, 'opening_stock' => 48, 'reorder_level' => 12],
                ['sku' => 'GR-NOODLE-INSTANT-10', 'name' => 'အသင့်စားခေါက်ဆွဲ 10 ထုပ်ပါ', 'category_key' => 'grocery', 'brand_key' => 'daily', 'supplier_key' => 'grocery_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 8500, 'wholesale_price' => 7800, 'purchase_cost' => 6500, 'opening_stock' => 60, 'reorder_level' => 15],
                ['sku' => 'GR-SNACK-POTATO', 'name' => 'အာလူးကြော် အထုပ်ကြီး', 'category_key' => 'snacks', 'brand_key' => 'daily', 'supplier_key' => 'grocery_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 2500, 'wholesale_price' => 2200, 'purchase_cost' => 1700, 'opening_stock' => 96, 'reorder_level' => 24],
                ['sku' => 'GR-WATER-1L', 'name' => 'သောက်ရေသန့် 1 L', 'category_key' => 'drinks', 'brand_key' => 'fresh', 'supplier_key' => 'grocery_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 900, 'wholesale_price' => 750, 'purchase_cost' => 520, 'opening_stock' => 180, 'reorder_level' => 48],
                ['sku' => 'GR-SHAMPOO-340', 'name' => 'မိသားစုသုံး Shampoo 340 ml', 'category_key' => 'personal', 'brand_key' => 'daily', 'supplier_key' => 'household_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 9500, 'wholesale_price' => 8800, 'purchase_cost' => 7200, 'opening_stock' => 36, 'reorder_level' => 10],
                ['sku' => 'GR-DETERGENT-1KG', 'name' => 'အဝတ်လျှော်မှုန့် 1 Kg', 'category_key' => 'household', 'brand_key' => 'home', 'supplier_key' => 'household_supplier', 'warehouse_code' => 'BACK', 'retail_price' => 7800, 'wholesale_price' => 7100, 'purchase_cost' => 5800, 'opening_stock' => 42, 'reorder_level' => 12],
                ['sku' => 'GR-DISHWASH-800', 'name' => 'ပန်းကန်ဆေးရည် 800 ml', 'category_key' => 'household', 'brand_key' => 'home', 'supplier_key' => 'household_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 6500, 'wholesale_price' => 5900, 'purchase_cost' => 4700, 'opening_stock' => 40, 'reorder_level' => 10],
            ],
        );
    }

    /**
     * Expand each compact scenario into a storefront-ready catalog while keeping
     * the source definitions readable. Generated records are deterministic and
     * remain idempotent because every SKU is stable.
     */
    private function enrichScenario(string $scenarioKey, array $scenario): array
    {
        $labels = match ($scenarioKey) {
            'mobile-accessories' => [
                'Black Edition', 'Blue Edition', 'White Edition', 'Titanium Gray',
                'Value Pack', 'Pro Edition', 'Fast Charge Set', 'Special Combo'
            ],
            'mobile-sale-service', 'datapos-mobile' => [
                'Official Warranty', 'Service Pack', 'AAA+ Grade', 'Original Chip',
                'Fast Charge Edition', 'Combo Set', 'Technician Choice', 'Special Promo'
            ],
            'cctv-network-computer' => ['Home Package', 'Shop Package', 'Office Package', 'Pro Package', 'Industrial Set'],
            'pharmacy' => ['10 Unit Pack', '30 Unit Pack', 'Family Pack', 'Clinic Pack', 'Value Set'],
            'restaurant', 'si-taw-gyi-food-bar' => ['Regular', 'Large', 'Family Set', 'Takeaway Set', 'Special Combo'],
            'diamond-stone-agri', 'diamon-stone-agri' => ['Small Farm Pack', 'Standard Pack', 'Value Pack', 'Commercial Pack', 'Bulk Sack'],
            'general-retail' => ['Small Pack', 'Standard Pack', 'Family Pack', 'Value Carton', 'Super Saver'],
            'kl-fashion', 'fashion-tailoring' => ['Custom Tailor', 'Silk Premium', 'Export Quality', 'Value Pack', 'Designer Set'],
            default => ['Standard', 'Plus', 'Value Pack', 'Premium', 'Special Edition'],
        };

        $baseProducts = array_values($scenario['products']);
        $products = $baseProducts;
        $targetCount = 100;
        $variantNumber = 1;

        while (count($products) < $targetCount) {
            $base = $baseProducts[($variantNumber - 1) % count($baseProducts)];
            $label = $labels[(int) floor(($variantNumber - 1) / count($baseProducts)) % count($labels)];
            $multiplier = [1.02, 1.05, 1.08, 1.12, 1.15, 1.20, 1.25][($variantNumber - 1) % 7];
            $variant = $base;
            $variant['sku'] = $base['sku'] . '-D' . str_pad((string) $variantNumber, 2, '0', STR_PAD_LEFT);
            $variant['barcode'] = !empty($base['barcode']) ? substr((string)$base['barcode'], 0, 10) . str_pad((string) $variantNumber, 3, '0', STR_PAD_LEFT) : null;
            $variant['name'] = $base['name'] . ' (' . $label . ')';
            $variant['retail_price'] = max(100, (int) round($base['retail_price'] * $multiplier / 100) * 100);
            $isVariantProduct = ($base['product_type'] ?? 'standard') === 'variant';
            $variant['opening_stock'] = $isVariantProduct ? 10 : (in_array($scenarioKey, ['mobile-accessories', 'mobile-sale-service', 'cctv-network-computer', 'datapos-mobile'], true) ? 30 : max(1, (int) ($base['opening_stock'] * (0.65 + (($variantNumber % 4) * 0.08)))));
            $variant['reorder_level'] = $isVariantProduct ? 3 : 5;
            $variant['is_featured'] = false;

            // If base product is a variant matrix, generate unique child variant SKUs & prices
            if (!empty($base['variants'])) {
                $childVariants = [];
                foreach ($base['variants'] as $vIdx => $cVar) {
                    $cVarCopy = $cVar;
                    $cVarCopy['sku'] = $variant['sku'] . '-V' . ($vIdx + 1);
                    $cVarCopy['retail_price'] = max(100, (int) round(($cVar['retail_price'] ?? $variant['retail_price']) * $multiplier / 100) * 100);
                    $cVarCopy['wholesale_price'] = max(100, (int) round($cVarCopy['retail_price'] * 0.92 / 100) * 100);
                    $cVarCopy['quantity_on_hand'] = 10;
                    $childVariants[] = $cVarCopy;
                }
                $variant['variants'] = $childVariants;
            }

            $products[] = $variant;
            $variantNumber++;
        }

        foreach ($products as $index => &$product) {
            $isEcommerce = $product['is_ecommerce'] ?? true;
            $product['description'] ??= $scenario['setting']['store_name'] . ' ၏ demo catalog အတွက် မြန်မာဈေးကွက်နှင့်ကိုက်ညီသော နမူနာကုန်ပစ္စည်းဖြစ်ပါသည်။';

            // Featured Products: Flagship phones, hot accessories, key services (~25% of products)
            $product['is_featured'] = $isEcommerce && (($product['is_featured'] ?? false) || ($index % 4 === 0));

            // Timed Promotions (အချိန်ပိုင်းလျှော့ဈေး / Flash Sales / Discounts - ~35% of products)
            if ($isEcommerce && ($index % 3 === 0)) {
                $discountMultiplier = [1.15, 1.20, 1.25, 1.30][$index % 4];
                $product['old_price'] = max($product['retail_price'] + 500, (int) ceil($product['retail_price'] * $discountMultiplier / 100) * 100);

                if ($index % 6 === 0) {
                    // Upcoming Flash Sale (မကြာမီ စတင်မည့် အချိန်ပိုင်း လျှော့ဈေး)
                    $product['sale_starts_at'] = now()->addHours(12);
                    $product['sale_ends_at'] = now()->addDays(7);
                } else {
                    // Active Flash Sale (လက်ရှိ အထူး လျှော့ဈေး ပရိုမိုးရှင်း)
                    $product['sale_starts_at'] = now()->subDays(2);
                    $product['sale_ends_at'] = now()->addDays(5 + ($index % 5));
                }
            }
        }
        unset($product);

        $scenario['products'] = $products;

        return $scenario;
    }

    private function diamondStoneAgri(): array
    {
        return [
            'store' => [
                'name' => 'Diamond Stone',
                'business_type' => 'agriculture_inputs',
                'slug' => 'diamond-stone-agri',
                'viber_number' => '09130000001',
                'telegram_username' => 'diamondstone_agri',
            ],
            'legacy_slugs' => ['diamon-stone-agri'],
            'setting' => [
                'store_name' => 'Diamond Stone',
                'tagline' => 'မျိုးစေ့၊ မြေသြဇာနှင့် စိုက်ပျိုးရေးဆေး ပစ္စည်းများ',
                'address' => 'Demo Township, Myanmar',
                'phone' => '09130000001',
                'viber_number' => '09130000001',
                'telegram_username' => 'diamondstone_agri',
                'opening_hours' => 'Mon - Sat: 7:00 AM - 6:00 PM',
                'delivery_info' => 'Township area delivery and farm pickup available.',
                'payment_info' => 'Cash, KBZ Pay, Wave Pay, supplier credit demo.',
            ],
            'users' => [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'Diamond Stone Manager', 'phone' => '09130000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Agri Technical Advisor', 'phone' => '09130000002', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'Diamond Stone Cashier', 'phone' => '09160000003', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'Warehouse & Stock Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'Finance & Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'ဦးဘိုဘို (Wholesale Farmer)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'ဒေါ်သန်းသန်းနွယ် (Retail Buyer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            'warehouses' => [
                ['name' => 'Seed Storage', 'code' => 'SEED'],
                ['name' => 'Chemical Storage', 'code' => 'CHEM'],
                ['name' => 'Expired / Return', 'code' => 'RETURN'],
            ],
            'categories' => [
                ['key' => 'seeds', 'name' => 'မျိုးစေ့', 'slug' => 'seeds', 'icon' => '🌱'],
                ['key' => 'fertilizer', 'name' => 'မြေသြဇာ', 'slug' => 'fertilizer', 'icon' => '🧪'],
                ['key' => 'pesticide', 'name' => 'ပိုးသတ်ဆေး', 'slug' => 'pesticide', 'icon' => '🛡️'],
                ['key' => 'tools', 'name' => 'စိုက်ပျိုးရေးအသုံးအဆောင်', 'slug' => 'farm-tools', 'icon' => '🧰'],
            ],
            'brands' => [
                ['key' => 'diamond', 'name' => 'Diamond Stone', 'slug' => 'diamond-stone', 'legacy_slugs' => ['diamon-stone']],
                ['key' => 'golden', 'name' => 'Golden Agro', 'slug' => 'golden-agro'],
                ['key' => 'green', 'name' => 'Green Field', 'slug' => 'green-field'],
            ],
            'suppliers' => [
                ['key' => 'yangon_agro', 'name' => 'Yangon Agro Supply', 'phone' => '09230000001', 'contact_person' => 'U Than', 'address' => 'Bayint Naung, Yangon'],
                ['key' => 'seed_house', 'name' => 'Myanmar Seed House', 'phone' => '09230000002', 'contact_person' => 'Daw Hnin', 'address' => 'Mandalay'],
            ],
            'products' => [
                ['sku' => 'DS-SEED-RICE-001', 'name' => 'Rice Seed Paw San 1 Bag', 'category_key' => 'seeds', 'brand_key' => 'green', 'supplier_key' => 'seed_house', 'warehouse_code' => 'SEED', 'retail_price' => 48000, 'wholesale_price' => 45500, 'purchase_cost' => 41000, 'opening_stock' => 35, 'reorder_level' => 8, 'shelf_location' => 'SEED-A1', 'is_featured' => true],
                ['sku' => 'DS-SEED-CORN-001', 'name' => 'Hybrid Corn Seed 1 Kg', 'category_key' => 'seeds', 'brand_key' => 'golden', 'supplier_key' => 'seed_house', 'warehouse_code' => 'SEED', 'retail_price' => 18000, 'wholesale_price' => 16500, 'purchase_cost' => 14000, 'opening_stock' => 60, 'reorder_level' => 12, 'shelf_location' => 'SEED-B1'],
                ['sku' => 'DS-FERT-NPK-001', 'name' => 'NPK Fertilizer 50 Kg', 'category_key' => 'fertilizer', 'brand_key' => 'golden', 'supplier_key' => 'yangon_agro', 'warehouse_code' => 'MAIN', 'retail_price' => 95000, 'wholesale_price' => 90000, 'purchase_cost' => 82000, 'opening_stock' => 25, 'reorder_level' => 6, 'shelf_location' => 'MAIN-F1'],
                ['sku' => 'DS-CHEM-INSECT-001', 'name' => 'Insect Control EC 500 ml', 'category_key' => 'pesticide', 'brand_key' => 'diamond', 'supplier_key' => 'yangon_agro', 'warehouse_code' => 'CHEM', 'retail_price' => 16500, 'wholesale_price' => 15000, 'purchase_cost' => 12500, 'opening_stock' => 45, 'reorder_level' => 10, 'shelf_location' => 'CHEM-L1', 'specs' => ['batch' => 'DEMO-BATCH-01', 'expiry' => '2028-12-31']],
                ['sku' => 'DS-CHEM-FUNGUS-001', 'name' => 'Fungus Guard WP 250 g', 'category_key' => 'pesticide', 'brand_key' => 'green', 'supplier_key' => 'yangon_agro', 'warehouse_code' => 'CHEM', 'retail_price' => 12000, 'wholesale_price' => 10800, 'purchase_cost' => 8800, 'opening_stock' => 40, 'reorder_level' => 10, 'shelf_location' => 'CHEM-L2', 'specs' => ['batch' => 'DEMO-BATCH-02', 'expiry' => '2028-06-30']],
                ['sku' => 'DS-TOOL-SPRAYER-001', 'name' => 'Manual Sprayer 16 L', 'category_key' => 'tools', 'brand_key' => 'diamond', 'supplier_key' => 'yangon_agro', 'warehouse_code' => 'MAIN', 'retail_price' => 38000, 'wholesale_price' => 35000, 'purchase_cost' => 30000, 'opening_stock' => 18, 'reorder_level' => 4, 'shelf_location' => 'MAIN-T1'],
            ],
        ];
    }

    private function siTawGyiFoodBar(): array
    {
        return [
            'store' => [
                'name' => 'စည်တော်ကြီး စားသောက်ဆိုင်',
                'business_type' => 'restaurant_bar',
                'slug' => 'si-taw-gyi-food-bar',
                'viber_number' => '09140000001',
                'telegram_username' => 'sitawgyi_foodbar',
            ],
            'setting' => [
                'store_name' => 'စည်တော်ကြီး စားသောက်ဆိုင်',
                'tagline' => 'စားသောက်ဆိုင်၊ အရက်နှင့် ဘီယာအရောင်း demo',
                'address' => 'Demo City, Myanmar',
                'phone' => '09140000001',
                'viber_number' => '09140000001',
                'telegram_username' => 'sitawgyi_foodbar',
                'opening_hours' => 'Daily: 10:00 AM - 10:00 PM',
                'delivery_info' => 'Dine-in, pickup and local delivery demo.',
                'payment_info' => 'Cash, KBZ Pay, Wave Pay.',
            ],
            'users' => [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'စည်တော်ကြီး Manager', 'phone' => '09140000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'စည်တော်ကြီး Cashier', 'phone' => '09140000002', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'Kitchen Bar Technician', 'phone' => '09140000003', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'Stock & Beverage Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'Finance & Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'Daw Aye (Event Wholesale)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'Ma Su (Dine-in Customer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            'warehouses' => [
                ['name' => 'Kitchen Stock', 'code' => 'KITCHEN'],
                ['name' => 'Bar Stock', 'code' => 'BAR'],
                ['name' => 'Daily Counter', 'code' => 'COUNTER'],
            ],
            'categories' => [
                ['key' => 'food', 'name' => 'အစားအစာ', 'slug' => 'food', 'icon' => '🍜'],
                ['key' => 'beer', 'name' => 'ဘီယာ', 'slug' => 'beer', 'icon' => '🍺'],
                ['key' => 'liquor', 'name' => 'အရက်', 'slug' => 'liquor', 'icon' => '🥃'],
                ['key' => 'soft_drink', 'name' => 'အချိုရည်', 'slug' => 'soft-drink', 'icon' => '🥤'],
            ],
            'brands' => [
                ['key' => 'house', 'name' => 'စည်တော်ကြီး', 'slug' => 'si-taw-gyi'],
                ['key' => 'myanmar', 'name' => 'Myanmar Brewery', 'slug' => 'myanmar-brewery'],
                ['key' => 'mandalay', 'name' => 'Mandalay', 'slug' => 'mandalay'],
            ],
            'suppliers' => [
                ['key' => 'food_supplier', 'name' => 'Fresh Food Supply', 'phone' => '09240000001', 'contact_person' => 'Ko Min', 'address' => 'Local Market'],
                ['key' => 'bar_supplier', 'name' => 'Beverage Distributor Demo', 'phone' => '09240000002', 'contact_person' => 'Ma Ei', 'address' => 'Yangon'],
            ],
            'products' => [
                ['sku' => 'STG-FOOD-RICE-001', 'name' => 'Chicken Fried Rice', 'category_key' => 'food', 'brand_key' => 'house', 'supplier_key' => 'food_supplier', 'warehouse_code' => 'KITCHEN', 'retail_price' => 6500, 'wholesale_price' => 6500, 'purchase_cost' => 3900, 'opening_stock' => 30, 'reorder_level' => 8, 'product_type' => 'service', 'service_duration' => '15 min', 'is_featured' => true],
                ['sku' => 'STG-FOOD-NOODLE-001', 'name' => 'Shan Noodle Bowl', 'category_key' => 'food', 'brand_key' => 'house', 'supplier_key' => 'food_supplier', 'warehouse_code' => 'KITCHEN', 'retail_price' => 4500, 'wholesale_price' => 4500, 'purchase_cost' => 2500, 'opening_stock' => 40, 'reorder_level' => 10, 'product_type' => 'service', 'service_duration' => '10 min'],
                ['sku' => 'STG-BEER-MYANMAR-001', 'name' => 'Myanmar Beer Bottle', 'category_key' => 'beer', 'brand_key' => 'myanmar', 'supplier_key' => 'bar_supplier', 'warehouse_code' => 'BAR', 'retail_price' => 3800, 'wholesale_price' => 3600, 'purchase_cost' => 2950, 'opening_stock' => 96, 'reorder_level' => 24, 'shelf_location' => 'BAR-B1'],
                ['sku' => 'STG-BEER-DRAUGHT-001', 'name' => 'Draught Beer Mug', 'category_key' => 'beer', 'brand_key' => 'myanmar', 'supplier_key' => 'bar_supplier', 'warehouse_code' => 'COUNTER', 'retail_price' => 2500, 'wholesale_price' => 2500, 'purchase_cost' => 1600, 'opening_stock' => 80, 'reorder_level' => 20, 'shelf_location' => 'COUNTER-B1'],
                ['sku' => 'STG-LIQUOR-RUM-001', 'name' => 'Mandalay Rum 350 ml', 'category_key' => 'liquor', 'brand_key' => 'mandalay', 'supplier_key' => 'bar_supplier', 'warehouse_code' => 'BAR', 'retail_price' => 14500, 'wholesale_price' => 13800, 'purchase_cost' => 11200, 'opening_stock' => 24, 'reorder_level' => 6, 'shelf_location' => 'BAR-L1'],
                ['sku' => 'STG-DRINK-COLA-001', 'name' => 'Cola Can', 'category_key' => 'soft_drink', 'brand_key' => 'house', 'supplier_key' => 'bar_supplier', 'warehouse_code' => 'COUNTER', 'retail_price' => 1800, 'wholesale_price' => 1700, 'purchase_cost' => 1200, 'opening_stock' => 72, 'reorder_level' => 24, 'shelf_location' => 'COUNTER-S1'],
            ],
        ];
    }

    private function klFashionTailoring(): array
    {
        return [
            'store' => [
                'name' => 'KL Fashion & Tailoring',
                'business_type' => 'fashion',
                'slug' => 'kl-fashion',
                'viber_number' => '09170000001',
                'telegram_username' => 'kl_fashion',
            ],
            'legacy_slugs' => ['fashion-tailoring-demo', 'kl-fashion-demo'],
            'setting' => [
                'store_name' => 'KL Fashion & Tailoring',
                'tagline' => 'စက်ချုပ်ဆိုင်၊ အဝတ်အထည်၊ ပိတ်စနှင့် စက်ချုပ်အပိုပစ္စည်း',
                'address' => 'အမှတ် (၁၂)၊ လမ်းမတော်၊ ရန်ကုန်မြို့။',
                'phone' => '09170000001',
                'viber_number' => '09170000001',
                'telegram_username' => 'kl_fashion',
                'opening_hours' => 'Daily: 9:00 AM - 8:00 PM',
                'delivery_info' => 'ရန်ကုန်/မန္တလေး အိမ်အရောက်နှင့် နယ်မြို့များသို့ ကားဂိတ်မှ ပို့ဆောင်ပေးပါသည်။',
                'payment_info' => 'KBZ Pay, CB Pay, Wave Pay, AYA Pay, Cash on Delivery.',
            ],
            'users' => [
                ['name' => 'DataPOS Store Owner', 'phone' => '09100000099', 'store_role' => 'store_owner', 'staff_role' => 'store_owner', 'pos_pin' => '1234'],
                ['name' => 'KL Fashion Manager', 'phone' => '09170000001', 'store_role' => 'store_manager', 'staff_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'KL Master Tailor', 'phone' => '09170000002', 'store_role' => 'staff', 'staff_role' => 'technician', 'pos_pin' => '1234'],
                ['name' => 'KL Sales Cashier', 'phone' => '09170000003', 'store_role' => 'staff', 'staff_role' => 'cashier', 'pos_pin' => '1234'],
                ['name' => 'Fabric & Material Keeper', 'phone' => '09100000003', 'store_role' => 'staff', 'staff_role' => 'stock_keeper', 'pos_pin' => '1234'],
                ['name' => 'Finance & Accountant', 'phone' => '09100000008', 'store_role' => 'staff', 'staff_role' => 'accountant', 'pos_pin' => '1234'],
                ['name' => 'Daw Aye (Boutique Wholesale)', 'phone' => '09100000004', 'store_role' => 'wholesale_customer'],
                ['name' => 'Ma Su (Retail Customer)', 'phone' => '09100000006', 'store_role' => 'retail_customer'],
            ],
            'warehouses' => [
                ['name' => 'Showroom & Front Shop', 'code' => 'FRONT'],
                ['name' => 'Tailoring Workshop', 'code' => 'WORKSHOP'],
                ['name' => 'Fabric & Material Storage', 'code' => 'FABRIC'],
            ],
            'categories' => [
                ['key' => 'tailoring', 'name' => 'စက်ချုပ်ဝန်ဆောင်မှု', 'slug' => 'tailoring-service', 'icon' => '✂️', 'description' => 'အမျိုးသမီး/အမျိုးသား အင်္ကျီချုပ်ခ၊ မင်္ဂလာဝတ်စုံနှင့် ပြင်ဆင်ခ'],
                ['key' => 'clothing', 'name' => 'အဝတ်အထည် (Ready-made)', 'slug' => 'clothing-garments', 'icon' => '👗', 'description' => 'အသင့်ဝတ် ဂါဝန်၊ တီရှပ်၊ ကုတ်အင်္ကျီနှင့် ပုဆိုး'],
                ['key' => 'fabrics', 'name' => 'ပိတ်စနှင့် အထည်လိပ်', 'slug' => 'fabrics-textiles', 'icon' => '🧵', 'description' => 'ပိုးထည်၊ ချည်ပိတ်စ၊ လင်နင်၊ ဂျင်းစနှင့် ဇာစ'],
                ['key' => 'notions', 'name' => 'စက်ချုပ်အပိုပစ္စည်း', 'slug' => 'sewing-accessories', 'icon' => '🪡', 'description' => 'အပ်ချည်ကြိုး၊ ဇစ်၊ ကြယ်သီး၊ စက်အပ်၊ ဓားကပ်၊ စက်ဆီ'],
            ],
            'brands' => [
                ['key' => 'kl_tailor', 'name' => 'KL Custom Tailor', 'slug' => 'kl-custom-tailor'],
                ['key' => 'shwe_zin', 'name' => 'Shwe Zin (ရွှေဇင်)', 'slug' => 'shwe-zin'],
                ['key' => 'ykk', 'name' => 'YKK', 'slug' => 'ykk'],
                ['key' => 'brother', 'name' => 'Brother / Juki', 'slug' => 'brother-juki'],
                ['key' => 'flying_wheel', 'name' => 'Flying Wheel (ရဟတ်)', 'slug' => 'flying-wheel'],
                ['key' => 'zara', 'name' => 'Zara Fashion', 'slug' => 'zara-fashion'],
            ],
            'suppliers' => [
                ['key' => 'mandalay_fabric', 'name' => 'မန္တလေး ပိုးထည်နှင့် ပိတ်စ လက်ကားဒိုင်', 'phone' => '09270000001', 'contact_person' => 'ဒေါ်ခင်စန်း', 'address' => 'ဈေးချို၊ မန္တလေး'],
                ['key' => 'yangon_notions', 'name' => 'ရန်ကုန် စက်ချုပ်ပစ္စည်းနှင့် အပိုပစ္စည်း ဖြန့်ချိရေး', 'phone' => '09270000002', 'contact_person' => 'ဦးသန်းအောင်', 'address' => 'မင်္ဂလာဈေး၊ ရန်ကုန်'],
                ['key' => 'shwe_oh_garment', 'name' => 'ရွှေအိုး အထည်ချုပ်နှင့် ဖက်ရှင်လက်ကား', 'phone' => '09270000003', 'contact_person' => 'ကိုမင်းမင်း', 'address' => 'ဘုရင့်နောင်၊ ရန်ကုန်'],
            ],
            'products' => [
                // === 1. Tailoring Services (စက်ချုပ်ဝန်ဆောင်မှု) ===
                ['sku' => 'KL-SRV-LADIES-01', 'name' => 'မြန်မာအမျိုးသမီး ရင်ဖုံး/ရင်စေ့ အင်္ကျီချုပ်ခ (Normal)', 'category_key' => 'tailoring', 'brand_key' => 'kl_tailor', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'WORKSHOP', 'retail_price' => 18000, 'wholesale_price' => 16000, 'purchase_cost' => 5000, 'opening_stock' => 50, 'reorder_level' => 10, 'product_type' => 'service', 'service_duration' => '3 Days', 'shelf_location' => 'WORKSHOP-SEWING', 'is_featured' => true],
                ['sku' => 'KL-SRV-BRIDAL-01', 'name' => 'ပွဲတက် မင်္ဂလာဝတ်စုံ ဒီဇိုင်းချုပ်ခ (Custom Bridal Design)', 'category_key' => 'tailoring', 'brand_key' => 'kl_tailor', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'WORKSHOP', 'retail_price' => 75000, 'wholesale_price' => 68000, 'purchase_cost' => 20000, 'opening_stock' => 20, 'reorder_level' => 5, 'product_type' => 'service', 'service_duration' => '7 Days', 'shelf_location' => 'WORKSHOP-CUTTING', 'is_featured' => true],
                ['sku' => 'KL-SRV-MEN-01', 'name' => 'အမျိုးသား တိုက်ပုံနှင့် ရှပ်အင်္ကျီ ချုပ်ခ', 'category_key' => 'tailoring', 'brand_key' => 'kl_tailor', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'WORKSHOP', 'retail_price' => 28000, 'wholesale_price' => 25000, 'purchase_cost' => 8000, 'opening_stock' => 35, 'reorder_level' => 8, 'product_type' => 'service', 'service_duration' => '4 Days', 'shelf_location' => 'WORKSHOP-SEWING'],
                ['sku' => 'KL-SRV-ALTER-01', 'name' => 'စကတ်/ဘောင်းဘီ အချိုးအစား ပြင်ဆင်ချုပ်ခ (Alteration)', 'category_key' => 'tailoring', 'brand_key' => 'kl_tailor', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'WORKSHOP', 'retail_price' => 6000, 'wholesale_price' => 5000, 'purchase_cost' => 1500, 'opening_stock' => 80, 'reorder_level' => 20, 'product_type' => 'service', 'service_duration' => '1 Day', 'shelf_location' => 'WORKSHOP-SEWING'],

                // === 2. Fabrics & Textiles (ပိတ်စနှင့် အထည်လိပ်) ===
                ['sku' => 'KL-FAB-SILK-01', 'name' => 'မန္တလေး ချည်ပိုး ဇာထိုးပိတ်စ (၁ ကိုက်)', 'category_key' => 'fabrics', 'brand_key' => 'shwe_zin', 'supplier_key' => 'mandalay_fabric', 'warehouse_code' => 'FABRIC', 'retail_price' => 18500, 'wholesale_price' => 16500, 'purchase_cost' => 13500, 'opening_stock' => 45, 'reorder_level' => 10, 'shelf_location' => 'SHELF-FABRIC-A', 'is_featured' => true],
                ['sku' => 'KL-FAB-LINEN-01', 'name' => 'Pure Linen အထည်လိပ် ပိတ်စ (အဖြူ/ကာကီ ၁ ကိုက်)', 'category_key' => 'fabrics', 'brand_key' => 'shwe_zin', 'supplier_key' => 'mandalay_fabric', 'warehouse_code' => 'FABRIC', 'retail_price' => 13000, 'wholesale_price' => 11800, 'purchase_cost' => 9500, 'opening_stock' => 60, 'reorder_level' => 15, 'shelf_location' => 'SHELF-FABRIC-A'],
                ['sku' => 'KL-FAB-COT-01', 'name' => 'ဂျပန် ချည်ပိတ်စ ပန်းရိုက်အဆင်ဒီဇိုင်း (၁ ကိုက်)', 'category_key' => 'fabrics', 'brand_key' => 'shwe_zin', 'supplier_key' => 'mandalay_fabric', 'warehouse_code' => 'FABRIC', 'retail_price' => 9000, 'wholesale_price' => 8000, 'purchase_cost' => 6200, 'opening_stock' => 80, 'reorder_level' => 20, 'shelf_location' => 'SHELF-FABRIC-B'],
                ['sku' => 'KL-FAB-DEN-01', 'name' => 'Stretch Denim Jeans ပိတ်စ အပြာရင့် (၁ ကိုက်)', 'category_key' => 'fabrics', 'brand_key' => 'shwe_zin', 'supplier_key' => 'mandalay_fabric', 'warehouse_code' => 'FABRIC', 'retail_price' => 15000, 'wholesale_price' => 13500, 'purchase_cost' => 10800, 'opening_stock' => 40, 'reorder_level' => 10, 'shelf_location' => 'SHELF-FABRIC-B'],

                // === 3. Readymade Garments (အဝတ်အထည်အရောင်း) ===
                ['sku' => 'KL-CLO-DRS-01', 'name' => 'Vintage Floral Summer Dress (အမျိုးသမီး ဂါဝန်)', 'category_key' => 'clothing', 'brand_key' => 'zara', 'supplier_key' => 'shwe_oh_garment', 'warehouse_code' => 'FRONT', 'retail_price' => 38000, 'wholesale_price' => 34000, 'purchase_cost' => 26000, 'opening_stock' => 18, 'reorder_level' => 5, 'shelf_location' => 'RACK-DRESS-01', 'is_featured' => true],
                ['sku' => 'KL-CLO-SHR-01', 'name' => 'Men Premium Cotton Long Sleeve Shirt (ရှပ်အင်္ကျီ)', 'category_key' => 'clothing', 'brand_key' => 'zara', 'supplier_key' => 'shwe_oh_garment', 'warehouse_code' => 'FRONT', 'retail_price' => 28000, 'wholesale_price' => 25000, 'purchase_cost' => 19500, 'opening_stock' => 25, 'reorder_level' => 6, 'shelf_location' => 'RACK-DRESS-01'],
                ['sku' => 'KL-CLO-PAS-01', 'name' => 'ကချင် ချည်လုံချည် / ပုဆိုး အဆင်ဒီဇိုင်း', 'category_key' => 'clothing', 'brand_key' => 'shwe_zin', 'supplier_key' => 'mandalay_fabric', 'warehouse_code' => 'FRONT', 'retail_price' => 22000, 'wholesale_price' => 19500, 'purchase_cost' => 15000, 'opening_stock' => 30, 'reorder_level' => 8, 'shelf_location' => 'SHOWROOM-DISPLAY'],
                ['sku' => 'KL-CLO-BLZ-01', 'name' => 'Casual Smart Blazer / Coat (အမျိုးသမီး ကုတ်အင်္ကျီ)', 'category_key' => 'clothing', 'brand_key' => 'zara', 'supplier_key' => 'shwe_oh_garment', 'warehouse_code' => 'FRONT', 'retail_price' => 58000, 'wholesale_price' => 52000, 'purchase_cost' => 41000, 'opening_stock' => 12, 'reorder_level' => 4, 'shelf_location' => 'SHOWROOM-DISPLAY', 'is_featured' => true],

                // === 4. Sewing Accessories & Notions (စက်ချုပ်အပိုပစ္စည်း) ===
                ['sku' => 'KL-SEW-THR-01', 'name' => 'Flying Wheel အပ်ချည်ကြိုး ခွေလိပ် (အရောင်စုံ)', 'category_key' => 'notions', 'brand_key' => 'flying_wheel', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'FRONT', 'retail_price' => 1200, 'wholesale_price' => 950, 'purchase_cost' => 700, 'opening_stock' => 250, 'reorder_level' => 50, 'shelf_location' => 'DRAWER-NOTIONS'],
                ['sku' => 'KL-SEW-ZIP-01', 'name' => 'YKK Invisible Concealed Zipper (၉ လက်မ ကွယ်ဇစ်)', 'category_key' => 'notions', 'brand_key' => 'ykk', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'FRONT', 'retail_price' => 1500, 'wholesale_price' => 1200, 'purchase_cost' => 850, 'opening_stock' => 180, 'reorder_level' => 40, 'shelf_location' => 'DRAWER-NOTIONS'],
                ['sku' => 'KL-SEW-BTN-01', 'name' => 'Premium Pearl Shell Buttons (ကမာ ကြယ်သီး ၁၂ လုံးပါ)', 'category_key' => 'notions', 'brand_key' => 'flying_wheel', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'FRONT', 'retail_price' => 3500, 'wholesale_price' => 2800, 'purchase_cost' => 2000, 'opening_stock' => 90, 'reorder_level' => 20, 'shelf_location' => 'DRAWER-NOTIONS'],
                ['sku' => 'KL-SEW-NDL-01', 'name' => 'Organ Sewing Machine Needles HAx1 (စက်အပ် ကတ်)', 'category_key' => 'notions', 'brand_key' => 'brother', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'FRONT', 'retail_price' => 4500, 'wholesale_price' => 3800, 'purchase_cost' => 2800, 'opening_stock' => 70, 'reorder_level' => 15, 'shelf_location' => 'DRAWER-NOTIONS'],
                ['sku' => 'KL-SEW-SCI-01', 'name' => 'Heavy Duty Tailor Scissors (၉ လက်မ စက်ချုပ် ဓားကပ်)', 'category_key' => 'notions', 'brand_key' => 'brother', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'FRONT', 'retail_price' => 19500, 'wholesale_price' => 17000, 'purchase_cost' => 13500, 'opening_stock' => 22, 'reorder_level' => 5, 'shelf_location' => 'DRAWER-NOTIONS'],
                ['sku' => 'KL-SEW-OIL-01', 'name' => 'Singer Sewing Machine Lubricant Oil (အပ်ချုပ်စက်ဆီ)', 'category_key' => 'notions', 'brand_key' => 'brother', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'FRONT', 'retail_price' => 2500, 'wholesale_price' => 2000, 'purchase_cost' => 1400, 'opening_stock' => 45, 'reorder_level' => 10, 'shelf_location' => 'DRAWER-NOTIONS'],
                ['sku' => 'KL-SEW-TAP-01', 'name' => 'Tailor Measuring Tape (၆၀ လက်မ ပေကြိုး)', 'category_key' => 'notions', 'brand_key' => 'flying_wheel', 'supplier_key' => 'yangon_notions', 'warehouse_code' => 'FRONT', 'retail_price' => 1000, 'wholesale_price' => 800, 'purchase_cost' => 500, 'opening_stock' => 120, 'reorder_level' => 30, 'shelf_location' => 'DRAWER-NOTIONS'],
            ],
        ];
    }
}
