<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\StoreDeliveryMethod;
use App\Models\StorePaymentMethod;
use App\Models\StorefrontSetting;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\Branch;
use App\POS\Models\InventoryMovement;
use App\POS\Models\Warehouse;
use App\POS\Services\CustomerDebtService;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreLocationService;
use App\Services\DemoBusinessScenarioService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * DemoStoresSeeder
 * ================
 * Builds rich, production-realistic Myanmar SME demo stores
 * (Agriculture, Mobile Accessories, Mobile Sale & Service, CCTV & Computer, Pharmacy, Restaurant)
 * with complete Settings, Warehouses, Categories, Brands, Suppliers, Products,
 * Multi-role User Accounts (Manager, Cashier, Technician, Wholesale/Retail Customer),
 * Customer Debts, Sample Online Orders, and POS Sales records.
 */
class DemoStoresSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('🚀 Building Myanmar SME Demo Stores for Client Demonstrations...');

        $now = Carbon::now();
        $scenarioService = app(DemoBusinessScenarioService::class);
        $locationService = app(StoreLocationService::class);
        $inventoryService = app(InventoryService::class);
        $debtService = app(CustomerDebtService::class);

        // 1. Create Core Platform Users
        $users = $this->createCoreUsers($now);

        // 2. Define the 6 Core Demo Stores
        $storesData = $this->storesDefinition();

        foreach ($storesData as $slug => $data) {
            $this->command?->info("🏢 Setting up: {$data['store']['name']} ({$slug})...");

            // Create or update store
            $store = Store::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['store']['name'],
                    'business_type' => $data['store']['business_type'],
                    'phone' => $data['store']['phone'],
                    'viber_number' => $data['store']['viber_number'] ?? $data['store']['phone'],
                    'telegram_username' => $data['store']['telegram_username'] ?? null,
                    'is_active' => true,
                ]
            );

            // Clean old test transactions for this store to ensure clean state
            $scenarioService->cleanStoreData($store);

            // Ensure Storefront Setting
            // Demo provisioning applies the profile-recommended theme (T6) so
            // each demo storefront opens with its suggested design; the owner
            // can still switch to any active theme later.
            StorefrontSetting::updateOrCreate(
                ['store_id' => $store->id],
                array_merge([
                    'store_name' => $data['store']['name'],
                    'tagline' => $data['setting']['tagline'] ?? 'Smart POS & E-Commerce System',
                    'about_text' => $data['setting']['about_text'] ?? 'အရည်အသွေးမြင့် ကုန်ပစ္စည်းများကို အထူးစျေးနှုန်းများဖြင့် မြန်မာတစ်နိုင်ငံလုံးသို့ လက်လီ/လက်ကား ပို့ဆောင်ပေးနေပါသည်။',
                    'phone' => $data['store']['phone'],
                    'viber_number' => $data['store']['viber_number'] ?? $data['store']['phone'],
                    'telegram_username' => $data['store']['telegram_username'] ?? null,
                    'address' => $data['setting']['address'] ?? 'ရန်ကုန်မြို့၊ မြန်မာနိုင်ငံ။',
                    'default_language' => 'my',
                    'currency_symbol' => 'Ks',
                    'theme_preset' => \App\Themes\ThemeRecommendation::recommendForDemoBusinessType($data['store']['business_type']),
                    'chat_button_enabled' => true,
                    'chat_button_label' => 'စုံစမ်းရန်',
                    'chat_button_icon' => 'viber',
                    'chat_channel_url' => 'https://msng.link/vi/' . ($data['store']['viber_number'] ?? $data['store']['phone']),
                ], $data['setting'] ?? [])
            );

            // Payment Methods
            $this->seedPaymentMethods($store);

            // Delivery Methods
            $this->seedDeliveryMethods($store);

            // Attach Users with Store Roles
            $this->attachStoreUsers($store, $users);

            // Ensure Branch & Warehouses
            $locations = $locationService->ensureDefaults($store);
            $warehouses = ['MAIN' => $locations['warehouse']];

            foreach ($data['warehouses'] as $wData) {
                $warehouses[$wData['code']] = Warehouse::updateOrCreate(
                    ['store_id' => $store->id, 'name' => $wData['name']],
                    [
                        'branch_id' => $locations['branch']->id,
                        'code' => $wData['code'],
                        'is_default' => false,
                        'is_active' => true,
                    ]
                );
            }

            // Categories
            $categories = [];
            foreach ($data['categories'] as $cat) {
                $categories[$cat['key']] = Category::updateOrCreate(
                    ['store_id' => $store->id, 'slug' => $cat['slug']],
                    [
                        'name' => $cat['name'],
                        'code' => strtoupper(str_replace('-', '_', $cat['slug'])),
                        'description' => $cat['description'] ?? null,
                        'icon' => $cat['icon'] ?? '📦',
                    ]
                );
            }

            // Brands
            $brands = [];
            foreach ($data['brands'] as $br) {
                $brands[$br['key']] = Brand::updateOrCreate(
                    ['store_id' => $store->id, 'slug' => $br['slug']],
                    [
                        'name' => $br['name'],
                        'code' => strtoupper(str_replace('-', '_', $br['slug'])),
                    ]
                );
            }

            // Suppliers
            $suppliers = [];
            foreach ($data['suppliers'] as $sup) {
                $suppliers[$sup['key']] = Supplier::updateOrCreate(
                    ['store_id' => $store->id, 'phone' => $sup['phone']],
                    [
                        'name' => $sup['name'],
                        'contact_person' => $sup['contact_person'] ?? null,
                        'address' => $sup['address'] ?? null,
                        'notes' => $sup['notes'] ?? null,
                    ]
                );
            }

            // Products & Stock
            $createdProducts = [];
            foreach ($data['products'] as $prod) {
                $wh = $warehouses[$prod['warehouse_code']] ?? $locations['warehouse'];
                $product = Product::updateOrCreate(
                    ['store_id' => $store->id, 'sku' => $prod['sku']],
                    [
                        'category_id' => $categories[$prod['category_key']]->id,
                        'brand_id' => $brands[$prod['brand_key']]->id,
                        'supplier_id' => $suppliers[$prod['supplier_key']]->id,
                        'warehouse_id' => $wh->id,
                        'product_type' => $prod['product_type'] ?? 'standard',
                        'name' => $prod['name'],
                        'slug' => Str::slug($prod['sku']),
                        'description' => $prod['description'] ?? "{$prod['name']} — ပစ္စည်းမှန် စျေးနှုန်းမှန်ကန်စွာ ရရှိနိုင်ပါသည်။",
                        'retail_price' => $prod['retail_price'],
                        'wholesale_price' => $prod['wholesale_price'] ?? $prod['retail_price'],
                        'purchase_cost' => $prod['purchase_cost'] ?? round($prod['retail_price'] * 0.75),
                        'reorder_level' => $prod['reorder_level'] ?? 5,
                        'stock_status' => 'in_stock',
                        'is_featured' => $prod['is_featured'] ?? false,
                        'is_ecommerce' => $prod['is_ecommerce'] ?? true,
                        'warranty' => $prod['warranty'] ?? '၁ နှစ် အာမခံ',
                        'return_policy' => $prod['return_policy'] ?? 'ဝယ်ယူပြီး ၇ ရက်အတွင်း မူလအတိုင်း လဲလှယ်နိုင်ပါသည်။',
                    ]
                );

                $createdProducts[] = $product;

                // Post Opening Stock
                $inventoryService->postMovement([
                    'store' => $store,
                    'warehouse_id' => $wh->id,
                    'branch_id' => $locations['branch']->id,
                    'product_id' => $product->id,
                    'movement_type' => InventoryMovementType::OpeningBalance->value,
                    'quantity_delta' => (string) ($prod['opening_stock'] ?? 20),
                    'unit_cost' => (string) ($prod['purchase_cost'] ?? round($prod['retail_price'] * 0.75)),
                    'source_type' => 'demo_scenario',
                    'source_id' => $store->id,
                    'client_transaction_id' => "demo:{$slug}:opening:{$product->sku}",
                    'metadata' => ['scenario' => $slug],
                ]);
            }

            $storeStaff = $this->getStorePersonnel($slug, $users);

            // Seed Sample Debts for Wholesale and Retail Customers
            if (!empty($storeStaff['wholesale'])) {
                $debtService->recordOpeningBalance(
                    $store,
                    $storeStaff['wholesale']->id,
                    '250000',
                    $storeStaff['manager'],
                    'ယခင်လ စာရင်းဖွင့် အကြွေးကျန်ငွေ',
                    "demo:debt:wholesale:{$store->id}"
                );
            }
            if (!empty($storeStaff['retail'])) {
                $debtService->recordOpeningBalance(
                    $store,
                    $storeStaff['retail']->id,
                    '45000',
                    $storeStaff['manager'],
                    'အပတ်စဉ် ပုံမှန်ဝယ်ယူသူ အကြွေးကျန်',
                    "demo:debt:retail:{$store->id}"
                );
            }

            // Seed Sample Online Orders
            $this->seedSampleOrders($store, $storeStaff, $createdProducts);

            // Seed Sample POS Sales
            $this->seedSamplePosSales($store, $storeStaff, $createdProducts, $locations['branch']);
        }

        $this->command?->info('✅ All 6 Myanmar SME Demo Stores have been successfully built and seeded!');
    }

    private function createCoreUsers(Carbon $now): array
    {
        $passwordHash = Hash::make('password');
        $pinHash = Hash::make('1234');

        $users = [];

        // 1. Super Admin (Platform Owner)
        $users['super_admin'] = User::updateOrCreate(
            ['phone' => '09777000111'],
            [
                'name' => 'ဦးအောင်မျိုး (DataPOS Super Admin)',
                'email' => 'admin@datapos.local',
                'password' => $passwordHash,
                'pos_pin' => $pinHash,
                'role' => 'platform_owner',
            ]
        );

        // 2. Global Unified Demo Accounts
        $users['manager'] = User::updateOrCreate(
            ['phone' => '09111222333'],
            [
                'name' => 'ဦးကျော်ကျော် (Store Manager)',
                'email' => 'manager@datapos.local',
                'password' => $passwordHash,
                'pos_pin' => $pinHash,
                'role' => 'customer',
            ]
        );

        $users['cashier'] = User::updateOrCreate(
            ['phone' => '09222333444'],
            [
                'name' => 'ဒေါ်လှလှ (Senior Cashier)',
                'email' => 'cashier@datapos.local',
                'password' => $passwordHash,
                'pos_pin' => $pinHash,
                'role' => 'customer',
            ]
        );

        $users['technician'] = User::updateOrCreate(
            ['phone' => '09333444555'],
            [
                'name' => 'ကိုမင်းမင်း (Technician & Stock)',
                'email' => 'tech@datapos.local',
                'password' => $passwordHash,
                'pos_pin' => $pinHash,
                'role' => 'customer',
            ]
        );

        $users['wholesale_customer'] = User::updateOrCreate(
            ['phone' => '09988776655'],
            [
                'name' => 'ဦးဘသိန်း (ရွှေလက်ကား ကုန်သည်ကြီး)',
                'email' => 'wholesale@partner.local',
                'password' => $passwordHash,
                'role' => 'customer',
            ]
        );

        $users['retail_customer'] = User::updateOrCreate(
            ['phone' => '09776655443'],
            [
                'name' => 'ဒေါ်နီလာ (လက်လီ ဝယ်ယူသူ)',
                'email' => 'nilar@gmail.com',
                'password' => $passwordHash,
                'role' => 'customer',
            ]
        );

        // 3. Dedicated Store Personnel

        // 🌾 Diamond Stone Agri
        $users['agri_owner'] = User::updateOrCreate(
            ['phone' => '09130000001'],
            ['name' => 'ဦးမြင့်အောင် (ဆိုင်ပိုင်ရှင်)', 'email' => 'agri.owner@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['agri_manager'] = User::updateOrCreate(
            ['phone' => '09130000002'],
            ['name' => 'ကိုသက်နိုင် (ဆိုင်မန်နေဂျာ)', 'email' => 'agri.mgr@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['agri_cashier'] = User::updateOrCreate(
            ['phone' => '09130000003'],
            ['name' => 'မစန္ဒာ (အရောင်းစာရေး)', 'email' => 'agri.cashier@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['agri_wholesale'] = User::updateOrCreate(
            ['phone' => '09130000004'],
            ['name' => 'ဦးဖိုးထောင် (လက်ကားကုန်သည်)', 'email' => 'agri.ws@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );
        $users['agri_retail'] = User::updateOrCreate(
            ['phone' => '09130000005'],
            ['name' => 'ကိုအောင်ဇော် (လက်လီတောင်သူ)', 'email' => 'agri.retail@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );

        // 📱 DataPOS Mobile
        $users['mob_owner'] = User::updateOrCreate(
            ['phone' => '09150000001'],
            ['name' => 'ဦးဝင်းဗိုလ် (ဆိုင်ပိုင်ရှင်)', 'email' => 'mob.owner@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['mob_manager'] = User::updateOrCreate(
            ['phone' => '09150000002'],
            ['name' => 'မအိအိဖြိုး (ဆိုင်မန်နေဂျာ)', 'email' => 'mob.mgr@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['mob_cashier'] = User::updateOrCreate(
            ['phone' => '09150000003'],
            ['name' => 'ကိုကျော်ဇင် (အရောင်းစာရေး)', 'email' => 'mob.cashier@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['mob_wholesale'] = User::updateOrCreate(
            ['phone' => '09150000004'],
            ['name' => 'ဒေါ်တင်တင်ဝင်း (လက်ကားဝယ်သူ)', 'email' => 'mob.ws@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );
        $users['mob_retail'] = User::updateOrCreate(
            ['phone' => '09150000005'],
            ['name' => 'မသီတာ (လက်လီဝယ်သူ)', 'email' => 'mob.retail@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );

        // 📹 ProTech CCTV & PC
        $users['cctv_owner'] = User::updateOrCreate(
            ['phone' => '09170000001'],
            ['name' => 'ဦးဉာဏ်ထွန်း (ဆိုင်ပိုင်ရှင်)', 'email' => 'cctv.owner@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['cctv_manager'] = User::updateOrCreate(
            ['phone' => '09170000002'],
            ['name' => 'ကိုစိုးမိုး (ဆိုင်မန်နေဂျာ)', 'email' => 'cctv.mgr@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['cctv_cashier'] = User::updateOrCreate(
            ['phone' => '09170000003'],
            ['name' => 'ဒေါ်သဲနု (အရောင်းစာရေး)', 'email' => 'cctv.cashier@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['cctv_technician'] = User::updateOrCreate(
            ['phone' => '09170000004'],
            ['name' => 'ကိုဇော်ကြီး (တပ်ဆင်ရေး/နည်းပညာရှင်)', 'email' => 'cctv.tech@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['cctv_wholesale'] = User::updateOrCreate(
            ['phone' => '09170000005'],
            ['name' => 'ဦးမျိုးမင်း (လက်ကားဝယ်သူ)', 'email' => 'cctv.ws@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );
        $users['cctv_retail'] = User::updateOrCreate(
            ['phone' => '09170000006'],
            ['name' => 'ကိုထက်အောင် (လက်လီဝယ်သူ)', 'email' => 'cctv.retail@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );

        // 🔧 Shwe Pyi Thit Mobile & Service
        $users['spt_owner'] = User::updateOrCreate(
            ['phone' => '09160000001'],
            ['name' => 'ဦးမိုးကျော် (ဆိုင်ပိုင်ရှင်)', 'email' => 'spt.owner@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['spt_manager'] = User::updateOrCreate(
            ['phone' => '09160000002'],
            ['name' => 'ဒေါ်နွယ်နွယ် (ဆိုင်မန်နေဂျာ)', 'email' => 'spt.mgr@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['spt_cashier'] = User::updateOrCreate(
            ['phone' => '09160000003'],
            ['name' => 'မမေသူ (အရောင်းစာရေး)', 'email' => 'spt.cashier@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['spt_technician'] = User::updateOrCreate(
            ['phone' => '09160000004'],
            ['name' => 'ကိုမင်းမင်း (စက်ပြင်ဆရာကြီး / Service Master)', 'email' => 'spt.tech@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['spt_wholesale'] = User::updateOrCreate(
            ['phone' => '09160000005'],
            ['name' => 'ဦးဘသိန်း (လက်ကားဝယ်သူ)', 'email' => 'spt.ws@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );
        $users['spt_retail'] = User::updateOrCreate(
            ['phone' => '09160000006'],
            ['name' => 'မခင်စိုး (လက်လီဝယ်သူ)', 'email' => 'spt.retail@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );

        // 💊 Shwe Mingalar Pharmacy
        $users['pha_owner'] = User::updateOrCreate(
            ['phone' => '09180000001'],
            ['name' => 'ဒေါက်တာကျော်မင်း (ဆိုင်ပိုင်ရှင်/ဆေးဝါးပညာရှင်)', 'email' => 'pha.owner@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['pha_manager'] = User::updateOrCreate(
            ['phone' => '09180000002'],
            ['name' => 'ဒေါ်ရီရီမြင့် (ဆိုင်မန်နေဂျာ)', 'email' => 'pha.mgr@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['pha_cashier'] = User::updateOrCreate(
            ['phone' => '09180000003'],
            ['name' => 'မနွယ်နီ (ကောင်တာစာရေး)', 'email' => 'pha.cashier@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['pha_wholesale'] = User::updateOrCreate(
            ['phone' => '09180000004'],
            ['name' => 'ဦးသန်းထွန်း (ဆေးဆိုင်လက်ကား)', 'email' => 'pha.ws@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );
        $users['pha_retail'] = User::updateOrCreate(
            ['phone' => '09180000005'],
            ['name' => 'ဒေါ်ချိုချို (လက်လီဝယ်သူ)', 'email' => 'pha.retail@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );

        // 🍲 Si Taw Gyi Food Bar
        $users['food_owner'] = User::updateOrCreate(
            ['phone' => '09140000001'],
            ['name' => 'ဒေါ်နန်းခင်စိန် (ဆိုင်ပိုင်ရှင်)', 'email' => 'food.owner@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['food_manager'] = User::updateOrCreate(
            ['phone' => '09140000002'],
            ['name' => 'ဦးအောင်ကို (မန်နေဂျာ)', 'email' => 'food.mgr@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['food_cashier'] = User::updateOrCreate(
            ['phone' => '09140000003'],
            ['name' => 'မခင်လေး (ငွေကိုင်/အရောင်း)', 'email' => 'food.cashier@datapos.local', 'password' => $passwordHash, 'pos_pin' => $pinHash, 'role' => 'customer']
        );
        $users['food_retail'] = User::updateOrCreate(
            ['phone' => '09140000004'],
            ['name' => 'ကိုမင်းသန့် (စားသုံးသူ/ဧည့်သည်)', 'email' => 'food.retail@datapos.local', 'password' => $passwordHash, 'role' => 'customer']
        );

        return $users;
    }

    private function attachStoreUsers(Store $store, array $users): void
    {
        // Ensure default roles exist for this store
        StaffRole::bootstrapDefaultRoles($store);
        $roles = StaffRole::where('store_id', $store->id)->get()->keyBy('slug');
        $ownerRoleId = $roles['store_owner']->id ?? null;
        $managerRoleId = $roles['store_manager']->id ?? null;
        $cashierRoleId = $roles['cashier']->id ?? null;
        $techRoleId = $roles['technician']->id ?? null;

        // Attach strictly dedicated store-specific users
        $slug = $store->slug;
        $syncMap = [];

        if ($slug === 'diamond-stone-agri') {
            $syncMap[$users['agri_owner']->id] = ['role' => 'store_owner', 'staff_role_id' => $ownerRoleId, 'status' => 'active'];
            $syncMap[$users['agri_manager']->id] = ['role' => 'store_manager', 'staff_role_id' => $managerRoleId, 'status' => 'active'];
            $syncMap[$users['agri_cashier']->id] = ['role' => 'staff', 'staff_role_id' => $cashierRoleId, 'status' => 'active'];
            $syncMap[$users['agri_wholesale']->id] = ['role' => 'wholesale_customer', 'staff_role_id' => null, 'status' => 'active'];
            $syncMap[$users['agri_retail']->id] = ['role' => 'retail_customer', 'staff_role_id' => null, 'status' => 'active'];
        } elseif ($slug === 'datapos-mobile') {
            $syncMap[$users['mob_owner']->id] = ['role' => 'store_owner', 'staff_role_id' => $ownerRoleId, 'status' => 'active'];
            $syncMap[$users['mob_manager']->id] = ['role' => 'store_manager', 'staff_role_id' => $managerRoleId, 'status' => 'active'];
            $syncMap[$users['mob_cashier']->id] = ['role' => 'staff', 'staff_role_id' => $cashierRoleId, 'status' => 'active'];
            $syncMap[$users['mob_wholesale']->id] = ['role' => 'wholesale_customer', 'staff_role_id' => null, 'status' => 'active'];
            $syncMap[$users['mob_retail']->id] = ['role' => 'retail_customer', 'staff_role_id' => null, 'status' => 'active'];
        } elseif ($slug === 'cctv-network-computer') {
            $syncMap[$users['cctv_owner']->id] = ['role' => 'store_owner', 'staff_role_id' => $ownerRoleId, 'status' => 'active'];
            $syncMap[$users['cctv_manager']->id] = ['role' => 'store_manager', 'staff_role_id' => $managerRoleId, 'status' => 'active'];
            $syncMap[$users['cctv_cashier']->id] = ['role' => 'staff', 'staff_role_id' => $cashierRoleId, 'status' => 'active'];
            $syncMap[$users['cctv_technician']->id] = ['role' => 'staff', 'staff_role_id' => $techRoleId, 'status' => 'active'];
            $syncMap[$users['cctv_wholesale']->id] = ['role' => 'wholesale_customer', 'staff_role_id' => null, 'status' => 'active'];
            $syncMap[$users['cctv_retail']->id] = ['role' => 'retail_customer', 'staff_role_id' => null, 'status' => 'active'];
        } elseif ($slug === 'mobile-sale-service') {
            $syncMap[$users['spt_owner']->id] = ['role' => 'store_owner', 'staff_role_id' => $ownerRoleId, 'status' => 'active'];
            $syncMap[$users['spt_manager']->id] = ['role' => 'store_manager', 'staff_role_id' => $managerRoleId, 'status' => 'active'];
            $syncMap[$users['spt_cashier']->id] = ['role' => 'staff', 'staff_role_id' => $cashierRoleId, 'status' => 'active'];
            $syncMap[$users['spt_technician']->id] = ['role' => 'staff', 'staff_role_id' => $techRoleId, 'status' => 'active'];
            $syncMap[$users['spt_wholesale']->id] = ['role' => 'wholesale_customer', 'staff_role_id' => null, 'status' => 'active'];
            $syncMap[$users['spt_retail']->id] = ['role' => 'retail_customer', 'staff_role_id' => null, 'status' => 'active'];
        } elseif ($slug === 'pharmacy') {
            $syncMap[$users['pha_owner']->id] = ['role' => 'store_owner', 'staff_role_id' => $ownerRoleId, 'status' => 'active'];
            $syncMap[$users['pha_manager']->id] = ['role' => 'store_manager', 'staff_role_id' => $managerRoleId, 'status' => 'active'];
            $syncMap[$users['pha_cashier']->id] = ['role' => 'staff', 'staff_role_id' => $cashierRoleId, 'status' => 'active'];
            $syncMap[$users['pha_wholesale']->id] = ['role' => 'wholesale_customer', 'staff_role_id' => null, 'status' => 'active'];
            $syncMap[$users['pha_retail']->id] = ['role' => 'retail_customer', 'staff_role_id' => null, 'status' => 'active'];
        } elseif ($slug === 'si-taw-gyi-food-bar') {
            $syncMap[$users['food_owner']->id] = ['role' => 'store_owner', 'staff_role_id' => $ownerRoleId, 'status' => 'active'];
            $syncMap[$users['food_manager']->id] = ['role' => 'store_manager', 'staff_role_id' => $managerRoleId, 'status' => 'active'];
            $syncMap[$users['food_cashier']->id] = ['role' => 'staff', 'staff_role_id' => $cashierRoleId, 'status' => 'active'];
            $syncMap[$users['food_retail']->id] = ['role' => 'retail_customer', 'staff_role_id' => null, 'status' => 'active'];
        }

        $store->users()->sync($syncMap);
    }

    private function seedPaymentMethods(Store $store): void
    {
        StorePaymentMethod::updateOrCreate(
            ['store_id' => $store->id, 'type' => 'kpay'],
            [
                'name' => 'KBZ Pay (KPay)',
                'code' => 'KPAY',
                'account_name' => 'U Kyaw Kyaw (DataPOS)',
                'account_number' => '09111222333',
                'instructions' => 'KPay ငွေလွှဲပြီးပါက ပြေစာ Screenshot ကို Viber သို့ ပို့ပေးပါ။',
                'is_active' => true,
            ]
        );

        StorePaymentMethod::updateOrCreate(
            ['store_id' => $store->id, 'type' => 'wave'],
            [
                'name' => 'Wave Money (WavePay)',
                'code' => 'WAVE',
                'account_name' => 'U Kyaw Kyaw',
                'account_number' => '09111222333',
                'instructions' => 'Wave Money ဖြင့် လွယ်ကူစွာ ပေးချေနိုင်ပါသည်။',
                'is_active' => true,
            ]
        );

        StorePaymentMethod::updateOrCreate(
            ['store_id' => $store->id, 'type' => 'cod'],
            [
                'name' => 'Cash on Delivery (ပစ္စည်းရောက်မှငွေချေ)',
                'code' => 'COD',
                'account_name' => 'Cash on Delivery',
                'account_number' => 'COD',
                'instructions' => 'ပစ္စည်းရောက်မှ ငွေချေစနစ်။',
                'is_active' => true,
            ]
        );
    }

    private function seedDeliveryMethods(Store $store): void
    {
        StoreDeliveryMethod::updateOrCreate(
            ['store_id' => $store->id, 'name' => 'ရန်ကုန်မြို့တွင်း အမြန်ပို့ဆောင်မှု (Yangon Express)'],
            [
                'fee' => 2500,
                'estimated_days' => '၁ - ၂ ရက်',
                'is_active' => true,
            ]
        );

        StoreDeliveryMethod::updateOrCreate(
            ['store_id' => $store->id, 'name' => 'မန္တလေးမြို့တွင်း အမြန်ပို့ဆောင်မှု (Mandalay Express)'],
            [
                'fee' => 3000,
                'estimated_days' => '၁ - ၂ ရက်',
                'is_active' => true,
            ]
        );

        StoreDeliveryMethod::updateOrCreate(
            ['store_id' => $store->id, 'name' => 'နယ်ဝေး ကားဂိတ်ပို့ဆောင်မှု (Nationwide Highway Gate Delivery)'],
            [
                'fee' => 4000,
                'estimated_days' => '၂ - ၃ ရက်',
                'is_active' => true,
            ]
        );

        StoreDeliveryMethod::updateOrCreate(
            ['store_id' => $store->id, 'name' => 'ဆိုင်တွင် ကိုယ်တိုင် လာရောက်ထုတ်ယူခြင်း (Store Pickup)'],
            [
                'fee' => 0,
                'estimated_days' => 'ချက်ချင်း',
                'is_active' => true,
            ]
        );
    }

    private function getStorePersonnel(string $slug, array $users): array
    {
        return match ($slug) {
            'diamond-stone-agri' => [
                'owner' => $users['agri_owner'],
                'manager' => $users['agri_manager'],
                'cashier' => $users['agri_cashier'],
                'technician' => null,
                'wholesale' => $users['agri_wholesale'],
                'retail' => $users['agri_retail'],
            ],
            'datapos-mobile' => [
                'owner' => $users['mob_owner'],
                'manager' => $users['mob_manager'],
                'cashier' => $users['mob_cashier'],
                'technician' => null,
                'wholesale' => $users['mob_wholesale'],
                'retail' => $users['mob_retail'],
            ],
            'cctv-network-computer' => [
                'owner' => $users['cctv_owner'],
                'manager' => $users['cctv_manager'],
                'cashier' => $users['cctv_cashier'],
                'technician' => $users['cctv_technician'],
                'wholesale' => $users['cctv_wholesale'],
                'retail' => $users['cctv_retail'],
            ],
            'mobile-sale-service' => [
                'owner' => $users['spt_owner'],
                'manager' => $users['spt_manager'],
                'cashier' => $users['spt_cashier'],
                'technician' => $users['spt_technician'],
                'wholesale' => $users['spt_wholesale'],
                'retail' => $users['spt_retail'],
            ],
            'pharmacy' => [
                'owner' => $users['pha_owner'],
                'manager' => $users['pha_manager'],
                'cashier' => $users['pha_cashier'],
                'technician' => null,
                'wholesale' => $users['pha_wholesale'],
                'retail' => $users['pha_retail'],
            ],
            'si-taw-gyi-food-bar' => [
                'owner' => $users['food_owner'],
                'manager' => $users['food_manager'],
                'cashier' => $users['food_cashier'],
                'technician' => null,
                'wholesale' => null,
                'retail' => $users['food_retail'],
            ],
            default => [
                'owner' => $users['manager'],
                'manager' => $users['manager'],
                'cashier' => $users['cashier'],
                'technician' => $users['technician'],
                'wholesale' => $users['wholesale_customer'],
                'retail' => $users['retail_customer'],
            ],
        };
    }

    private function seedSampleOrders(Store $store, array $storeStaff, array $products): void
    {
        if (empty($products))
            return;

        $p1 = $products[0];
        $p2 = $products[1] ?? $products[0];
        $retailUser = $storeStaff['retail'] ?? $storeStaff['owner'];
        $wholesaleUser = $storeStaff['wholesale'] ?? $retailUser;

        // 1. Pending Contact Order
        $order1 = Order::create([
            'store_id' => $store->id,
            'user_id' => $retailUser->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(6)),
            'customer_name' => $retailUser->name,
            'customer_phone' => $retailUser->phone,
            'customer_address' => 'အမှတ် (၂၅)၊ လှည်းတန်းလမ်း၊ ကမာရွတ်၊ ရန်ကုန်။',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => $p1->retail_price,
            'agreed_amount' => $p1->retail_price,
            'status' => 'pending_contact',
        ]);
        OrderItem::create([
            'order_id' => $order1->id,
            'product_name' => $p1->name,
            'unit_price' => $p1->retail_price,
            'quantity' => 1,
            'subtotal' => $p1->retail_price,
        ]);

        // 2. Confirmed Wholesale Order
        $order2 = Order::create([
            'store_id' => $store->id,
            'user_id' => $wholesaleUser->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(6)),
            'customer_name' => $wholesaleUser->name,
            'customer_phone' => $wholesaleUser->phone,
            'customer_address' => 'အောင်မင်္ဂလာ ကားဂိတ်၊ ရန်ကုန်။',
            'contact_channel' => 'telegram',
            'pricing_type' => 'wholesale',
            'total_amount' => $p1->wholesale_price * 5 + $p2->wholesale_price * 3,
            'agreed_amount' => $p1->wholesale_price * 5 + $p2->wholesale_price * 3,
            'status' => 'confirmed',
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_name' => $p1->name,
            'unit_price' => $p1->wholesale_price,
            'quantity' => 5,
            'subtotal' => $p1->wholesale_price * 5,
        ]);
        OrderItem::create([
            'order_id' => $order2->id,
            'product_name' => $p2->name,
            'unit_price' => $p2->wholesale_price,
            'quantity' => 3,
            'subtotal' => $p2->wholesale_price * 3,
        ]);

        // 3. Delivered Order
        $order3 = Order::create([
            'store_id' => $store->id,
            'user_id' => $retailUser->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(6)),
            'customer_name' => $retailUser->name,
            'customer_phone' => $retailUser->phone,
            'customer_address' => '၇၃ လမ်း၊ မန္တလေးမြို့။',
            'contact_channel' => 'viber',
            'pricing_type' => 'retail',
            'total_amount' => $p2->retail_price * 2,
            'agreed_amount' => $p2->retail_price * 2,
            'status' => 'delivered',
        ]);
        OrderItem::create([
            'order_id' => $order3->id,
            'product_name' => $p2->name,
            'unit_price' => $p2->retail_price,
            'quantity' => 2,
            'subtotal' => $p2->retail_price * 2,
        ]);
    }

    private function seedSamplePosSales(Store $store, array $storeStaff, array $products, Branch $branch): void
    {
        if (!Schema::hasTable('pos_sales') || empty($products))
            return;

        $cashierUser = $storeStaff['cashier'] ?? $storeStaff['manager'];
        $retailUser = $storeStaff['retail'] ?? $storeStaff['owner'];

        $p1 = $products[0];
        $p2 = $products[1] ?? $products[0];
        $lineTotal = (float) ($p1->retail_price * 2);

        $saleId = DB::table('pos_sales')->insertGetId([
            'store_id' => $store->id,
            'branch_id' => $branch->id,
            'cashier_id' => $cashierUser->id,
            'customer_id' => $retailUser->id,
            'receipt_number' => 'REC-' . strtoupper(Str::random(8)),
            'status' => 'posted',
            'subtotal' => $lineTotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $lineTotal,
            'notes' => 'Walk-in Cash Sale (ငွေသားအရောင်း)',
            'posted_at' => now()->subHours(2),
            'created_by' => $cashierUser->id,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        if (Schema::hasTable('pos_sale_items')) {
            DB::table('pos_sale_items')->insert([
                'pos_sale_id' => $saleId,
                'product_id' => $p1->id,
                'product_name' => $p1->name,
                'sku' => $p1->sku,
                'unit_price' => (float) $p1->retail_price,
                'quantity' => 2,
                'line_total' => $lineTotal,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);
        }
    }

    private function storesDefinition(): array
    {
        return [
            // 1. Diamond Stone Agricultural Inputs & Fertilizer
            'diamond-stone-agri' => [
                'store' => [
                    'name' => 'Agriculture and fertilizer store',
                    'business_type' => 'agriculture_inputs',
                    'phone' => '09778899111',
                    'viber_number' => '09778899111',
                    'telegram_username' => 'diamond_stone_agri',
                ],
                'setting' => [
                    'tagline' => 'စိုက်ပျိုးရေးသုံး ဆေး၊ မြေသြဇာ၊ မျိုးစေ့နှင့် ကိရိယာ လက်လီ/လက်ကား',
                    'address' => 'အမှတ် (၁၂)၊ ကုန်သည်လမ်း၊ ပြည်ကြီးတံခွန်မြို့နယ်၊ မန္တလေးမြို့။',
                ],
                'warehouses' => [
                    ['name' => 'ပင်မ ပစ္စည်းဂိုဒေါင်', 'code' => 'MAIN'],
                    ['name' => 'ပိုးသတ်ဆေးနှင့် ဓာတ်မြေသြဇာ သီးသန့်ဂိုဒေါင်', 'code' => 'CHEM'],
                    ['name' => 'မျိုးစေ့နှင့် စိုက်ပျိုးရေး ကိရိယာခန်း', 'code' => 'SEED'],
                ],
                'categories' => [
                    ['key' => 'fertilizer', 'name' => 'ဓာတ်မြေသြဇာနှင့် ရွက်ဖျန်းအားဆေး', 'slug' => 'fertilizers', 'icon' => '🌱'],
                    ['key' => 'pesticide', 'name' => 'ပိုးသတ်ဆေးနှင့် မှိုသတ်ဆေးများ', 'slug' => 'pesticides', 'icon' => '🛡️'],
                    ['key' => 'seeds', 'name' => 'စိုက်ပျိုးရေး မျိုးစေ့များ', 'slug' => 'crop-seeds', 'icon' => '🌾'],
                    ['key' => 'tools', 'name' => 'ရေဖျန်းပိုက်နှင့် စိုက်ပျိုးရေးသုံးပစ္စည်းများ', 'slug' => 'farming-tools', 'icon' => '🚜'],
                ],
                'brands' => [
                    ['key' => 'diamond', 'name' => 'Diamond Star (စိန်ကြယ်)', 'slug' => 'diamond-star'],
                    ['key' => 'awba', 'name' => 'Awba (သြဘာ)', 'slug' => 'myanmar-awba'],
                    ['key' => 'maru', 'name' => 'Marubeni Agri', 'slug' => 'marubeni-agri'],
                    ['key' => 'golden_lion', 'name' => 'Golden Lion (ရွှေခြင်္သေ့)', 'slug' => 'golden-lion'],
                ],
                'suppliers' => [
                    ['key' => 'mandalay_agri', 'name' => 'မန္တလေး စိုက်ပျိုးရေး သွင်းအားစု လက်ကားဖြန့်ချိရေး', 'phone' => '09790000001', 'address' => 'မန္တလေးမြို့'],
                    ['key' => 'awba_dist', 'name' => 'သြဘာ စိုက်ပျိုးရေး ဆေးနှင့် ဓာတ်မြေသြဇာ ကုမ္ပဏီ', 'phone' => '09790000002', 'address' => 'ရန်ကုန်မြို့'],
                ],
                'products' => [
                    ['sku' => 'AGRI-NPK-151515', 'name' => 'စိန်ကြယ် NPK 15-15-15 ကွန်ပေါင်း မြေသြဇာ (၅၀ ကီလို)', 'category_key' => 'fertilizer', 'brand_key' => 'diamond', 'supplier_key' => 'mandalay_agri', 'warehouse_code' => 'CHEM', 'retail_price' => 125000, 'wholesale_price' => 118000, 'purchase_cost' => 105000, 'opening_stock' => 50, 'reorder_level' => 10, 'is_featured' => true],
                    ['sku' => 'AGRI-UREA-FOIL', 'name' => 'သြဘာ အပင်သန် ရွက်ဖျန်းအားဆေး (၁ လီတာ)', 'category_key' => 'fertilizer', 'brand_key' => 'awba', 'supplier_key' => 'awba_dist', 'warehouse_code' => 'CHEM', 'retail_price' => 28000, 'wholesale_price' => 24500, 'purchase_cost' => 19500, 'opening_stock' => 100, 'reorder_level' => 20, 'is_featured' => true],
                    ['sku' => 'AGRI-PEST-CYPER', 'name' => 'ဆိုက်ပါမက်သရင် ပိုးသတ်ဆေး (၅၀၀ စီစီ)', 'category_key' => 'pesticide', 'brand_key' => 'awba', 'supplier_key' => 'awba_dist', 'warehouse_code' => 'CHEM', 'retail_price' => 16500, 'wholesale_price' => 14000, 'purchase_cost' => 11000, 'opening_stock' => 80, 'reorder_level' => 15],
                    ['sku' => 'AGRI-SEED-CABBAGE', 'name' => 'ရွှေခြင်္သေ့ ထိုင်ဝမ် ပန်းဂေါ်ဖီထုပ် မျိုးစေ့ (၅၀ ဂရမ်)', 'category_key' => 'seeds', 'brand_key' => 'golden_lion', 'supplier_key' => 'mandalay_agri', 'warehouse_code' => 'SEED', 'retail_price' => 14000, 'wholesale_price' => 12000, 'purchase_cost' => 9000, 'opening_stock' => 120, 'reorder_level' => 30],
                    ['sku' => 'AGRI-SPRAYER-16L', 'name' => '၁၆ လီတာ ဘက်ထရီသုံး ပိုးသတ်ဆေးဖျန်းပုံး', 'category_key' => 'tools', 'brand_key' => 'diamond', 'supplier_key' => 'mandalay_agri', 'warehouse_code' => 'MAIN', 'retail_price' => 85000, 'wholesale_price' => 78000, 'purchase_cost' => 62000, 'opening_stock' => 25, 'reorder_level' => 5, 'warranty' => '၆ လ အာမခံ', 'is_featured' => true],
                ],
            ],

            // 2. DataPOS Mobile & Accessories
            'datapos-mobile' => [
                'store' => [
                    'name' => 'DataPOS မိုဘိုင်းဖုန်းနှင့် ဆက်စပ်ပစ္စည်း အရောင်းဆိုင်',
                    'business_type' => 'mobile_accessories',
                    'phone' => '09123456789',
                    'viber_number' => '09123456789',
                    'telegram_username' => 'datapos_mobile',
                ],
                'setting' => [
                    'tagline' => 'စမတ်ဖုန်း၊ ကာဗာ၊ မှန်ကပ်၊ အားသွင်းကြိုးနှင့် ဆက်စပ်ပစ္စည်း စုံလင်စွာရရှိနိုင်သောဆိုင်',
                    'address' => 'အမှတ် (၄၅)၊ လှည်းတန်းလမ်းမကြီး၊ ကမာရွတ်မြို့နယ်၊ ရန်ကုန်မြို့။',
                ],
                'warehouses' => [
                    ['name' => 'ဆိုင်ရှေ့ Showroom ကောင်တာ', 'code' => 'FRONT'],
                    ['name' => 'စတော့ပစ္စည်း သိုလှောင်ရုံ', 'code' => 'BACK'],
                ],
                'categories' => [
                    ['key' => 'phones', 'name' => 'စမတ်ဖုန်းများ (Smartphones)', 'slug' => 'smartphones', 'icon' => '📱'],
                    ['key' => 'glass', 'name' => 'ဖန်သားမှန်ကပ်များ (Tempered Glass)', 'slug' => 'tempered-glass', 'icon' => '🛡️'],
                    ['key' => 'charging', 'name' => 'Charger နှင့် Data Cables', 'slug' => 'chargers-cables', 'icon' => '⚡'],
                    ['key' => 'audio', 'name' => 'နားကြပ်နှင့် Bluetooth Speaker', 'slug' => 'audio-accessories', 'icon' => '🎧'],
                ],
                'brands' => [
                    ['key' => 'apple', 'name' => 'Apple', 'slug' => 'apple'],
                    ['key' => 'samsung', 'name' => 'Samsung', 'slug' => 'samsung'],
                    ['key' => 'xiaomi', 'name' => 'Xiaomi', 'slug' => 'xiaomi'],
                    ['key' => 'anker', 'name' => 'Anker', 'slug' => 'anker'],
                ],
                'suppliers' => [
                    ['key' => 'ygn_mobile', 'name' => 'ရန်ကုန် မိုဘိုင်း လက်ကား ကုန်တိုက်', 'phone' => '09250000001', 'address' => 'ရန်ကုန်မြို့'],
                    ['key' => 'accessory_hub', 'name' => 'အာရှ ဆက်စပ်ပစ္စည်း လက်ကားဖြန့်ချိရေး', 'phone' => '09250000002', 'address' => 'ရန်ကုန်မြို့'],
                ],
                'products' => [
                    ['sku' => 'MOB-IP15-128-BLK', 'name' => 'Apple iPhone 15 128GB (Black) Official', 'category_key' => 'phones', 'brand_key' => 'apple', 'supplier_key' => 'ygn_mobile', 'warehouse_code' => 'BACK', 'retail_price' => 2650000, 'wholesale_price' => 2580000, 'purchase_cost' => 2480000, 'opening_stock' => 5, 'reorder_level' => 1, 'warranty' => '၁ နှစ် အာမခံ', 'is_featured' => true],
                    ['sku' => 'MOB-SAM-A15-128', 'name' => 'Samsung Galaxy A15 (8/128GB)', 'category_key' => 'phones', 'brand_key' => 'samsung', 'supplier_key' => 'ygn_mobile', 'warehouse_code' => 'FRONT', 'retail_price' => 520000, 'wholesale_price' => 495000, 'purchase_cost' => 460000, 'opening_stock' => 12, 'reorder_level' => 3, 'warranty' => '၁ နှစ် အာမခံ', 'is_featured' => true],
                    ['sku' => 'MOB-GLASS-IP15', 'name' => 'iPhone 15 9D Full Cover Tempered Glass', 'category_key' => 'glass', 'brand_key' => 'anker', 'supplier_key' => 'accessory_hub', 'warehouse_code' => 'FRONT', 'retail_price' => 5000, 'wholesale_price' => 3500, 'purchase_cost' => 1800, 'opening_stock' => 150, 'reorder_level' => 30],
                    ['sku' => 'MOB-ANKER-20W', 'name' => 'Anker 20W PD Fast Charging Adapter', 'category_key' => 'charging', 'brand_key' => 'anker', 'supplier_key' => 'accessory_hub', 'warehouse_code' => 'FRONT', 'retail_price' => 42000, 'wholesale_price' => 38000, 'purchase_cost' => 32000, 'opening_stock' => 30, 'reorder_level' => 5, 'warranty' => '၁၈ လ အာမခံ', 'is_featured' => true],
                    ['sku' => 'MOB-CABLE-TYPEC', 'name' => 'Anker 60W Type-C to Type-C Cable (1m)', 'category_key' => 'charging', 'brand_key' => 'anker', 'supplier_key' => 'accessory_hub', 'warehouse_code' => 'FRONT', 'retail_price' => 18500, 'wholesale_price' => 16000, 'purchase_cost' => 12500, 'opening_stock' => 60, 'reorder_level' => 15],
                ],
            ],

            // 3. ProTech CCTV & Computer Network
            'cctv-network-computer' => [
                'store' => [
                    'name' => 'ProTech လုံခြုံရေးကင်မရာ၊ ကွန်ပျူတာနှင့် ကွန်ရက်ဆိုင်',
                    'business_type' => 'cctv_network_computer',
                    'phone' => '09445566777',
                    'viber_number' => '09445566777',
                    'telegram_username' => 'protech_cctv_mm',
                ],
                'setting' => [
                    'tagline' => 'CCTV လုံခြုံရေးကင်မရာ၊ Network၊ ကွန်ပျူတာအရောင်းနှင့် တပ်ဆင်ရေးဝန်ဆောင်မှု',
                    'address' => 'အမှတ် (၈၈)၊ အနော်ရထာလမ်း၊ ကျောက်တံတားမြို့နယ်၊ ရန်ကုန်မြို့။',
                ],
                'warehouses' => [
                    ['name' => 'Showroom ကောင်တာ', 'code' => 'SHOW'],
                    ['name' => 'Project ပစ္စည်း သိုလှောင်ရုံ', 'code' => 'PROJECT'],
                ],
                'categories' => [
                    ['key' => 'cctv', 'name' => 'CCTV ကင်မရာနှင့် စက်များ', 'slug' => 'cctv-systems', 'icon' => '📹'],
                    ['key' => 'network', 'name' => 'Network Routers & Switches', 'slug' => 'network-gear', 'icon' => '📡'],
                    ['key' => 'computer', 'name' => 'Desktops & Laptops', 'slug' => 'computers-laptops', 'icon' => '💻'],
                    ['key' => 'service', 'name' => 'တပ်ဆင်ရေး ဝန်ဆောင်မှုများ', 'slug' => 'installation-service', 'icon' => '🛠️'],
                ],
                'brands' => [
                    ['key' => 'hikvision', 'name' => 'Hikvision', 'slug' => 'hikvision'],
                    ['key' => 'dahua', 'name' => 'Dahua', 'slug' => 'dahua'],
                    ['key' => 'tplink', 'name' => 'TP-Link', 'slug' => 'tp-link'],
                    ['key' => 'lenovo', 'name' => 'Lenovo', 'slug' => 'lenovo'],
                ],
                'suppliers' => [
                    ['key' => 'security_supplier', 'name' => 'မြန်မာ လုံခြုံရေး ကင်မရာ လက်ကား တင်သွင်းသူ', 'phone' => '09270000001', 'address' => 'ရန်ကုန်မြို့'],
                    ['key' => 'it_distributor', 'name' => 'အိုင်တီ ကွန်ပျူတာ ဖြန့်ချိရေး ကုမ္ပဏီ', 'phone' => '09270000002', 'address' => 'ရန်ကုန်မြို့'],
                ],
                'products' => [
                    ['sku' => 'CCTV-HIK-2MP-DOME', 'name' => 'Hikvision 2MP Audio Dome Camera (ColorVu)', 'category_key' => 'cctv', 'brand_key' => 'hikvision', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 85000, 'wholesale_price' => 79000, 'purchase_cost' => 65000, 'opening_stock' => 40, 'reorder_level' => 10, 'warranty' => '၁ နှစ် အာမခံ', 'is_featured' => true],
                    ['sku' => 'CCTV-DAH-NVR-8CH', 'name' => 'Dahua 8-Channel 4K NVR Recorder', 'category_key' => 'cctv', 'brand_key' => 'dahua', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 185000, 'wholesale_price' => 175000, 'purchase_cost' => 148000, 'opening_stock' => 15, 'reorder_level' => 3, 'warranty' => '၁ နှစ် အာမခံ'],
                    ['sku' => 'NET-TPL-ARCHER-C6', 'name' => 'TP-Link Archer C6 Gigabit Dual-Band WiFi Router', 'category_key' => 'network', 'brand_key' => 'tplink', 'supplier_key' => 'it_distributor', 'warehouse_code' => 'SHOW', 'retail_price' => 88000, 'wholesale_price' => 83000, 'purchase_cost' => 70000, 'opening_stock' => 20, 'reorder_level' => 5, 'warranty' => '၂ နှစ် အာမခံ', 'is_featured' => true],
                    ['sku' => 'PC-LEN-I5-SET', 'name' => 'Lenovo Core i5 12th Gen Desktop PC Set', 'category_key' => 'computer', 'brand_key' => 'lenovo', 'supplier_key' => 'it_distributor', 'warehouse_code' => 'SHOW', 'retail_price' => 1250000, 'wholesale_price' => 1190000, 'purchase_cost' => 1080000, 'opening_stock' => 8, 'reorder_level' => 2, 'warranty' => '၁ နှစ် အာမခံ', 'is_featured' => true],
                    ['sku' => 'SVC-CCTV-4CAM', 'name' => 'CCTV ၄ လုံး တပ်ဆင်ရေး ဝန်ဆောင်မှု Package', 'category_key' => 'service', 'brand_key' => 'hikvision', 'supplier_key' => 'security_supplier', 'warehouse_code' => 'PROJECT', 'retail_price' => 250000, 'wholesale_price' => 250000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'is_ecommerce' => false],
                ],
            ],

            // 4. Shwe Pyi Thit Mobile & Service
            'mobile-sale-service' => [
                'store' => [
                    'name' => ' Mobile & Service',
                    'business_type' => 'mobile_sale_service',
                    'phone' => '09556677888',
                    'viber_number' => '09556677888',
                    'telegram_username' => 'shwepyithit_service',
                ],
                'setting' => [
                    'tagline' => 'စမတ်ဖုန်းအရောင်း၊ စက်ပြင် Service လက်မှတ်နှင့် Spare Parts ပစ္စည်းစုံ',
                    'address' => 'အမှတ် (၁၀)၊ ဗိုလ်ချုပ်လမ်း၊ ပြည်မြို့။',
                ],
                'warehouses' => [
                    ['name' => 'ဖုန်းအရောင်း ကောင်တာ', 'code' => 'SHOW'],
                    ['name' => 'စက်ပြင် အပိုပစ္စည်း စတော့', 'code' => 'SPARE'],
                ],
                'categories' => [
                    ['key' => 'phones', 'name' => 'စမတ်ဖုန်း အသစ်နှင့် တစ်ပတ်ရစ်', 'slug' => 'service-smartphones', 'icon' => '📱'],
                    ['key' => 'parts', 'name' => 'ဖုန်းမျက်နှာပြင်နှင့် ဘက်ထရီ အပိုပစ္စည်း', 'slug' => 'phone-parts', 'icon' => '🔋'],
                    ['key' => 'service', 'name' => 'စက်ပြင် ဝန်ဆောင်မှု လုပ်ခ', 'slug' => 'repair-fees', 'icon' => '🛠️'],
                ],
                'brands' => [
                    ['key' => 'apple', 'name' => 'Apple', 'slug' => 'repair-apple'],
                    ['key' => 'samsung', 'name' => 'Samsung', 'slug' => 'repair-samsung'],
                    ['key' => 'oppo', 'name' => 'OPPO', 'slug' => 'oppo'],
                ],
                'suppliers' => [
                    ['key' => 'parts_wh', 'name' => 'မိုဘိုင်း အပိုပစ္စည်း လက်ကား တိုက်', 'phone' => '09260000002', 'address' => 'ရန်ကုန်မြို့'],
                ],
                'products' => [
                    ['sku' => 'MSS-OPPO-A58', 'name' => 'OPPO A58 (6/128GB) Glowing Black', 'category_key' => 'phones', 'brand_key' => 'oppo', 'supplier_key' => 'parts_wh', 'warehouse_code' => 'SHOW', 'retail_price' => 610000, 'wholesale_price' => 585000, 'purchase_cost' => 545000, 'opening_stock' => 6, 'reorder_level' => 2, 'warranty' => '၁ နှစ် အာမခံ', 'is_featured' => true],
                    ['sku' => 'MSS-IP-BAT-11', 'name' => 'iPhone 11 Original Battery Replacement', 'category_key' => 'parts', 'brand_key' => 'apple', 'supplier_key' => 'parts_wh', 'warehouse_code' => 'SPARE', 'retail_price' => 58000, 'wholesale_price' => 52000, 'purchase_cost' => 39000, 'opening_stock' => 20, 'reorder_level' => 5, 'warranty' => '၆ လ အာမခံ', 'is_featured' => true],
                    ['sku' => 'MSS-SAM-A12-LCD', 'name' => 'Samsung A12 Original Display LCD Set', 'category_key' => 'parts', 'brand_key' => 'samsung', 'supplier_key' => 'parts_wh', 'warehouse_code' => 'SPARE', 'retail_price' => 72000, 'wholesale_price' => 68000, 'purchase_cost' => 53000, 'opening_stock' => 15, 'reorder_level' => 4, 'warranty' => '၃ လ အာမခံ', 'is_featured' => true],
                    ['sku' => 'MSS-SVC-LCD-CHANGE', 'name' => 'ဖုန်းမှန်လဲ ဝန်ဆောင်မှု လုပ်ခ (LCD Service Fee)', 'category_key' => 'service', 'brand_key' => 'oppo', 'supplier_key' => 'parts_wh', 'warehouse_code' => 'SPARE', 'retail_price' => 25000, 'wholesale_price' => 25000, 'purchase_cost' => 0, 'opening_stock' => 1, 'reorder_level' => 0, 'product_type' => 'service', 'is_ecommerce' => false],
                ],
            ],

            // 5. Shwe Thitsar Pharmacy & Healthcare
            'pharmacy' => [
                'store' => [
                    'name' => 'Pharmacy & Healthcare',
                    'business_type' => 'pharmacy',
                    'phone' => '09334455666',
                    'viber_number' => '09334455666',
                    'telegram_username' => 'shwemingalar_pharma',
                ],
                'setting' => [
                    'tagline' => 'ဆေးဝါး၊ အားဆေးနှင့် ဆေးဘက်ဆိုင်ရာ ကျန်းမာရေးသုံး ကိရိယာများ',
                    'address' => 'အမှတ် (၅)၊ ဆေးရုံလမ်း၊ တောင်ကြီးမြို့။',
                ],
                'warehouses' => [
                    ['name' => 'အရောင်း ကောင်တာ စင်', 'code' => 'SHELF'],
                    ['name' => 'အအေးခန်း ဆေးဝါး စတော့', 'code' => 'COLD'],
                ],
                'categories' => [
                    ['key' => 'medicine', 'name' => 'သောက်ဆေးနှင့် ဆေးဝါးများ', 'slug' => 'general-medicine', 'icon' => '💊'],
                    ['key' => 'supplement', 'name' => 'ဗီတာမင်နှင့် ဖြည့်စွက်အာဟာရများ', 'slug' => 'vitamins-supplements', 'icon' => '✨'],
                    ['key' => 'device', 'name' => 'ကျန်းမာရေးနှင့် ဆေးဘက်သုံး ကိရိယာများ', 'slug' => 'medical-equipment', 'icon' => '🩺'],
                ],
                'brands' => [
                    ['key' => 'mega', 'name' => 'Mega We Care', 'slug' => 'mega-we-care'],
                    ['key' => 'omron', 'name' => 'Omron Healthcare', 'slug' => 'omron'],
                    ['key' => 'generic_pharma', 'name' => 'Myanmar Pharmaceutical', 'slug' => 'myanmar-pharma'],
                ],
                'suppliers' => [
                    ['key' => 'pharma_wh', 'name' => 'တောင်ကြီး ဆေးဝါး ဖြန့်ချိရေး ကုန်တိုက်', 'phone' => '09280000001', 'address' => 'တောင်ကြီးမြို့'],
                ],
                'products' => [
                    ['sku' => 'PHA-PARA-500', 'name' => 'Paracetamol 500mg (၁၀ တောင့်ကတ်)', 'category_key' => 'medicine', 'brand_key' => 'generic_pharma', 'supplier_key' => 'pharma_wh', 'warehouse_code' => 'SHELF', 'retail_price' => 1500, 'wholesale_price' => 1200, 'purchase_cost' => 800, 'opening_stock' => 400, 'reorder_level' => 80, 'is_featured' => true],
                    ['sku' => 'PHA-VITC-100', 'name' => 'Nat C 1000mg Vitamin C (အလုံး ၃၀)', 'category_key' => 'supplement', 'brand_key' => 'mega', 'supplier_key' => 'pharma_wh', 'warehouse_code' => 'SHELF', 'retail_price' => 26500, 'wholesale_price' => 24000, 'purchase_cost' => 20000, 'opening_stock' => 50, 'reorder_level' => 10, 'is_featured' => true],
                    ['sku' => 'PHA-OMRON-BP', 'name' => 'Omron Digital Blood Pressure Monitor (သွေးပေါင်ချိန်စက်)', 'category_key' => 'device', 'brand_key' => 'omron', 'supplier_key' => 'pharma_wh', 'warehouse_code' => 'SHELF', 'retail_price' => 145000, 'wholesale_price' => 135000, 'purchase_cost' => 115000, 'opening_stock' => 15, 'reorder_level' => 3, 'warranty' => '၃ နှစ် အာမခံ', 'is_featured' => true],
                ],
            ],

            // 6. Si Taw Gyi Food Bar & Restaurant
            'si-taw-gyi-food-bar' => [
                'store' => [
                    'name' => ' Food Bar & Restaurant',
                    'business_type' => 'restaurant',
                    'phone' => '09667788999',
                    'viber_number' => '09667788999',
                    'telegram_username' => 'sitawgyi_foodbar',
                ],
                'setting' => [
                    'tagline' => 'အရသာရှိသော မြန်မာ/ရှမ်း ရိုးရာ အစားအသောက်နှင့် လတ်ဆတ်သော အဖျော်ယမကာများ',
                    'address' => 'အမှတ် (၃)၊ ကမ်းနားလမ်း၊ ပုသိမ်မြို့။',
                ],
                'warehouses' => [
                    ['name' => 'မီးဖိုချောင် ကုန်ကြမ်းခန်း', 'code' => 'KITCHEN'],
                    ['name' => 'အရောင်း ကောင်တာ ဘား', 'code' => 'BAR'],
                ],
                'categories' => [
                    ['key' => 'food', 'name' => 'ထမင်းနှင့် ဟင်းပွဲများ', 'slug' => 'rice-dishes', 'icon' => '🍲'],
                    ['key' => 'noodles', 'name' => 'ခေါက်ဆွဲနှင့် မုန့်ဟင်းခါး', 'slug' => 'noodles-soups', 'icon' => '🍜'],
                    ['key' => 'drinks', 'name' => 'လတ်ဆတ် အဖျော်ယမကာ', 'slug' => 'fresh-drinks', 'icon' => '🥤'],
                ],
                'brands' => [
                    ['key' => 'house', 'name' => 'စည်တော်ကြီး လက်ရာ', 'slug' => 'sitawgyi-house'],
                ],
                'suppliers' => [
                    ['key' => 'market', 'name' => 'ပုသိမ် မြို့မဈေးကြီး ကုန်စိမ်း/ကုန်ခြောက်', 'phone' => '09290000001', 'address' => 'ပုသိမ်မြို့'],
                ],
                'products' => [
                    ['sku' => 'FOOD-CHICKEN-RICE', 'name' => 'စည်တော်ကြီး ကြက်သားဆီပြန် ထမင်းပွဲ', 'category_key' => 'food', 'brand_key' => 'house', 'supplier_key' => 'market', 'warehouse_code' => 'KITCHEN', 'retail_price' => 6500, 'wholesale_price' => 6500, 'purchase_cost' => 3800, 'opening_stock' => 40, 'reorder_level' => 10, 'product_type' => 'service', 'is_featured' => true],
                    ['sku' => 'FOOD-SHAN-NOODLE', 'name' => 'ရှမ်းရိုးရာ ခေါက်ဆွဲ (ကြက်/ဝက်)', 'category_key' => 'noodles', 'brand_key' => 'house', 'supplier_key' => 'market', 'warehouse_code' => 'KITCHEN', 'retail_price' => 4500, 'wholesale_price' => 4500, 'purchase_cost' => 2400, 'opening_stock' => 50, 'reorder_level' => 15, 'product_type' => 'service', 'is_featured' => true],
                    ['sku' => 'DRINK-AVOCADO', 'name' => 'လတ်ဆတ် ထောပတ်သီး ဖျော်ရည်', 'category_key' => 'drinks', 'brand_key' => 'house', 'supplier_key' => 'market', 'warehouse_code' => 'BAR', 'retail_price' => 3500, 'wholesale_price' => 3500, 'purchase_cost' => 1800, 'opening_stock' => 60, 'reorder_level' => 20, 'product_type' => 'service', 'is_featured' => true],
                ],
            ],
        ];
    }
}
