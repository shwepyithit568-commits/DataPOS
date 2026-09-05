<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StaffRole;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use App\POS\Services\StoreLocationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Local UAT Test Data Seeder
 * ===========================
 *
 * Creates realistic, production-grade test data for Local User Acceptance Testing.
 * Auto-provisions staff roles, master data presets, warehouses, payment/delivery methods,
 * suppliers, complete inventory opening stock movements, and cross-store isolation data.
 *
 * **SAFETY GUARDS:**
 *   - Will ABORT if APP_ENV is 'production' or 'staging'.
 *   - Requires ALLOW_UAT_SEEDING=true in .env to run.
 *   - All passwords are set to 'password' for easy testing.
 *   - Do NOT use this seeder on any production or public-facing server.
 *
 * Run with:
 *   php artisan db:seed --class=UatSeeder
 */
class UatSeeder extends Seeder
{
    private int $storeId;
    private int $storeBId;
    private int $ownerId;
    private int $storeOwnerId;
    private int $managerId;
    private int $cashierId;
    private int $technicianId;
    private int $stockKeeperId;
    private int $accountantId;
    private int $wholesaleApprovedId;
    private int $wholesalePendingId;
    private int $retailCustomerId;
    private int $storeBManagerId;

    public function run(): void
    {
        $this->guardEnvironment();

        $this->command?->info('🌱 Seeding UAT test data...');

        $now = Carbon::now();

        $this->syncSeederAssets();
        $this->createStore($now);
        $this->createStorefrontSettings($now);
        $this->createHomeBanners($now);
        $this->bootstrapStaffRoles();
        $this->createLocationsAndWarehouses();
        $this->createStorePaymentAndDeliveryMethods($now);
        $this->createStoreB($now);
        $this->createUsers($now);
        $this->assignStoreRoles();
        $this->assignStoreBRoles();
        $this->importMasterDataPresets();
        $this->createCategories($now);
        $this->createBrands($now);
        $this->createSuppliers($now);
        $this->createProducts($now);
        $this->createGlassFinderItems($now);
        $this->createWholesaleApplications($now);
        $this->createOrders($now);
        $this->createStoreBData($now);
        $this->seedBlogAndAuditLogs();

        $this->command?->info('✅ UAT test data seeded successfully.');
    }

