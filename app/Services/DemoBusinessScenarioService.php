<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\InventoryMovement;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreLocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoBusinessScenarioService
{
    public function __construct(
        private StoreLocationService $storeLocations,
        private InventoryService $inventory,
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
            ],
            'mobile-sale-service' => [
                'label' => 'Mobile Sale & Service',
                'subtitle' => 'ဖုန်းအရောင်းနှင့် Service/Repair ဆိုင်',
                'description' => 'Phone sales, spare parts, service items, technician workflow စမ်းရန်။',
                'store_slug' => 'mobile-sale-service-demo',
            ],
            'cctv-network-computer' => [
                'label' => 'CCTV + Network + Computer',
                'subtitle' => 'CCTV, Network, Computer အရောင်း/တပ်ဆင်ရေး',
                'description' => 'Project stock, showroom, service spare parts, installation package workflow စမ်းရန်။',
                'store_slug' => 'cctv-network-computer-demo',
            ],
            'pharmacy' => [
                'label' => 'Pharmacy',
                'subtitle' => 'ဆေးဆိုင်',
                'description' => 'Medicine, supplement, device, expiry/batch stock workflow စမ်းရန်။',
                'store_slug' => 'pharmacy-demo',
            ],
            'restaurant' => [
                'label' => 'Restaurant',
                'subtitle' => 'စားသောက်ဆိုင်',
                'description' => 'Kitchen stock, counter drinks, ready-to-sell menu workflow စမ်းရန်။',
                'store_slug' => 'restaurant-demo',
            ],
            'diamond-stone-agri' => [
                'label' => 'Diamond Stone',
                'subtitle' => 'စိုက်ပျိုးရေး မျိုးစေ့နှင့် ပိုးသတ်ဆေးအရောင်းဆိုင်',
                'description' => 'Seeds, fertilizer, pesticide, supplier credit, expiry/chemical stock workflow စမ်းရန်။',
                'store_slug' => 'diamond-stone-agri',
            ],
            'si-taw-gyi-food-bar' => [
                'label' => 'စည်တော်ကြီး',
                'subtitle' => 'စားသောက်ဆိုင် + အရက်/ဘီယာအရောင်းဆိုင်',
                'description' => 'Kitchen stock, bar stock, daily counter, food/drink sale workflow စမ်းရန်။',
                'store_slug' => 'si-taw-gyi-food-bar',
            ],
        ];
    }

    public function create(string $scenarioKey, User $actor): array
    {
        if (! app()->environment(['local', 'testing', 'uat']) || ! config('app.show_quick_login')) {
            throw new \RuntimeException('Demo business scenarios are available only in local/UAT quick-login mode.');
        }

        $scenario = $this->scenarioDefinition($scenarioKey);

        return DB::transaction(function () use ($scenarioKey, $scenario, $actor) {
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

            $locations = $this->storeLocations->ensureDefaults($store);
            $this->attachUser($store, $actor, 'store_manager');

            foreach ($scenario['users'] as $user) {
                $this->attachUser($store, $this->user($user), $user['store_role']);
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
                $categories[$category['key']] = Category::updateOrCreate(
                    ['store_id' => $store->id, 'slug' => $category['slug']],
                    [
                        'name' => $category['name'],
                        'code' => strtoupper(str_replace('-', '_', $category['slug'])),
                        'description' => $category['description'] ?? null,
                        'icon' => $category['icon'] ?? null,
                    ]
                );
            }

            $brands = [];
            foreach ($scenario['brands'] as $brand) {
                $brandModel = Brand::where('store_id', $store->id)->where('slug', $brand['slug'])->first();
                if (! $brandModel && ! empty($brand['legacy_slugs'])) {
                    $brandModel = Brand::where('store_id', $store->id)->whereIn('slug', $brand['legacy_slugs'])->first();
                }

                if ($brandModel) {
                    $brandModel->update([
                        'name' => $brand['name'],
                        'slug' => $brand['slug'],
                        'code' => strtoupper(str_replace('-', '_', $brand['slug'])),
                    ]);
                } else {
                    $brandModel = Brand::create([
                        'store_id' => $store->id,
                        'name' => $brand['name'],
                        'slug' => $brand['slug'],
                        'code' => strtoupper(str_replace('-', '_', $brand['slug'])),
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

            return [
                'store' => $store,
                'products' => $productsCreated,
                'warehouses' => count($warehouses),
                'users' => count($scenario['users']) + 1,
            ];
        });
    }

    private function scenarioDefinition(string $scenarioKey): array
    {
        return match ($scenarioKey) {
            'mobile-accessories' => $this->mobileAccessories(),
            'mobile-sale-service' => $this->mobileSaleService(),
            'cctv-network-computer' => $this->cctvNetworkComputer(),
            'pharmacy' => $this->pharmacy(),
            'restaurant' => $this->restaurant(),
            'diamond-stone-agri', 'diamon-stone-agri' => $this->diamondStoneAgri(),
            'si-taw-gyi-food-bar' => $this->siTawGyiFoodBar(),
            default => throw new \InvalidArgumentException('Unknown demo scenario.'),
        };
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

    private function attachUser(Store $store, User $user, string $role): void
    {
        $store->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'status' => 'active',
                'updated_at' => now(),
            ],
        ]);
    }

    private function mobileAccessories(): array
    {
        return $this->scenario(
            store: ['name' => 'Mobile & Accessories Demo', 'business_type' => 'mobile_accessories', 'slug' => 'mobile-accessories-demo', 'viber_number' => '09150000001', 'telegram_username' => 'mobile_accessories_demo'],
            setting: ['store_name' => 'Mobile & Accessories Demo', 'tagline' => 'ဖုန်း၊ ကာဗာ၊ မှန်ကပ်၊ Charger နှင့် Accessories', 'phone' => '09150000001'],
            users: [
                ['name' => 'Mobile Accessories Manager', 'phone' => '09150000001', 'store_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Mobile Accessories Staff', 'phone' => '09150000002', 'store_role' => 'staff'],
            ],
            warehouses: [
                ['name' => 'Front Shop', 'code' => 'FRONT'],
                ['name' => 'Back Stock', 'code' => 'BACK'],
                ['name' => 'Damaged / Return', 'code' => 'RETURN'],
            ],
            categories: [
                ['key' => 'phones', 'name' => 'Mobile Phones', 'slug' => 'mobile-phones'],
                ['key' => 'glass', 'name' => 'Tempered Glass', 'slug' => 'tempered-glass'],
                ['key' => 'cases', 'name' => 'Phone Cases', 'slug' => 'phone-cases'],
                ['key' => 'charging', 'name' => 'Chargers & Cables', 'slug' => 'chargers-cables'],
                ['key' => 'audio', 'name' => 'Audio Accessories', 'slug' => 'audio-accessories'],
            ],
            brands: [
                ['key' => 'apple', 'name' => 'Apple', 'slug' => 'apple'],
                ['key' => 'samsung', 'name' => 'Samsung', 'slug' => 'samsung'],
                ['key' => 'xiaomi', 'name' => 'Xiaomi', 'slug' => 'xiaomi'],
                ['key' => 'anker', 'name' => 'Anker', 'slug' => 'anker'],
                ['key' => 'generic', 'name' => 'Generic', 'slug' => 'generic'],
            ],
            suppliers: [
                ['key' => 'phone_supplier', 'name' => 'Yangon Mobile Wholesale', 'phone' => '09250000001'],
                ['key' => 'accessory_supplier', 'name' => 'Accessory Mart Demo', 'phone' => '09250000002'],
            ],
            products: [
                ['sku' => 'MA-IP15-128-BLK', 'name' => 'iPhone 15 128GB Black', 'category_key' => 'phones', 'brand_key' => 'apple', 'supplier_key' => 'phone_supplier', 'warehouse_code' => 'BACK', 'retail_price' => 2650000, 'wholesale_price' => 2580000, 'purchase_cost' => 2480000, 'opening_stock' => 4, 'reorder_level' => 1, 'warranty' => '1 year service warranty', 'is_featured' => true],
                ['sku' => 'MA-SAM-A15-128', 'name' => 'Samsung Galaxy A15 128GB', 'category_key' => 'phones', 'brand_key' => 'samsung', 'supplier_key' => 'phone_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 520000, 'wholesale_price' => 498000, 'purchase_cost' => 465000, 'opening_stock' => 8, 'reorder_level' => 2, 'warranty' => '1 year service warranty'],
                ['sku' => 'MA-XIA-13C-128', 'name' => 'Redmi 13C 128GB', 'category_key' => 'phones', 'brand_key' => 'xiaomi', 'supplier_key' => 'phone_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 420000, 'wholesale_price' => 398000, 'purchase_cost' => 365000, 'opening_stock' => 10, 'reorder_level' => 3],
                ['sku' => 'MA-GLASS-IP15', 'name' => 'iPhone 15 Tempered Glass', 'category_key' => 'glass', 'brand_key' => 'generic', 'supplier_key' => 'accessory_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 5000, 'wholesale_price' => 3500, 'purchase_cost' => 1800, 'opening_stock' => 120, 'reorder_level' => 30],
                ['sku' => 'MA-CASE-SAM-A15', 'name' => 'Samsung A15 Clear Case', 'category_key' => 'cases', 'brand_key' => 'generic', 'supplier_key' => 'accessory_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 6500, 'wholesale_price' => 4800, 'purchase_cost' => 2500, 'opening_stock' => 80, 'reorder_level' => 20],
                ['sku' => 'MA-ANKER-20W', 'name' => 'Anker 20W USB-C Charger', 'category_key' => 'charging', 'brand_key' => 'anker', 'supplier_key' => 'accessory_supplier', 'warehouse_code' => 'BACK', 'retail_price' => 42000, 'wholesale_price' => 39000, 'purchase_cost' => 33000, 'opening_stock' => 20, 'reorder_level' => 5],
                ['sku' => 'MA-CABLE-TYPEC-1M', 'name' => 'Type-C Cable 1m', 'category_key' => 'charging', 'brand_key' => 'generic', 'supplier_key' => 'accessory_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 4500, 'wholesale_price' => 3300, 'purchase_cost' => 1800, 'opening_stock' => 150, 'reorder_level' => 40],
                ['sku' => 'MA-EARPHONE-WIRED', 'name' => 'Wired Earphone', 'category_key' => 'audio', 'brand_key' => 'generic', 'supplier_key' => 'accessory_supplier', 'warehouse_code' => 'FRONT', 'retail_price' => 8500, 'wholesale_price' => 6500, 'purchase_cost' => 4000, 'opening_stock' => 55, 'reorder_level' => 15],
            ],
        );
    }

    private function mobileSaleService(): array
    {
        return $this->scenario(
            store: ['name' => 'Mobile Sale & Service Demo', 'business_type' => 'mobile_sale_service', 'slug' => 'mobile-sale-service-demo', 'viber_number' => '09160000001', 'telegram_username' => 'mobile_service_demo'],
            setting: ['store_name' => 'Mobile Sale & Service Demo', 'tagline' => 'ဖုန်းအရောင်း၊ Service Ticket နှင့် Spare Parts', 'phone' => '09160000001'],
            users: [
                ['name' => 'Mobile Service Manager', 'phone' => '09160000001', 'store_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Mobile Service Technician', 'phone' => '09160000002', 'store_role' => 'staff'],
            ],
            warehouses: [
                ['name' => 'Showroom', 'code' => 'SHOW'],
                ['name' => 'Service Spare Parts', 'code' => 'SPARE'],
                ['name' => 'Repair Intake Shelf', 'code' => 'INTAKE'],
            ],
            categories: [
                ['key' => 'phones', 'name' => 'Mobile Phones', 'slug' => 'service-mobile-phones'],
                ['key' => 'parts', 'name' => 'Spare Parts', 'slug' => 'spare-parts'],
                ['key' => 'service', 'name' => 'Repair Services', 'slug' => 'repair-services'],
                ['key' => 'tools', 'name' => 'Technician Tools', 'slug' => 'technician-tools'],
            ],
            brands: [
                ['key' => 'apple', 'name' => 'Apple', 'slug' => 'service-apple'],
                ['key' => 'samsung', 'name' => 'Samsung', 'slug' => 'service-samsung'],
                ['key' => 'oppo', 'name' => 'OPPO', 'slug' => 'oppo'],
                ['key' => 'house', 'name' => 'Service Center', 'slug' => 'service-center'],
            ],
            suppliers: [
                ['key' => 'phone_supplier', 'name' => 'Mobile Device Distributor', 'phone' => '09260000001'],
                ['key' => 'parts_supplier', 'name' => 'Phone Parts Wholesale', 'phone' => '09260000002'],
            ],
            products: [
                ['sku' => 'MSS-OPPO-A58', 'name' => 'OPPO A58 128GB', 'category_key' => 'phones', 'brand_key' => 'oppo', 'supplier_key' => 'phone_supplier', 'warehouse_code' => 'SHOW', 'retail_price' => 610000, 'wholesale_price' => 585000, 'purchase_cost' => 545000, 'opening_stock' => 7, 'reorder_level' => 2, 'warranty' => '1 year service warranty'],
                ['sku' => 'MSS-IP-BAT-11', 'name' => 'iPhone 11 Battery', 'category_key' => 'parts', 'brand_key' => 'apple', 'supplier_key' => 'parts_supplier', 'warehouse_code' => 'SPARE', 'retail_price' => 58000, 'wholesale_price' => 52000, 'purchase_cost' => 39000, 'opening_stock' => 12, 'reorder_level' => 4],
                ['sku' => 'MSS-SAM-A12-LCD', 'name' => 'Samsung A12 LCD Assembly', 'category_key' => 'parts', 'brand_key' => 'samsung', 'supplier_key' => 'parts_supplier', 'warehouse_code' => 'SPARE', 'retail_price' => 72000, 'wholesale_price' => 68000, 'purchase_cost' => 53000, 'opening_stock' => 9, 'reorder_level' => 3],
                ['sku' => 'MSS-CHG-PORT-TYPEC', 'name' => 'Type-C Charging Port Board', 'category_key' => 'parts', 'brand_key' => 'house', 'supplier_key' => 'parts_supplier', 'warehouse_code' => 'SPARE', 'retail_price' => 18000, 'wholesale_price' => 15000, 'purchase_cost' => 9000, 'opening_stock' => 25, 'reorder_level' => 8],
                ['sku' => 'MSS-SVC-LCD-CHANGE', 'name' => 'LCD Change Service Fee', 'category_key' => 'service', 'brand_key' => 'house', 'supplier_key' => 'parts_supplier', 'warehouse_code' => 'INTAKE', 'retail_price' => 25000, 'wholesale_price' => 25000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'service_duration' => '45 min', 'is_ecommerce' => false],
                ['sku' => 'MSS-SVC-CLEANING', 'name' => 'Phone Cleaning Service', 'category_key' => 'service', 'brand_key' => 'house', 'supplier_key' => 'parts_supplier', 'warehouse_code' => 'INTAKE', 'retail_price' => 10000, 'wholesale_price' => 10000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'service_duration' => '20 min', 'is_ecommerce' => false],
                ['sku' => 'MSS-TOOL-SCREWDRIVER', 'name' => 'Precision Screwdriver Set', 'category_key' => 'tools', 'brand_key' => 'house', 'supplier_key' => 'parts_supplier', 'warehouse_code' => 'SPARE', 'retail_price' => 35000, 'wholesale_price' => 33000, 'purchase_cost' => 26000, 'opening_stock' => 5, 'reorder_level' => 2],
                ['sku' => 'MSS-GLUE-OCA', 'name' => 'OCA Glue Pack', 'category_key' => 'parts', 'brand_key' => 'house', 'supplier_key' => 'parts_supplier', 'warehouse_code' => 'SPARE', 'retail_price' => 12000, 'wholesale_price' => 10500, 'purchase_cost' => 7000, 'opening_stock' => 30, 'reorder_level' => 10],
            ],
        );
    }

    private function cctvNetworkComputer(): array
    {
        return $this->scenario(
            store: ['name' => 'CCTV Network Computer Demo', 'business_type' => 'cctv_network_computer', 'slug' => 'cctv-network-computer-demo', 'viber_number' => '09170000001', 'telegram_username' => 'cctv_network_demo'],
            setting: ['store_name' => 'CCTV Network Computer Demo', 'tagline' => 'CCTV, Network, Computer Sales & Installation', 'phone' => '09170000001'],
            users: [
                ['name' => 'CCTV Network Manager', 'phone' => '09170000001', 'store_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Installation Technician', 'phone' => '09170000002', 'store_role' => 'staff'],
            ],
            warehouses: [
                ['name' => 'Showroom', 'code' => 'SHOW'],
                ['name' => 'Project Stock', 'code' => 'PROJECT'],
                ['name' => 'Service Spare Parts', 'code' => 'SPARE'],
            ],
            categories: [
                ['key' => 'cctv', 'name' => 'CCTV Cameras', 'slug' => 'cctv-cameras'],
                ['key' => 'network', 'name' => 'Network Devices', 'slug' => 'network-devices'],
                ['key' => 'computer', 'name' => 'Computers', 'slug' => 'computers'],
                ['key' => 'cables', 'name' => 'Cables & Accessories', 'slug' => 'cables-accessories'],
                ['key' => 'service', 'name' => 'Installation Services', 'slug' => 'installation-services'],
            ],
            brands: [
                ['key' => 'hikvision', 'name' => 'Hikvision', 'slug' => 'hikvision'],
                ['key' => 'dahua', 'name' => 'Dahua', 'slug' => 'dahua'],
                ['key' => 'tplink', 'name' => 'TP-Link', 'slug' => 'tp-link'],
                ['key' => 'lenovo', 'name' => 'Lenovo', 'slug' => 'lenovo'],
                ['key' => 'house', 'name' => 'Project Service', 'slug' => 'project-service'],
            ],
            suppliers: [
                ['key' => 'security_supplier', 'name' => 'Security System Wholesale', 'phone' => '09270000001'],
                ['key' => 'computer_supplier', 'name' => 'Computer & Network Distributor', 'phone' => '09270000002'],
            ],
            products: [
                ['sku' => 'CNC-HIK-2MP-DOME', 'name' => 'Hikvision 2MP Dome Camera', 'category_key' => 'cctv', 'brand_key' => 'hikvision', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 85000, 'wholesale_price' => 79000, 'purchase_cost' => 65000, 'opening_stock' => 20, 'reorder_level' => 6, 'warranty' => '1 year'],
                ['sku' => 'CNC-DAH-NVR-8CH', 'name' => 'Dahua 8CH NVR', 'category_key' => 'cctv', 'brand_key' => 'dahua', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 185000, 'wholesale_price' => 175000, 'purchase_cost' => 148000, 'opening_stock' => 8, 'reorder_level' => 2],
                ['sku' => 'CNC-TPL-ARCHER-C6', 'name' => 'TP-Link Archer C6 Router', 'category_key' => 'network', 'brand_key' => 'tplink', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'SHOW', 'retail_price' => 88000, 'wholesale_price' => 83000, 'purchase_cost' => 70000, 'opening_stock' => 15, 'reorder_level' => 4],
                ['sku' => 'CNC-CAT6-305M', 'name' => 'CAT6 Cable Box 305m', 'category_key' => 'cables', 'brand_key' => 'house', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 165000, 'wholesale_price' => 155000, 'purchase_cost' => 130000, 'opening_stock' => 10, 'reorder_level' => 3],
                ['sku' => 'CNC-LEN-I5-DESKTOP', 'name' => 'Lenovo i5 Desktop Set', 'category_key' => 'computer', 'brand_key' => 'lenovo', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'SHOW', 'retail_price' => 1250000, 'wholesale_price' => 1190000, 'purchase_cost' => 1080000, 'opening_stock' => 5, 'reorder_level' => 1],
                ['sku' => 'CNC-SSD-512-SATA', 'name' => '512GB SATA SSD', 'category_key' => 'computer', 'brand_key' => 'house', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'SPARE', 'retail_price' => 95000, 'wholesale_price' => 90000, 'purchase_cost' => 76000, 'opening_stock' => 18, 'reorder_level' => 5],
                ['sku' => 'CNC-SVC-CCTV-4CAM', 'name' => '4 Camera Installation Package', 'category_key' => 'service', 'brand_key' => 'house', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 250000, 'wholesale_price' => 250000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'service_duration' => '1 day', 'is_ecommerce' => false],
                ['sku' => 'CNC-SVC-WIFI-SETUP', 'name' => 'WiFi Network Setup Service', 'category_key' => 'service', 'brand_key' => 'house', 'supplier_key' => 'computer_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 60000, 'wholesale_price' => 60000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'service_duration' => '3 hr', 'is_ecommerce' => false],
            ],
        );
    }

    private function pharmacy(): array
    {
        return $this->scenario(
            store: ['name' => 'Pharmacy Demo', 'business_type' => 'pharmacy', 'slug' => 'pharmacy-demo', 'viber_number' => '09180000001', 'telegram_username' => 'pharmacy_demo'],
            setting: ['store_name' => 'Pharmacy Demo', 'tagline' => 'ဆေးဝါး၊ Supplement နှင့် Medical Device', 'phone' => '09180000001'],
            users: [
                ['name' => 'Pharmacy Manager', 'phone' => '09180000001', 'store_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Pharmacy Staff', 'phone' => '09180000002', 'store_role' => 'staff'],
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
                ['name' => 'Restaurant Manager', 'phone' => '09190000001', 'store_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Restaurant Cashier', 'phone' => '09190000002', 'store_role' => 'staff'],
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
                ['name' => 'Diamond Stone Manager', 'phone' => '09130000001', 'store_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'Diamond Stone Staff', 'phone' => '09130000002', 'store_role' => 'staff'],
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
                ['name' => 'စည်တော်ကြီး Manager', 'phone' => '09140000001', 'store_role' => 'store_manager', 'pos_pin' => '1234'],
                ['name' => 'စည်တော်ကြီး Cashier', 'phone' => '09140000002', 'store_role' => 'staff'],
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
}