    private function guardEnvironment(): void
    {
        if (!app()->environment(['local', 'testing', 'uat'])) {
            throw new \RuntimeException(
                'UatSeeder is NOT safe for non-local/testing/uat environments (' . app()->environment() . '). Aborting.'
            );
        }

        if (!config('app.allow_uat_seeding')) {
            throw new \RuntimeException(
                'UatSeeder requires config("app.allow_uat_seeding") to be true. Set ALLOW_UAT_SEEDING=true in your .env file.'
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Asset Synchronization                                              */
    /* ------------------------------------------------------------------ */

    private function syncSeederAssets(): void
    {
        $assetsDir = database_path('seeders/assets');
        if (!File::isDirectory($assetsDir)) {
            return;
        }

        $files = File::allFiles($assetsDir);
        foreach ($files as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            if (!Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->put($relativePath, File::get($file->getRealPath()));
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Store                                                              */
    /* ------------------------------------------------------------------ */

    private function createStore(Carbon $now): void
    {
        $existing = DB::table('stores')->where('slug', 'datapos-mobile')->first();
        if ($existing) {
            $this->storeId = $existing->id;
            return;
        }

        $this->storeId = DB::table('stores')->insertGetId([
            'name'              => 'DataPOS Mobile & Accessories',
            'business_type'     => 'mobile_sale_service',
            'slug'              => 'datapos-mobile',
            'viber_number'      => '09150000001',
            'telegram_username' => 'datapos_mobile',
            'is_active'         => true,
            'is_primary'        => true,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Storefront Settings                                                */
    /* ------------------------------------------------------------------ */

    private function createStorefrontSettings(Carbon $now): void
    {
        if (DB::table('storefront_settings')->where('store_id', $this->storeId)->exists()) {
            return;
        }

        DB::table('storefront_settings')->insert([
            'store_id'             => $this->storeId,
            'store_name'           => 'DataPOS Mobile & Accessories (အလင်းသစ် မိုဘိုင်း)',
            'tagline'              => 'စမတ်ဖုန်း၊ ကာဗာ၊ မှန်ကပ်၊ အားသွင်းကြိုး၊ အပိုပစ္စည်းနှင့် ကျွမ်းကျင်ဖုန်းပြုပြင်ရေး ဝန်ဆောင်မှု',
            'logo_path'            => 'store-logos/EU9aTxNGXzb35lHtLnDzO9r8OAtjnal6Rl8DQWKo.webp',
            'storefront_logo_path' => 'store-logos/EU9aTxNGXzb35lHtLnDzO9r8OAtjnal6Rl8DQWKo.webp',
            'admin_logo_path'      => 'admin-logos/0PrE72f6lR6cc9snJTCoVo4ExhtHRPPVsknTZynR.webp',
            'favicon_path'         => 'favicons/Pw1A8rMQ25p7qnKuDKvvx0ldOSW6EyhGHG9z4vLS.webp',
            'address'              => "No. 123, Maha Bandula Road\nBotataung Township\nYangon, Myanmar",
            'phone'                => '09150000001',
            'opening_hours'        => 'Mon - Sat: 9:00 AM - 6:00 PM, Sun: 10:00 AM - 4:00 PM',
            'viber_number'         => '09150000001',
            'telegram_username'    => 'datapos_mobile',
            'delivery_info'        => "ရန်ကုန်မြို့တွင်း အိမ်အရောက်ငွေချေ (COD) ၁ ရက်မှ ၂ ရက်အတွင်း ပို့ဆောင်ပေးပါသည်။\nနယ်မြို့များအတွက် ကားဂိတ် (သို့မဟုတ်) Royal / Kerry Express ဖြင့် အမြန်ပို့ဆောင်ပေးပါသည်။",
            'payment_info'         => "KBZ Pay: 09150000001 (DataPOS Mobile)\nWave Pay: 09150000001\nCB Pay, AYA Pay နှင့် MMQR လက်ခံပါသည်။\nရန်ကုန်မြို့တွင်း ပစ္စည်းရောက်မှ ငွေချေစနစ် ရရှိပါသည်။",
            'default_language'     => 'my',
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Home Banners                                                       */
    /* ------------------------------------------------------------------ */

    private function createHomeBanners(Carbon $now): void
    {
        if (DB::table('home_banners')->where('store_id', $this->storeId)->exists()) {
            return;
        }

        $banners = [
            [
                'store_id'   => $this->storeId,
                'title'      => 'All-in-One Tech & Mobile Superstore',
                'image_path' => 'banners/alinnthit-all-categories-2026.webp',
                'link_url'   => '/store/datapos-mobile/products',
                'sort_order' => 1,
                'is_active'  => true,
            ],
            [
                'store_id'   => $this->storeId,
                'title'      => 'Premium Glass Finder & Screen Protection',
                'image_path' => 'banners/alinnthit-glass-finder-premium-2026.webp',
                'link_url'   => '/store/datapos-mobile/glass-finder',
                'sort_order' => 2,
                'is_active'  => true,
            ],
            [
                'store_id'   => $this->storeId,
                'title'      => 'Mobile Accessories & Fast Chargers',
                'image_path' => 'banners/alinnthit-mobile-accessories-2026.webp',
                'link_url'   => '/store/datapos-mobile/products?category=chargers-cables',
                'sort_order' => 3,
                'is_active'  => true,
            ],
            [
                'store_id'   => $this->storeId,
                'title'      => 'CCTV Security Camera & Smart Surveillance',
                'image_path' => 'banners/alinnthit-cctv-security-2026.webp',
                'link_url'   => '/store/datapos-mobile/products?category=cctv-network',
                'sort_order' => 4,
                'is_active'  => true,
            ],
            [
                'store_id'   => $this->storeId,
                'title'      => 'Computer, Laptop & High Speed Networking',
                'image_path' => 'banners/alinnthit-computer-laptop-2026.webp',
                'link_url'   => '/store/datapos-mobile/products?category=network-connectivity',
                'sort_order' => 5,
                'is_active'  => true,
            ],
            [
                'store_id'   => $this->storeId,
                'title'      => 'Wholesale Discount Available (လက်ကားဈေး လျှောက်ထားရန်)',
                'image_path' => 'banners/alinnthit-glass-wholesale-2026.webp',
                'link_url'   => '/store/datapos-mobile/wholesale/apply',
                'sort_order' => 6,
                'is_active'  => true,
            ],
        ];

        foreach ($banners as $banner) {
            $banner['created_at'] = $now;
            $banner['updated_at'] = $now;
            DB::table('home_banners')->insert($banner);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Staff Role Presets Bootstrapping                                   */
    /* ------------------------------------------------------------------ */

    private function bootstrapStaffRoles(): void
    {
        $store = Store::find($this->storeId);
        if ($store) {
            StaffRole::bootstrapDefaultRoles($store);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Store Locations & Warehouses                                       */
    /* ------------------------------------------------------------------ */

    private array $warehouseMap = [];
    private int $defaultBranchId;

    private function createLocationsAndWarehouses(): void
    {
        $store = Store::find($this->storeId);
        if (!$store) {
            return;
        }

        $locations = app(StoreLocationService::class)->ensureDefaults($store);
        $this->defaultBranchId = $locations['branch']->id;
        $this->warehouseMap['MAIN'] = $locations['warehouse']->id;
        $this->warehouseMap['SHOW'] = $locations['warehouse']->id;

        $extraWarehouses = [
            ['name' => 'Back Stock Storage', 'code' => 'BACK'],
            ['name' => 'Technician Spare Parts Cabinet', 'code' => 'SPARE'],
            ['name' => 'Repair Intake & Testing Desk', 'code' => 'INTAKE'],
            ['name' => 'Damaged / Return Storage', 'code' => 'RETURN'],
        ];

        foreach ($extraWarehouses as $w) {
            $wh = Warehouse::updateOrCreate(
                ['store_id' => $store->id, 'name' => $w['name']],
                [
                    'branch_id' => $this->defaultBranchId,
                    'code' => $w['code'],
                    'is_default' => false,
                    'is_active' => true,
                ]
            );
            $this->warehouseMap[$w['code']] = $wh->id;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Store Payment & Delivery Methods                                   */
    /* ------------------------------------------------------------------ */

    private function createStorePaymentAndDeliveryMethods(Carbon $now): void
    {
        if (DB::table('store_payment_methods')->where('store_id', $this->storeId)->exists()) {
            return;
        }

        $paymentMethods = [
            ['code' => 'kpay', 'name' => 'KBZ Pay (KPay)', 'account_name' => 'DataPOS Mobile Hub', 'account_number' => '09150000001', 'type' => 'custom', 'icon_type' => 'builtin', 'icon_value' => 'kpay', 'instructions' => 'KPay ဖြင့် ငွေလွှဲပြီး Screenshot ပို့ပေးပါ။', 'is_active' => true, 'show_account_details' => true],
            ['code' => 'wave', 'name' => 'Wave Pay', 'account_name' => 'DataPOS Mobile Hub', 'account_number' => '09150000001', 'type' => 'custom', 'icon_type' => 'builtin', 'icon_value' => 'wave', 'instructions' => 'Wave Pay ဖြင့် ငွေလွှဲနိုင်ပါသည်။', 'is_active' => true, 'show_account_details' => true],
            ['code' => 'cbpay', 'name' => 'CB Pay', 'account_name' => 'DataPOS Mobile Hub', 'account_number' => '0012345678901', 'type' => 'custom', 'icon_type' => 'initials', 'icon_value' => 'CB', 'instructions' => 'CB Bank အကောင့်သို့ လွှဲပေးနိုင်ပါသည်။', 'is_active' => true, 'show_account_details' => true],
            ['code' => 'cod', 'name' => 'Cash on Delivery (အိမ်အရောက်ငွေချေ)', 'account_name' => null, 'account_number' => null, 'type' => 'custom', 'icon_type' => 'initials', 'icon_value' => 'COD', 'instructions' => 'ရန်ကုန်မြို့တွင်း ပစ္စည်းရောက်မှ ငွေချေပါ။', 'is_active' => true, 'show_account_details' => false],
            ['code' => 'cash', 'name' => 'Counter Cash (ကောင်တာ ငွေသား)', 'account_name' => 'POS Cash Desk', 'account_number' => null, 'type' => 'custom', 'icon_type' => 'initials', 'icon_value' => 'CASH', 'instructions' => 'ဆိုင်ကောင်တာတွင် ငွေသားဖြင့် ရှင်းနိုင်ပါသည်။', 'is_active' => true, 'show_account_details' => false],
        ];

        foreach ($paymentMethods as $pm) {
            DB::table('store_payment_methods')->insert([
                'store_id'             => $this->storeId,
                'code'                 => $pm['code'],
                'name'                 => $pm['name'],
                'type'                 => $pm['type'],
                'icon_type'            => $pm['icon_type'],
                'icon_value'           => $pm['icon_value'],
                'account_name'         => $pm['account_name'],
                'account_number'       => $pm['account_number'],
                'instructions'         => $pm['instructions'],
                'is_active'            => $pm['is_active'],
                'show_account_details' => $pm['show_account_details'],
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }

        $deliveryMethods = [
            ['name' => 'Yangon City COD (ရန်ကုန်မြို့တွင်း အိမ်အရောက်)', 'icon' => '🛵', 'service_area' => 'Yangon Townships', 'estimated_time' => '1 - 2 Days', 'fee_note' => 'Ks 2,500', 'is_active' => true],
            ['name' => 'Royal Express (နယ်မြို့များ အမြန်ချောပို့)', 'icon' => '📦', 'service_area' => 'Nationwide / Gates', 'estimated_time' => '2 - 4 Days', 'fee_note' => 'Ks 4,500', 'is_active' => true],
            ['name' => 'Kerry Express (မြန်မာတစ်နိုင်ငံလုံး ပို့ဆောင်ရေး)', 'icon' => '🚚', 'service_area' => 'Nationwide', 'estimated_time' => '2 - 3 Days', 'fee_note' => 'Ks 5,000', 'is_active' => true],
            ['name' => 'Store Pickup (ဆိုင်တွင် ကိုယ်တိုင်လာယူမည်)', 'icon' => '🏪', 'service_area' => 'Store Showroom', 'estimated_time' => 'Same Day', 'fee_note' => 'Free (အခမဲ့)', 'is_active' => true],
        ];

        foreach ($deliveryMethods as $dm) {
            DB::table('store_delivery_methods')->insert([
                'store_id'       => $this->storeId,
                'name'           => $dm['name'],
                'icon'           => $dm['icon'],
                'service_area'   => $dm['service_area'],
                'estimated_time' => $dm['estimated_time'],
                'fee_note'       => $dm['fee_note'],
                'is_active'      => $dm['is_active'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Users                                                              */
    /* ------------------------------------------------------------------ */

    private function createUsers(Carbon $now): void
    {
        $users = [
            [
                'name'     => 'Owner (Platform Admin)',
                'phone'    => '09100000001',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'platform_owner',
            ],
            [
                'name'     => 'DataPOS Store Owner',
                'phone'    => '09100000099',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Mg Hla (Store Manager)',
                'phone'    => '09100000002',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Front Counter Cashier (Ma Aye)',
                'phone'    => '09160000003',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Master Hardware Technician (Ko Tun)',
                'phone'    => '09160000002',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Ko Kyaw (Warehouse & Stock Keeper)',
                'phone'    => '09100000003',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Finance & Accountant (Daw Ni)',
                'phone'    => '09100000008',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Daw Aye (Wholesale — Approved)',
                'phone'    => '09100000004',
                'password' => Hash::make('password'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'U Mya (Wholesale — Pending)',
                'phone'    => '09100000005',
                'password' => Hash::make('password'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Ma Su (Retail Customer)',
                'phone'    => '09100000006',
                'password' => Hash::make('password'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'U Ko Ko (Store B Manager)',
                'phone'    => '09100000007',
                'password' => Hash::make('password'),
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
        ];

        $phones = array_column($users, 'phone');
        $existing = DB::table('users')->whereIn('phone', $phones)->pluck('id', 'phone');

        foreach ($users as $user) {
            if (isset($existing[$user['phone']])) {
                $id = $existing[$user['phone']];
            } else {
                $user['created_at'] = $now;
                $user['updated_at'] = $now;
                $id = DB::table('users')->insertGetId($user);
            }

            match ($user['phone']) {
                '09100000001' => $this->ownerId = $id,
                '09100000099' => $this->storeOwnerId = $id,
                '09100000002' => $this->managerId = $id,
                '09160000003' => $this->cashierId = $id,
                '09160000002' => $this->technicianId = $id,
                '09100000003' => $this->stockKeeperId = $id,
                '09100000008' => $this->accountantId = $id,
                '09100000004' => $this->wholesaleApprovedId = $id,
                '09100000005' => $this->wholesalePendingId = $id,
                '09100000006' => $this->retailCustomerId = $id,
                '09100000007' => $this->storeBManagerId = $id,
                default => null,
            };
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Store Roles (Pivot)                                                */
    /* ------------------------------------------------------------------ */

    private function assignStoreRoles(): void
    {
        $staffRoles = StaffRole::where('store_id', $this->storeId)->pluck('id', 'slug')->all();

        $pivot = [
            ['user_id' => $this->storeOwnerId,        'role' => 'store_owner',        'staff_role_id' => $staffRoles['store_owner'] ?? null,   'status' => 'active'],
            ['user_id' => $this->managerId,           'role' => 'store_manager',      'staff_role_id' => $staffRoles['store_manager'] ?? null, 'status' => 'active'],
            ['user_id' => $this->cashierId,           'role' => 'staff',              'staff_role_id' => $staffRoles['cashier'] ?? null,       'status' => 'active'],
            ['user_id' => $this->technicianId,        'role' => 'staff',              'staff_role_id' => $staffRoles['technician'] ?? null,    'status' => 'active'],
            ['user_id' => $this->stockKeeperId,       'role' => 'staff',              'staff_role_id' => $staffRoles['stock_keeper'] ?? null,   'status' => 'active'],
            ['user_id' => $this->accountantId,        'role' => 'staff',              'staff_role_id' => $staffRoles['accountant'] ?? null,     'status' => 'active'],
            ['user_id' => $this->wholesaleApprovedId, 'role' => 'wholesale_customer', 'staff_role_id' => null,                                 'status' => 'active'],
            ['user_id' => $this->wholesalePendingId,  'role' => 'wholesale_customer', 'staff_role_id' => null,                                 'status' => 'pending'],
            ['user_id' => $this->retailCustomerId,   'role' => 'retail_customer',    'staff_role_id' => null,                                 'status' => 'active'],
        ];

        $now = Carbon::now();
        foreach ($pivot as $row) {
            $row['store_id'] = $this->storeId;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;

            $existing = DB::table('store_user')
                ->where('store_id', $row['store_id'])
                ->where('user_id', $row['user_id'])
                ->first();

            if (!$existing) {
                DB::table('store_user')->insert($row);
            } else {
                DB::table('store_user')
                    ->where('store_id', $row['store_id'])
                    ->where('user_id', $row['user_id'])
                    ->update([
                        'role' => $row['role'],
                        'staff_role_id' => $row['staff_role_id'],
                        'status' => $row['status'],
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Store B Roles (Pivot) — Isolation UAT                              */
    /* ------------------------------------------------------------------ */

    private function assignStoreBRoles(): void
    {
        $storeB = Store::find($this->storeBId);
        if ($storeB) {
            StaffRole::bootstrapDefaultRoles($storeB);
            $storeBManagerRoleId = StaffRole::where('store_id', $this->storeBId)->where('slug', 'store_manager')->value('id');

            $alreadyExists = DB::table('store_user')
                ->where('store_id', $this->storeBId)
                ->where('user_id', $this->storeBManagerId)
                ->exists();

            if (!$alreadyExists) {
                DB::table('store_user')->insert([
                    'store_id'      => $this->storeBId,
                    'user_id'       => $this->storeBManagerId,
                    'role'          => 'store_manager',
                    'staff_role_id' => $storeBManagerRoleId,
                    'status'        => 'active',
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now(),
                ]);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Master Data Presets Importing                                      */
    /* ------------------------------------------------------------------ */

    private function importMasterDataPresets(): void
    {
        $store = Store::find($this->storeId);
        if ($store) {
            app(MasterDataSeedImporter::class)->importForStore(
                $store,
                ['brands', 'categories', 'connectors', 'colors', 'shelves', 'warranties', 'return_policies', 'variant_presets'],
                'tech'
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Categories                                                         */
    /* ------------------------------------------------------------------ */

    private array $categoryIds = [];

    private function createCategories(Carbon $now): void
    {
        $categories = [
            ['name' => 'Tempered Glass (ဖန်ကာကွယ်)',        'slug' => 'tempered-glass',        'code' => 'SCR_TG'],
            ['name' => 'Back Glass (နောက်ဖုံးဖန်)',         'slug' => 'back-glass',            'code' => 'SCR_BG'],
            ['name' => 'Camera Lens (ကင်မရာမှန်ဘီလူး)',    'slug' => 'camera-lens',           'code' => 'CAM_LN'],
            ['name' => 'Chargers & Cables (အားသွင်းကြိုး)', 'slug' => 'chargers-cables',       'code' => 'CBCH'],
            ['name' => 'Phone Cases (ဖုန်းအိတ်)',          'slug' => 'phone-cases',           'code' => 'ACC_CS'],
            ['name' => 'Smartphones & Tablets',            'slug' => 'smartphones-tablets',   'code' => 'PHN'],
            ['name' => 'Audio & Sound (နားကြပ်/စပီကာ)',    'slug' => 'audio-sound',           'code' => 'AUD'],
            ['name' => 'Power & Storage (ပါဝါဘဏ်/မန်မိုရီ)', 'slug' => 'power-storage',         'code' => 'PWR'],
            ['name' => 'Digital & Gift Cards (ဒစ်ဂျစ်တယ်ကုတ်)', 'slug' => 'digital-codes-topup', 'code' => 'DIG'],
            ['name' => 'Service & Repair (ပြုပြင်ခ ဝန်ဆောင်မှု)', 'slug' => 'repair-service-fees', 'code' => 'SVC'],
        ];

        foreach ($categories as $cat) {
            $existing = DB::table('categories')
                ->where('store_id', $this->storeId)
                ->where(function ($q) use ($cat) {
                    $q->where('slug', $cat['slug'])
                      ->orWhere('name', $cat['name'])
                      ->orWhere('code', $cat['code']);
                })
                ->first();

            if ($existing) {
                $this->categoryIds[$cat['slug']] = $existing->id;
            } else {
                $id = DB::table('categories')->insertGetId([
                    'store_id'    => $this->storeId,
                    'name'        => $cat['name'],
                    'slug'        => $cat['slug'],
                    'code'        => $cat['code'],
                    'description' => null,
                    'image_path'  => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $this->categoryIds[$cat['slug']] = $id;
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Brands                                                             */
    /* ------------------------------------------------------------------ */

    private array $brandIds = [];

    private function createBrands(Carbon $now): void
    {
        $brands = [
            ['name' => 'Samsung',                  'slug' => 'samsung',                   'code' => 'SAM'],
            ['name' => 'iPhone / Apple',           'slug' => 'iphone',                    'code' => 'APL'],
            ['name' => 'Xiaomi / Redmi',           'slug' => 'xiaomi',                    'code' => 'RM'],
            ['name' => 'OPPO',                     'slug' => 'oppo',                      'code' => 'OP'],
            ['name' => 'Vivo',                     'slug' => 'vivo',                      'code' => 'VV'],
            ['name' => 'Anker',                    'slug' => 'anker',                     'code' => 'ANKER'],
            ['name' => 'Baseus',                   'slug' => 'baseus',                    'code' => 'BASEUS'],
            ['name' => 'Remax',                    'slug' => 'remax',                     'code' => 'REMAX'],
            ['name' => 'Hoco',                     'slug' => 'hoco',                      'code' => 'HOCO'],
            ['name' => 'Apple Gift & iTunes',      'slug' => 'apple-id-itunes',           'code' => 'APL_ID'],
            ['name' => 'Mobile Legends (Moonton)', 'slug' => 'mobile-legends-moonton',    'code' => 'MLBB'],
            ['name' => 'PUBG Mobile',              'slug' => 'pubg-mobile',               'code' => 'PUBG'],
            ['name' => 'MPT Telecom',              'slug' => 'mpt-telecom',               'code' => 'MPT'],
            ['name' => 'DataPOS Service Center',   'slug' => 'datapos-service-center',    'code' => 'SVC_CTR'],
        ];

        foreach ($brands as $brand) {
            $existing = DB::table('brands')
                ->where('store_id', $this->storeId)
                ->where(function ($q) use ($brand) {
                    $q->where('slug', $brand['slug'])
                      ->orWhere('name', $brand['name'])
                      ->orWhere('code', $brand['code']);
                })
                ->first();

            if ($existing) {
                $this->brandIds[$brand['slug']] = $existing->id;
            } else {
                $id = DB::table('brands')->insertGetId([
                    'store_id'   => $this->storeId,
                    'name'       => $brand['name'],
                    'slug'       => $brand['slug'],
                    'code'       => $brand['code'],
                    'logo_path'  => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->brandIds[$brand['slug']] = $id;
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Suppliers                                                          */
    /* ------------------------------------------------------------------ */

    private array $supplierIds = [];

    private function createSuppliers(Carbon $now): void
    {
        $suppliers = [
            ['key' => 'yangon_mobile', 'name' => 'Yangon Mobile Wholesale Hub (ရန်ကုန် မိုဘိုင်း လက်ကား)', 'phone' => '09250000001', 'address' => 'Pansodan Road, Yangon'],
            ['key' => 'mingalar_parts', 'name' => 'Mingalar Phone Parts Wholesale (မင်္ဂလာဈေး ဖုန်းအပိုပစ္စည်း)', 'phone' => '09250000002', 'address' => 'Mingalar Market, Yangon'],
            ['key' => 'mandalay_tech', 'name' => 'Mandalay Tech Wholesale (မန္တလေး အီလက်ထရောနစ်)', 'phone' => '09250000003', 'address' => '78th Road, Mandalay'],
            ['key' => 'digital_distributor', 'name' => 'Global Digital PIN & Top-Up Distributor (ဒစ်ဂျစ်တယ်ကုတ် လက်ကား)', 'phone' => '09250000004', 'address' => 'Yangon Cyber Hub'],
        ];

        foreach ($suppliers as $s) {
            $sup = Supplier::updateOrCreate(
                ['store_id' => $this->storeId, 'phone' => $s['phone']],
                [
                    'name' => $s['name'],
                    'address' => $s['address'],
                    'notes' => 'Official UAT Verified Supplier',
                ]
            );
            $this->supplierIds[$s['key']] = $sup->id;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Products (Complete Catalog + Inventory Balances)                   */
    /* ------------------------------------------------------------------ */

    private function createProducts(Carbon $now): void
    {
        $store = Store::find($this->storeId);
        $inventoryService = app(InventoryService::class);
        $primaryWarehouseId = $this->warehouseMap['SHOW'] ?? $this->warehouseMap['MAIN'];

        // Canonical product definitions
        // [name, slug, category_slug, brand_slug, supplier_key, retail, wholesale, purchase_cost, stock_status, featured, warranty, return_policy, type, duration, specs, warehouse_code]
        $products = [
            // -- Canonical Core items (including tests' expected items) --
            ['Samsung Galaxy S24 Ultra Tempered Glass',    'samsung-s24-ultra-tg',    'tempered-glass',        'samsung', 'yangon_mobile', 15000, 9000,  7000,  'in_stock',     true,  '6 months warranty against defects', 'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['iPhone 15 Pro Max Tempered Glass',            'iphone-15pm-tg',          'tempered-glass',        'iphone',  'yangon_mobile', 18000, 11000, 8500,  'in_stock',     true,  '6 months warranty against defects', 'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['Xiaomi Redmi Note 13 Back Glass',             'xiaomi-note13-bg',        'back-glass',            'xiaomi',  'yangon_mobile', 25000, 16000, 12000, 'in_stock',     true,  'No warranty on glass',             'Non-returnable (hygiene product)',  'standard', null, null, 'SHOW'],
            ['Samsung Galaxy S24 Camera Lens Protector',    'samsung-s24-cam',         'camera-lens',           'samsung', 'yangon_mobile', 8000,  5000,  3500,  'in_stock',     true,  '12 months warranty',               'Return within 14 days',             'standard', null, null, 'SHOW'],
            ['iPhone 15 Pro Max Silicone Case',             'iphone-15pm-case',        'phone-cases',           'iphone',  'yangon_mobile', 22000, 14000, 10500, 'in_stock',     true,  '6 months warranty',                'Return within 7 days',              'standard', null, null, 'SHOW'],
            ['OPPO Reno 11 Tempered Glass',                 'oppo-reno11-tg',          'tempered-glass',        'oppo',    'yangon_mobile', 12000, 7500,  5500,  'in_stock',     true,  '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['Vivo V30 Back Glass',                         'vivo-v30-bg',             'back-glass',            'vivo',    'yangon_mobile', 28000, 18000, 13500, 'in_stock',     true,  'No warranty on glass',             'Non-returnable',                    'standard', null, null, 'SHOW'],
            ['Samsung Galaxy A55 Tempered Glass',           'samsung-a55-tg',          'tempered-glass',        'samsung', 'yangon_mobile', 10000, 6000,  4500,  'in_stock',     true,  '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],

            // -- Additional Standard Items --
            ['iPhone 14 Tempered Glass',                    'iphone-14-tg',            'tempered-glass',        'iphone',  'yangon_mobile', 14000, 8500,  6000,  'in_stock',     false, '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['iPhone 14 Pro Back Glass',                    'iphone-14-pro-bg',        'back-glass',            'iphone',  'yangon_mobile', 30000, 20000, 15000, 'in_stock',     false, 'No warranty on glass',             'Non-returnable',                    'standard', null, null, 'SHOW'],
            ['Xiaomi Redmi Note 12 Tempered Glass',         'xiaomi-note12-tg',        'tempered-glass',        'xiaomi',  'yangon_mobile', 9000,  5500,  4000,  'in_stock',     false, '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['Xiaomi 14T Camera Lens',                      'xiaomi-14t-cam',          'camera-lens',           'xiaomi',  'yangon_mobile', 10000, 6500,  4500,  'in_stock',     false, '12 months warranty',               'Return within 14 days',             'standard', null, null, 'SHOW'],
            ['OPPO Find N3 Flip Tempered Glass',            'oppo-n3-flip-tg',         'tempered-glass',        'oppo',    'yangon_mobile', 16000, 10000, 7500,  'in_stock',     false, '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['Vivo V29e Tempered Glass',                    'vivo-v29e-tg',            'tempered-glass',        'vivo',    'yangon_mobile', 11000, 7000,  5000,  'in_stock',     false, '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['Samsung Galaxy S24 Ultra Silicone Case',      'samsung-s24-case',        'phone-cases',           'samsung', 'yangon_mobile', 15000, 9500,  7000,  'in_stock',     false, '6 months warranty',                'Return within 7 days',              'standard', null, null, 'SHOW'],
            ['iPhone 15 Pro Max Clear Case',                'iphone-15pm-clear',       'phone-cases',           'iphone',  'yangon_mobile', 18000, 11000, 8000,  'in_stock',     false, '6 months warranty',                'Return within 7 days',              'standard', null, null, 'SHOW'],
            ['Xiaomi Redmi Note 13 Silicone Case',          'xiaomi-note13-case',      'phone-cases',           'xiaomi',  'yangon_mobile', 8000,  5000,  3500,  'in_stock',     false, '6 months warranty',                'Return within 7 days',              'standard', null, null, 'SHOW'],
            ['USB-C Fast Charger 25W',                      'usbc-charger-25w',        'chargers-cables',       'samsung', 'mandalay_tech', 15000, 9000,  6500,  'in_stock',     false, '12 months warranty',               'Return within 14 days',             'standard', null, null, 'SHOW'],
            ['Lightning Cable 2M',                          'lightning-cable-2m',      'chargers-cables',       'iphone',  'mandalay_tech', 12000, 7000,  5000,  'in_stock',     false, '6 months warranty',                'Return within 7 days',              'standard', null, null, 'SHOW'],
            ['Type-C Cable 2M Braided',                     'typec-cable-braided',     'chargers-cables',       'remax',   'mandalay_tech', 8000,  5000,  3500,  'in_stock',     false, '6 months warranty',                'Return within 7 days',              'standard', null, null, 'SHOW'],
            ['OPPO Reno 11 Silicone Case',                  'oppo-reno11-case',        'phone-cases',           'oppo',    'yangon_mobile', 10000, 6000,  4500,  'in_stock',     false, '6 months warranty',                'Return within 7 days',              'standard', null, null, 'SHOW'],
            ['Samsung Galaxy A15 Tempered Glass',            'samsung-a15-tg',          'tempered-glass',        'samsung', 'yangon_mobile', 8000,  5000,  3500,  'in_stock',     false, '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],

            // -- Out of stock items --
            ['Samsung Galaxy S23 Ultra Tempered Glass',     'samsung-s23-ultra-tg',    'tempered-glass',        'samsung', 'yangon_mobile', 15000, 9000,  7000,  'out_of_stock', false, '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['iPhone 13 Tempered Glass',                    'iphone-13-tg',            'tempered-glass',        'iphone',  'yangon_mobile', 12000, 7500,  5500,  'out_of_stock', false, '6 months warranty',                'Return within 7 days if unopened', 'standard', null, null, 'SHOW'],
            ['Xiaomi Redmi 12 Back Glass',                  'xiaomi-redmi12-bg',       'back-glass',            'xiaomi',  'yangon_mobile', 20000, 13000, 9500,  'out_of_stock', false, 'No warranty on glass',             'Non-returnable',                    'standard', null, null, 'SHOW'],

            // -- Mobile Phones & Hardware --
            ['iPhone 15 Pro Max 256GB Natural Titanium',    'iphone-15-pro-max-256gb', 'smartphones-tablets',   'iphone',  'yangon_mobile', 4450000, 4380000, 4250000, 'in_stock',  true,  '1 Year Official Apple Warranty',    '7 Days Defect Exchange',            'standard', null, ['capacity' => '256GB', 'color' => 'Natural Titanium'], 'SHOW'],
            ['Samsung Galaxy A55 5G 8/256GB Awesome Navy',  'samsung-galaxy-a55-5g',   'smartphones-tablets',   'samsung', 'yangon_mobile', 1380000, 1320000, 1250000, 'in_stock',  true,  '1 Year Official Samsung Warranty',  '7 Days Defect Exchange',            'standard', null, ['ram_rom' => '8GB/256GB', 'color' => 'Awesome Navy'], 'SHOW'],
            ['Anker 20W PowerPort III Fast Charger Cube',   'anker-20w-fast-charger',  'chargers-cables',       'anker',   'mandalay_tech', 45000, 39000, 32000, 'in_stock',        true,  '18 Months Official Anker Warranty', 'Defective Exchange',               'standard', null, null, 'SHOW'],
            ['Baseus 65W GaN Multi-Port Fast Charger',      'baseus-65w-gan-charger',  'chargers-cables',       'baseus',  'mandalay_tech', 78000, 69000, 58000, 'in_stock',        true,  '6 Months Replacement Warranty',     'Defective Exchange',               'standard', null, null, 'SHOW'],
            ['Remax 20000mAh 22.5W Fast Power Bank',        'remax-20000mah-powerbank','power-storage',         'remax',   'mandalay_tech', 58000, 49000, 41000, 'in_stock',        true,  '6 Months Warranty',                 'Defective Exchange',               'standard', null, null, 'SHOW'],
            ['Hoco W35 Wireless Bluetooth ANC Headphone',   'hoco-w35-headphone',      'audio-sound',           'hoco',    'mandalay_tech', 42000, 35000, 28000, 'in_stock',        true,  '3 Months Replacement Warranty',     'Defective Exchange',               'standard', null, null, 'SHOW'],

            // -- Services & Repairs --
            ['Phone LCD / Touch Screen Replacement Service', 'phone-lcd-replacement-fee', 'repair-service-fees', 'datapos-service-center', 'mingalar_parts', 25000, 25000, 0, 'in_stock', false, '3 Months Workmanship Warranty', 'Service Satisfaction Guaranteed', 'service', '45 mins', null, 'INTAKE'],
            ['Phone Battery Replacement Service Fee',        'battery-replacement-fee',   'repair-service-fees', 'datapos-service-center', 'mingalar_parts', 15000, 15000, 0, 'in_stock', false, '3 Months Workmanship Warranty', 'Service Satisfaction Guaranteed', 'service', '25 mins', null, 'INTAKE'],
            ['Motherboard IC & Water Damage Repair Service', 'motherboard-ic-repair-fee',  'repair-service-fees', 'datapos-service-center', 'mingalar_parts', 65000, 65000, 0, 'in_stock', false, '1 Month Workmanship Warranty',  'Service Satisfaction Guaranteed', 'service', '2 hours', null, 'INTAKE'],

            // -- Digital Products & Codes --
            ['Apple iTunes & App Store $10 Gift Card US',    'apple-itunes-10-usd',     'digital-codes-topup',   'apple-id-itunes', 'digital_distributor', 42000, 40000, 38500, 'in_stock', true, '100% Genuine Digital Code', 'Non-refundable once redeemed', 'digital', null, ['denomination' => '$10 USD'], 'SHOW'],
            ['Mobile Legends 706 Diamonds Direct Top-Up',    'mlbb-706-diamonds',       'digital-codes-topup',   'mobile-legends-moonton', 'digital_distributor', 38000, 36000, 34500, 'in_stock', true, 'Instant Direct Diamond Top-up', 'Direct in-game credit', 'digital', null, ['diamonds' => '706 Diamonds'], 'SHOW'],
            ['PUBG Mobile 660 UC Digital Voucher',           'pubg-660-uc-voucher',     'digital-codes-topup',   'pubg-mobile', 'digital_distributor', 36000, 34000, 32500, 'in_stock', true, 'Official PUBG UC Voucher', 'Non-refundable once redeemed', 'digital', null, ['uc' => '660 UC'], 'SHOW'],
            ['MPT 10,000 MMK Mobile Top-Up E-Pin',           'mpt-10000-topup-code',    'digital-codes-topup',   'mpt-telecom', 'digital_distributor', 10000, 9850, 9700, 'in_stock', true, '100% Genuine Telecom Code', 'Instant Top-Up', 'digital', null, null, 'SHOW'],
        ];

        foreach ($products as $idx => $p) {
            $catId = $this->categoryIds[$p[2]] ?? reset($this->categoryIds);
            $brandId = $this->brandIds[$p[3]] ?? reset($this->brandIds);
            $supId = $this->supplierIds[$p[4]] ?? reset($this->supplierIds);
            $whId = $this->warehouseMap[$p[15]] ?? $primaryWarehouseId;

            $sku = 'UAT-SKU-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT);
            $openingQty = $p[8] === 'in_stock' ? 30 : 0;

            $product = Product::updateOrCreate(
                ['store_id' => $this->storeId, 'slug' => $p[1]],
                [
                    'category_id'     => $catId,
                    'brand_id'        => $brandId,
                    'supplier_id'     => $supId,
                    'sku'             => $sku,
                    'name'            => $p[0],
                    'description'     => "High-quality {$p[0]} compatible with your device. Durable, reliable and fully tested.",
                    'retail_price'    => $p[5],
                    'wholesale_price' => $p[6],
                    'stock_status'    => $p[8],
                    'product_type'    => $p[12],
                    'service_duration'=> $p[13],
                    'specs'           => $p[14],
                    'image_path'      => null,
                    'warranty'        => $p[10],
                    'return_policy'   => $p[11],
                    'is_featured'     => $p[9],
                    'is_ecommerce'    => true,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]
            );

            // Post inventory opening movement if in stock and not yet recorded
            if ($openingQty > 0 && $store) {
                $hasMovement = DB::table('inventory_movements')
                    ->where('store_id', $store->id)
                    ->where('product_id', $product->id)
                    ->where('movement_type', InventoryMovementType::OpeningBalance->value)
                    ->exists();

                if (!$hasMovement) {
                    try {
                        $inventoryService->postMovement([
                            'store'                 => $store,
                            'warehouse_id'          => $whId,
                            'branch_id'             => $this->defaultBranchId,
                            'product_id'            => $product->id,
                            'movement_type'         => InventoryMovementType::OpeningBalance->value,
                            'quantity_delta'        => (string) $openingQty,
                            'unit_cost'             => $p[7],
                            'source_type'           => 'uat_seed',
                            'source_id'             => $store->id,
                            'client_transaction_id' => "uat:opening:{$product->id}:{$store->id}",
                            'metadata'              => ['seed' => 'UatSeeder'],
                        ]);
                    } catch (\Throwable) {
                        // ignore if already posted
                    }
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Glass Finder Items  (15)                                           */
    /* ------------------------------------------------------------------ */

    private function createGlassFinderItems(Carbon $now): void
    {
        $items = [
            ['Samsung', 'Samsung Galaxy S24 Ultra',  'G-S24U-F',   'in_stock'],
            ['Samsung', 'Samsung Galaxy S24 Ultra',  'G-S24U-B',   'in_stock'],
            ['Samsung', 'Samsung Galaxy S24',        'G-S24-F',    'in_stock'],
            ['Samsung', 'Samsung Galaxy A55',        'G-A55-F',    'in_stock'],
            ['Samsung', 'Samsung Galaxy A55',        'G-A55-B',    'out_of_stock'],
            ['iPhone',  'iPhone 15 Pro Max',         'G-I15PM-F',  'in_stock'],
            ['iPhone',  'iPhone 15 Pro Max',         'G-I15PM-B',  'in_stock'],
            ['iPhone',  'iPhone 15 Pro',             'G-I15P-F',   'in_stock'],
            ['iPhone',  'iPhone 14 Pro Max',         'G-I14PM-F',  'in_stock'],
            ['Xiaomi',  'Xiaomi Redmi Note 13',      'G-RN13-F',   'in_stock'],
            ['Xiaomi',  'Xiaomi Redmi Note 13',      'G-RN13-B',   'in_stock'],
            ['Xiaomi',  'Xiaomi 14T',                'G-X14T-F',   'in_stock'],
            ['OPPO',    'OPPO Reno 11',              'G-OR11-F',   'in_stock'],
            ['OPPO',    'OPPO Find N3 Flip',         'G-OFN3-F',   'in_stock'],
            ['Vivo',    'Vivo V30',                  'G-VV30-F',   'in_stock'],
        ];

        foreach ($items as $item) {
            DB::table('glass_finder_items')->updateOrInsert(
                [
                    'store_id'              => $this->storeId,
                    'glass_code'           => $item[2],
                    'phone_model'          => $item[1],
                ],
                [
                    'brand'                => $item[0],
                    'normalized_glass_code' => $item[2],
                    'stock_status'         => $item[3],
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]
            );
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Wholesale Applications  (2)                                        */
    /* ------------------------------------------------------------------ */

    private function createWholesaleApplications(Carbon $now): void
    {
        DB::table('wholesale_applications')->updateOrInsert(
            [
                'store_id' => $this->storeId,
                'user_id'  => $this->wholesalePendingId,
            ],
            [
                'business_name' => 'Mya Phone Repair & Service Shop',
                'phone'         => '09100000005',
                'address'       => 'No. 45, Anawrahta Road, Dagon Township, Yangon',
                'status'        => 'pending',
                'notes'         => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]
        );

        DB::table('wholesale_applications')->updateOrInsert(
            [
                'store_id' => $this->storeId,
                'user_id'  => $this->wholesaleApprovedId,
            ],
            [
                'business_name' => 'Aye Electronics & Mobile Trading',
                'phone'         => '09100000004',
                'address'       => 'No. 78, Bayint Naung Road, Insein Township, Yangon',
                'status'        => 'approved',
                'notes'         => 'Verified business license. Approved by store manager.',
                'created_at'    => $now->copy()->subDays(7),
                'updated_at'    => $now,
            ]
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Orders  (4)                                                        */
    /* ------------------------------------------------------------------ */

    private function createOrders(Carbon $now): void
    {
        // Order 1: Pending contact — guest
        $order1 = DB::table('orders')->where('store_id', $this->storeId)->where('order_number', 'ORD-UAT-001')->first();
        if (!$order1) {
            $order1Id = DB::table('orders')->insertGetId([
                'store_id'           => $this->storeId,
                'user_id'            => null,
                'order_number'       => 'ORD-UAT-001',
                'confirmation_token' => Str::random(40),
                'customer_name'      => 'Guest Customer (UAT)',
                'customer_phone'     => '09777777777',
                'customer_address'   => 'No. 10, Sule Pagoda Road, Kyauktada, Yangon',
                'contact_channel'    => 'viber',
                'pricing_type'       => 'retail',
                'total_amount'       => 15000.00,
                'status'             => 'pending_contact',
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            DB::table('order_items')->insert([
                'order_id'     => $order1Id,
                'product_name' => 'Samsung Galaxy S24 Ultra Tempered Glass',
                'unit_price'   => 15000.00,
                'quantity'     => 1,
                'subtotal'     => 15000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // Order 2: Confirmed — guest
        $order2 = DB::table('orders')->where('store_id', $this->storeId)->where('order_number', 'ORD-UAT-002')->first();
        if (!$order2) {
            $order2Id = DB::table('orders')->insertGetId([
                'store_id'           => $this->storeId,
                'user_id'            => null,
                'order_number'       => 'ORD-UAT-002',
                'confirmation_token' => Str::random(40),
                'customer_name'      => 'Ni Ni (UAT)',
                'customer_phone'     => '09788888888',
                'customer_address'   => 'No. 22, Kabar Aye Pagoda Road, Bahan, Yangon',
                'contact_channel'    => 'telegram',
                'pricing_type'       => 'retail',
                'total_amount'       => 40000.00,
                'status'             => 'confirmed',
                'created_at'         => $now->copy()->subDays(2),
                'updated_at'         => $now->copy()->subDay(),
            ]);

            DB::table('order_items')->insert([
                [
                    'order_id'     => $order2Id,
                    'product_name' => 'iPhone 15 Pro Max Tempered Glass',
                    'unit_price'   => 18000.00,
                    'quantity'     => 1,
                    'subtotal'     => 18000.00,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
                [
                    'order_id'     => $order2Id,
                    'product_name' => 'iPhone 15 Pro Max Silicone Case',
                    'unit_price'   => 22000.00,
                    'quantity'     => 1,
                    'subtotal'     => 22000.00,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ]);
        }

        // Order 3: Confirmed — guest
        $order3 = DB::table('orders')->where('store_id', $this->storeId)->where('order_number', 'ORD-UAT-003')->first();
        if (!$order3) {
            $order3Id = DB::table('orders')->insertGetId([
                'store_id'           => $this->storeId,
                'user_id'            => null,
                'order_number'       => 'ORD-UAT-003',
                'confirmation_token' => Str::random(40),
                'customer_name'      => 'Tin Tin (UAT)',
                'customer_phone'     => '09766666666',
                'customer_address'   => 'No. 5, Shwe Gon Daing Road, Tamwe, Yangon',
                'contact_channel'    => 'viber',
                'pricing_type'       => 'retail',
                'total_amount'       => 25000.00,
                'status'             => 'confirmed',
                'created_at'         => $now->copy()->subDays(5),
                'updated_at'         => $now->copy()->subDays(3),
            ]);

            DB::table('order_items')->insert([
                'order_id'     => $order3Id,
                'product_name' => 'Xiaomi Redmi Note 13 Back Glass',
                'unit_price'   => 25000.00,
                'quantity'     => 1,
                'subtotal'     => 25000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // Order 4: Pending contact — logged-in retail customer (Ma Su)
        $order4 = DB::table('orders')->where('store_id', $this->storeId)->where('order_number', 'ORD-UAT-004')->first();
        if (!$order4) {
            $order4Id = DB::table('orders')->insertGetId([
                'store_id'           => $this->storeId,
                'user_id'            => $this->retailCustomerId,
                'order_number'       => 'ORD-UAT-004',
                'confirmation_token' => Str::random(40),
                'customer_name'      => 'Ma Su (Retail Customer)',
                'customer_phone'     => '09100000006',
                'customer_address'   => 'No. 88, Inya Road, Kamayut, Yangon',
                'contact_channel'    => 'telegram',
                'pricing_type'       => 'retail',
                'total_amount'       => 10000.00,
                'status'             => 'pending_contact',
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            DB::table('order_items')->insert([
                'order_id'     => $order4Id,
                'product_name' => 'Samsung Galaxy A55 Tempered Glass',
                'unit_price'   => 10000.00,
                'quantity'     => 1,
                'subtotal'     => 10000.00,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    /* ================================================================== */
    /*  STORE B — Isolation UAT                                            */
    /*  For cross-store isolation testing only.                            */
    /* ================================================================== */

    private function createStoreB(Carbon $now): void
    {
        $existing = DB::table('stores')->where('slug', 'uat-store-b')->first();
        if ($existing) {
            $this->storeBId = $existing->id;
            return;
        }

        $this->storeBId = DB::table('stores')->insertGetId([
            'name'              => 'UAT Test Store B',
            'business_type'     => 'general_retail',
            'slug'              => 'uat-store-b',
            'viber_number'      => '09000000001',
            'telegram_username' => 'uat_store_b',
            'is_active'         => true,
            'is_primary'        => false,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
    }

    private function createStoreBData(Carbon $now): void
    {
        // 1 category
        $catBId = DB::table('categories')->insertGetId([
            'store_id'    => $this->storeBId,
            'name'        => 'Test Category B (Isolation UAT)',
            'slug'        => 'test-category-b',
            'description' => 'Store B only — used for cross-store isolation testing.',
            'image_path'  => null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // 1 brand
        $brandBId = DB::table('brands')->insertGetId([
            'store_id'   => $this->storeBId,
            'name'       => 'Test Brand B (Isolation UAT)',
            'slug'       => 'test-brand-b',
            'logo_path'  => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2 products (1 in-stock, 1 out-of-stock)
        DB::table('products')->insert([
            [
                'store_id'        => $this->storeBId,
                'category_id'     => $catBId,
                'brand_id'        => $brandBId,
                'sku'             => 'SKU-B-001',
                'name'            => 'Store B Test Product (In Stock)',
                'slug'            => 'store-b-test-product-in-stock',
                'description'     => '[TEST ONLY] This product belongs exclusively to Store B. Used to verify cross-store product isolation.',
                'retail_price'    => 10000.00,
                'wholesale_price' => 7000.00,
                'stock_status'    => 'in_stock',
                'image_path'      => null,
                'warranty'        => 'Test warranty — Store B only',
                'return_policy'   => 'Test return policy — Store B only',
                'is_featured'     => false,
                'is_ecommerce'    => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'store_id'        => $this->storeBId,
                'category_id'     => $catBId,
                'brand_id'        => $brandBId,
                'sku'             => 'SKU-B-002',
                'name'            => 'Store B Test Product (Out of Stock)',
                'slug'            => 'store-b-test-product-out-of-stock',
                'description'     => '[TEST ONLY] Out-of-stock product for Store B isolation testing.',
                'retail_price'    => 15000.00,
                'wholesale_price' => 10000.00,
                'stock_status'    => 'out_of_stock',
                'image_path'      => null,
                'warranty'        => null,
                'return_policy'   => null,
                'is_featured'     => false,
                'is_ecommerce'    => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ]);

        // 1 glass finder item
        DB::table('glass_finder_items')->insert([
            'store_id'              => $this->storeBId,
            'brand'                 => 'Test Brand B',
            'phone_model'           => 'Test Phone B-100',
            'glass_code'            => 'G-TEST-B',
            'normalized_glass_code' => 'G-TEST-B',
            'stock_status'          => 'in_stock',
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        // 1 order (pending_contact, guest)
        $orderBId = DB::table('orders')->insertGetId([
            'store_id'           => $this->storeBId,
            'user_id'            => null,
            'order_number'       => 'ORD-UAT-B-001',
            'confirmation_token' => Str::random(40),
            'customer_name'      => 'Store B Test Customer',
            'customer_phone'     => '09333333333',
            'customer_address'   => 'Test Address, Store B Town, Yangon',
            'contact_channel'    => 'viber',
            'pricing_type'       => 'retail',
            'total_amount'       => 10000.00,
            'status'             => 'pending_contact',
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        DB::table('order_items')->insert([
            'order_id'     => $orderBId,
            'product_name' => 'Store B Test Product (In Stock)',
            'unit_price'   => 10000.00,
            'quantity'     => 1,
            'subtotal'     => 10000.00,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Blog & Audit Logs Seeding                                          */
    /* ------------------------------------------------------------------ */

    private function seedBlogAndAuditLogs(): void
    {
        $store = Store::find($this->storeId);
        if ($store) {
            BlogSeeder::seedForStore($store);
        }

        $this->call(AuditLogSeeder::class, false, ['storeId' => $this->storeId]);
    }
}
