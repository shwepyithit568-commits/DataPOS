<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Local UAT Test Data Seeder
 * ===========================
 *
 * Creates realistic test data for Local User Acceptance Testing.
 *
 * **SAFETY GUARDS:**
 *   - Will ABORT if APP_ENV is 'production'.
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
    private int $managerId;
    private int $staffId;
    private int $wholesaleApprovedId;
    private int $wholesalePendingId;
    private int $retailCustomerId;
    private int $storeBManagerId;

    public function run(): void
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


        $this->command?->info('🌱 Seeding UAT test data...');

        $now = Carbon::now();

        $this->createStore($now);
        $this->createStorefrontSettings($now);
        $this->createHomeBanners($now);
        $this->createStoreB($now);
        $this->createUsers($now);
        $this->assignStoreRoles();
        $this->assignStoreBRoles();
        $this->createCategories($now);
        $this->createBrands($now);
        $this->createProducts($now);
        $this->createGlassFinderItems($now);
        $this->createWholesaleApplications($now);
        $this->createOrders($now);
        $this->createStoreBData($now);
        $this->createAuditLogs($now);

        $this->command?->info('✅ UAT test data seeded successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  Store                                                              */
    /* ------------------------------------------------------------------ */

    private function createStore(Carbon $now): void
    {
        // Idempotent: use existing store if already seeded
        $existing = DB::table('stores')->where('slug', 'datapos-mobile')->first();
        if ($existing) {
            $this->storeId = $existing->id;
            return;
        }

        $this->storeId = DB::table('stores')->insertGetId([
            'name'             => 'DataPOS',
            'slug'             => 'datapos-mobile',
            'viber_number'     => '09123456789',
            'telegram_username' => 'datapos_mobile',
            'is_active'        => true,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Storefront Settings                                                */
    /* ------------------------------------------------------------------ */

    private function createStorefrontSettings(Carbon $now): void
    {
        // Idempotent: skip if already exists
        if (DB::table('storefront_settings')->where('store_id', $this->storeId)->exists()) {
            return;
        }

        DB::table('storefront_settings')->insert([
            'store_id'         => $this->storeId,
            'store_name'       => 'DataPOS (အလင်းသစ် မိုဘိုင်း)',
            'logo_path'        => null,
            'address'          => "No. 123, Maha Bandula Road\nBotataung Township\nYangon, Myanmar",
            'phone'            => '09123456789',
            'opening_hours'    => 'Mon - Sat: 9:00 AM - 6:00 PM, Sun: Closed',
            'viber_number'     => '09123456789',
            'telegram_username' => 'datapos_mobile',
            'delivery_info'    => "Yangon area: 1-2 business days.\nOther regions: 3-5 business days via express delivery.\nFree delivery for orders over Ks 50,000 within Yangon.",
            'payment_info'     => "KBZ Pay: 09123456789\nWave Pay: 09123456789\nBank transfer available upon request.\nCash on delivery available in Yangon.",
            'default_language' => 'my',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Home Banners                                                       */
    /* ------------------------------------------------------------------ */

    private function createHomeBanners(Carbon $now): void
    {
        // Idempotent: skip if banners already seeded for this store
        if (DB::table('home_banners')->where('store_id', $this->storeId)->exists()) {
            return;
        }

        $banners = [
            [
                'store_id'   => $this->storeId,
                'title'      => 'New Arrivals — Tempered Glass',
                'image_path' => 'banners/new-arrivals.jpg',
                'link_url'   => '/store/datapos-mobile/products',
                'sort_order' => 1,
                'is_active'  => true,
            ],
            [
                'store_id'   => $this->storeId,
                'title'      => 'Wholesale Discount Available',
                'image_path' => 'banners/wholesale.jpg',
                'link_url'   => '/store/datapos-mobile/wholesale/apply',
                'sort_order' => 2,
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
    /*  Users                                                              */
    /* ------------------------------------------------------------------ */

    private function createUsers(Carbon $now): void
    {
        $users = [
            [
                'name'     => 'Owner (Platform Admin)',
                'phone'    => '09100000001',
                'password' => Hash::make('password'),
                'role'     => 'platform_owner',
            ],
            [
                'name'     => 'Mg Hla (Store Manager)',
                'phone'    => '09100000002',
                'password' => Hash::make('password'),
                // POS override-approval PIN (demo): manager PIN for deep discounts.
                'pos_pin'  => Hash::make('1234'),
                'role'     => 'customer',
            ],
            [
                'name'     => 'Ko Kyaw (Staff)',
                'phone'    => '09100000003',
                'password' => Hash::make('password'),
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
                'role'     => 'customer',
            ],
        ];

        $ids = [];
        $phones = array_column($users, 'phone');
        $existing = DB::table('users')->whereIn('phone', $phones)->pluck('id', 'phone');

        foreach ($users as $user) {
            if (isset($existing[$user['phone']])) {
                $ids[] = $existing[$user['phone']];
            } else {
                $user['created_at'] = $now;
                $user['updated_at'] = $now;
                $ids[] = DB::table('users')->insertGetId($user);
            }
        }

        $this->ownerId           = $ids[0];
        $this->managerId         = $ids[1];
        $this->staffId           = $ids[2];
        $this->wholesaleApprovedId = $ids[3];
        $this->wholesalePendingId  = $ids[4];
        $this->retailCustomerId  = $ids[5];
        $this->storeBManagerId   = $ids[6];
    }

    /* ------------------------------------------------------------------ */
    /*  Store Roles (Pivot)                                                */
    /* ------------------------------------------------------------------ */

    private function assignStoreRoles(): void
    {
        $pivot = [
            ['user_id' => $this->managerId,         'role' => 'store_manager',      'status' => 'active'],
            ['user_id' => $this->staffId,            'role' => 'staff',              'status' => 'active'],
            ['user_id' => $this->wholesaleApprovedId, 'role' => 'wholesale_customer', 'status' => 'active'],
            ['user_id' => $this->wholesalePendingId,  'role' => 'wholesale_customer', 'status' => 'pending'],
            ['user_id' => $this->retailCustomerId,   'role' => 'retail_customer',    'status' => 'active'],
        ];

        $now = Carbon::now();
        foreach ($pivot as $row) {
            $row['store_id'] = $this->storeId;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            // Idempotent: skip if this user-store-role already exists
            $alreadyExists = DB::table('store_user')
                ->where('store_id', $row['store_id'])
                ->where('user_id', $row['user_id'])
                ->exists();
            if (!$alreadyExists) {
                DB::table('store_user')->insert($row);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Store B Roles (Pivot) — Isolation UAT                              */
    /* ------------------------------------------------------------------ */

    private function assignStoreBRoles(): void
    {
        $alreadyExists = DB::table('store_user')
            ->where('store_id', $this->storeBId)
            ->where('user_id', $this->storeBManagerId)
            ->exists();
        if (!$alreadyExists) {
            DB::table('store_user')->insert([
                'store_id'    => $this->storeBId,
                'user_id'     => $this->storeBManagerId,
                'role'        => 'store_manager',
                'status'      => 'active',
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Categories                                                         */
    /* ------------------------------------------------------------------ */

    private array $categoryIds = [];

    private function createCategories(Carbon $now): void
    {
        $categories = [
            ['name' => 'Tempered Glass (ဖန်ကာကွယ်)',        'slug' => 'tempered-glass'],
            ['name' => 'Back Glass (နောက်ဖုံးဖန်)',         'slug' => 'back-glass'],
            ['name' => 'Camera Lens (ကင်မရာမှန်ဘီလူး)',    'slug' => 'camera-lens'],
            ['name' => 'Chargers & Cables (အားသွင်းကြိုး)', 'slug' => 'chargers-cables'],
            ['name' => 'Phone Cases (ဖုန်းအိတ်)',          'slug' => 'phone-cases'],
        ];

        foreach ($categories as $cat) {
            // Idempotent: reuse existing category
            $existing = DB::table('categories')
                ->where('store_id', $this->storeId)
                ->where('slug', $cat['slug'])
                ->first();

            if ($existing) {
                $this->categoryIds[] = $existing->id;
            } else {
                $this->categoryIds[] = DB::table('categories')->insertGetId([
                    'store_id'    => $this->storeId,
                    'name'        => $cat['name'],
                    'slug'        => $cat['slug'],
                    'description' => null,
                    'image_path'  => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
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
            ['name' => 'Samsung',  'slug' => 'samsung'],
            ['name' => 'iPhone',   'slug' => 'iphone'],
            ['name' => 'Xiaomi',   'slug' => 'xiaomi'],
            ['name' => 'OPPO',     'slug' => 'oppo'],
            ['name' => 'Vivo',     'slug' => 'vivo'],
        ];

        foreach ($brands as $brand) {
            // Idempotent: reuse existing brand
            $existing = DB::table('brands')
                ->where('store_id', $this->storeId)
                ->where('slug', $brand['slug'])
                ->first();

            if ($existing) {
                $this->brandIds[] = $existing->id;
            } else {
                $this->brandIds[] = DB::table('brands')->insertGetId([
                    'store_id'   => $this->storeId,
                    'name'       => $brand['name'],
                    'slug'       => $brand['slug'],
                    'logo_path'  => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Products  (25 total)                                               */
    /* ------------------------------------------------------------------ */

    private function createProducts(Carbon $now): void
    {
        // [name, slug, category_index, brand_index, retail, wholesale, stock, featured, warranty, return_policy]
        $products = [
            // -- Featured products (8) --
            ['Samsung Galaxy S24 Ultra Tempered Glass',    'samsung-s24-ultra-tg',    0, 0, 15000, 9000,  'in_stock',    true,  '6 months warranty against defects', 'Return within 7 days if unopened'],
            ['iPhone 15 Pro Max Tempered Glass',            'iphone-15pm-tg',          0, 1, 18000, 11000, 'in_stock',    true,  '6 months warranty against defects', 'Return within 7 days if unopened'],
            ['Xiaomi Redmi Note 13 Back Glass',             'xiaomi-note13-bg',        1, 2, 25000, 16000, 'in_stock',    true,  'No warranty on glass',             'Non-returnable (hygiene product)'],
            ['Samsung Galaxy S24 Camera Lens Protector',    'samsung-s24-cam',         2, 0, 8000,  5000,  'in_stock',    true,  '12 months warranty',               'Return within 14 days'],
            ['iPhone 15 Pro Max Silicone Case',             'iphone-15pm-case',        4, 1, 22000, 14000, 'in_stock',    true,  '6 months warranty',                'Return within 7 days'],
            ['OPPO Reno 11 Tempered Glass',                 'oppo-reno11-tg',          0, 3, 12000, 7500,  'in_stock',    true,  '6 months warranty',                'Return within 7 days if unopened'],
            ['Vivo V30 Back Glass',                         'vivo-v30-bg',             1, 4, 28000, 18000, 'in_stock',    true,  'No warranty on glass',             'Non-returnable'],
            ['Samsung Galaxy A55 Tempered Glass',           'samsung-a55-tg',          0, 0, 10000, 6000,  'in_stock',    true,  '6 months warranty',                'Return within 7 days if unopened'],

            // -- Non-featured, in-stock --
            ['iPhone 14 Tempered Glass',                    'iphone-14-tg',            0, 1, 14000, 8500,  'in_stock',    false, '6 months warranty',                'Return within 7 days if unopened'],
            ['iPhone 14 Pro Back Glass',                    'iphone-14-pro-bg',        1, 1, 30000, 20000, 'in_stock',    false, 'No warranty on glass',             'Non-returnable'],
            ['Xiaomi Redmi Note 12 Tempered Glass',         'xiaomi-note12-tg',        0, 2, 9000,  5500,  'in_stock',    false, '6 months warranty',                'Return within 7 days if unopened'],
            ['Xiaomi 14T Camera Lens',                      'xiaomi-14t-cam',          2, 2, 10000, 6500,  'in_stock',    false, '12 months warranty',               'Return within 14 days'],
            ['OPPO Find N3 Flip Tempered Glass',            'oppo-n3-flip-tg',         0, 3, 16000, 10000, 'in_stock',    false, '6 months warranty',                'Return within 7 days if unopened'],
            ['Vivo V29e Tempered Glass',                    'vivo-v29e-tg',            0, 4, 11000, 7000,  'in_stock',    false, '6 months warranty',                'Return within 7 days if unopened'],
            ['Samsung Galaxy S24 Ultra Silicone Case',      'samsung-s24-case',        4, 0, 15000, 9500,  'in_stock',    false, '6 months warranty',                'Return within 7 days'],
            ['iPhone 15 Pro Max Clear Case',                'iphone-15pm-clear',       4, 1, 18000, 11000, 'in_stock',    false, '6 months warranty',                'Return within 7 days'],
            ['Xiaomi Redmi Note 13 Silicone Case',          'xiaomi-note13-case',      4, 2, 8000,  5000,  'in_stock',    false, '6 months warranty',                'Return within 7 days'],
            ['USB-C Fast Charger 25W',                      'usbc-charger-25w',        3, 0, 15000, 9000,  'in_stock',    false, '12 months warranty',               'Return within 14 days'],
            ['Lightning Cable 2M',                          'lightning-cable-2m',      3, 1, 12000, 7000,  'in_stock',    false, '6 months warranty',                'Return within 7 days'],
            ['Type-C Cable 2M Braided',                     'typec-cable-braided',     3, 2, 8000,  5000,  'in_stock',    false, '6 months warranty',                'Return within 7 days'],
            ['OPPO Reno 11 Silicone Case',                  'oppo-reno11-case',        4, 3, 10000, 6000,  'in_stock',    false, '6 months warranty',                'Return within 7 days'],
            ['Samsung Galaxy A15 Tempered Glass',            'samsung-a15-tg',          0, 0, 8000,  5000,  'in_stock',    false, '6 months warranty',                'Return within 7 days if unopened'],

            // -- Out-of-stock (3) --
            ['Samsung Galaxy S23 Ultra Tempered Glass',     'samsung-s23-ultra-tg',    0, 0, 15000, 9000,  'out_of_stock', false, '6 months warranty',                'Return within 7 days if unopened'],
            ['iPhone 13 Tempered Glass',                    'iphone-13-tg',            0, 1, 12000, 7500,  'out_of_stock', false, '6 months warranty',                'Return within 7 days if unopened'],
            ['Xiaomi Redmi 12 Back Glass',                  'xiaomi-redmi12-bg',       1, 2, 20000, 13000, 'out_of_stock', false, 'No warranty on glass',             'Non-returnable'],
        ];

        foreach ($products as $p) {
            // Idempotent: skip if product with this slug already exists in this store
            if (DB::table('products')->where('store_id', $this->storeId)->where('slug', $p[1])->exists()) {
                continue;
            }
            DB::table('products')->insert([
                'store_id'        => $this->storeId,
                'category_id'     => $this->categoryIds[$p[2]],
                'brand_id'        => $this->brandIds[$p[3]],
                'sku'             => 'SKU-' . strtoupper(Str::random(8)),
                'name'            => $p[0],
                'slug'            => $p[1],
                'description'     => "High-quality {$p[0]} compatible with your device. Durable and reliable.",
                'retail_price'    => $p[4],
                'wholesale_price' => $p[5],
                'stock_status'    => $p[6],
                'image_path'      => null,
                'warranty'        => $p[8],
                'return_policy'   => $p[9],
                'is_featured'     => $p[7],
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Glass Finder Items  (15)                                           */
    /* ------------------------------------------------------------------ */

    private function createGlassFinderItems(Carbon $now): void
    {
        $items = [
            // [brand_index, phone_model, glass_code, stock]
            [0, 'Samsung Galaxy S24 Ultra',  'G-S24U-F',   'in_stock'],
            [0, 'Samsung Galaxy S24 Ultra',  'G-S24U-B',   'in_stock'],
            [0, 'Samsung Galaxy S24',        'G-S24-F',    'in_stock'],
            [0, 'Samsung Galaxy A55',        'G-A55-F',    'in_stock'],
            [0, 'Samsung Galaxy A55',        'G-A55-B',    'out_of_stock'],
            [1, 'iPhone 15 Pro Max',         'G-I15PM-F',  'in_stock'],
            [1, 'iPhone 15 Pro Max',         'G-I15PM-B',  'in_stock'],
            [1, 'iPhone 15 Pro',             'G-I15P-F',   'in_stock'],
            [1, 'iPhone 14 Pro Max',         'G-I14PM-F',  'in_stock'],
            [2, 'Xiaomi Redmi Note 13',      'G-RN13-F',   'in_stock'],
            [2, 'Xiaomi Redmi Note 13',      'G-RN13-B',   'in_stock'],
            [2, 'Xiaomi 14T',                'G-X14T-F',   'in_stock'],
            [3, 'OPPO Reno 11',              'G-OR11-F',   'in_stock'],
            [3, 'OPPO Find N3 Flip',         'G-OFN3-F',   'in_stock'],
            [4, 'Vivo V30',                  'G-VV30-F',   'in_stock'],
        ];

        foreach ($items as $item) {
            DB::table('glass_finder_items')->insert([
                'store_id'             => $this->storeId,
                'brand'                => ['Samsung', 'iPhone', 'Xiaomi', 'OPPO', 'Vivo'][$item[0]],
                'phone_model'          => $item[1],
                'glass_code'           => $item[2],
                'normalized_glass_code' => $item[2],
                'stock_status'         => $item[3],
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Wholesale Applications  (2)                                        */
    /* ------------------------------------------------------------------ */

    private function createWholesaleApplications(Carbon $now): void
    {
        // Application for the pending-wholesale user
        DB::table('wholesale_applications')->insert([
            'store_id'      => $this->storeId,
            'user_id'       => $this->wholesalePendingId,
            'business_name' => 'Mya Phone Repair Shop',
            'phone'         => '09100000005',
            'address'       => 'No. 45, Anawrahta Road, Dagon Township, Yangon',
            'status'        => 'pending',
            'notes'         => null,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        // Application for the approved-wholesale user
        DB::table('wholesale_applications')->insert([
            'store_id'      => $this->storeId,
            'user_id'       => $this->wholesaleApprovedId,
            'business_name' => 'Aye Electronics Trading',
            'phone'         => '09100000004',
            'address'       => 'No. 78, Bayint Naung Road, Insein Township, Yangon',
            'status'        => 'approved',
            'notes'         => 'Verified business license. Approved by manager.',
            'created_at'    => $now->copy()->subDays(7),
            'updated_at'    => $now,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Orders  (4)                                                        */
    /* ------------------------------------------------------------------ */

    private function createOrders(Carbon $now): void
    {
        // Order 1: Pending contact — guest
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

        // Order 2: Confirmed — guest
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
            'order_id'     => $order2Id,
            'product_name' => 'iPhone 15 Pro Max Tempered Glass',
            'unit_price'   => 18000.00,
            'quantity'     => 1,
            'subtotal'     => 18000.00,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        DB::table('order_items')->insert([
            'order_id'     => $order2Id,
            'product_name' => 'iPhone 15 Pro Max Silicone Case',
            'unit_price'   => 22000.00,
            'quantity'     => 1,
            'subtotal'     => 22000.00,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // Order 3: Completed — guest
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

        // Order 4: Pending contact — logged-in retail customer (Ma Su)
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
            'total_amount'       => 18000.00,
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

    /* ================================================================== */
    /*  STORE B — Isolation UAT                                            */
    /*  For cross-store isolation testing only.                            */
    /* ================================================================== */

    private function createStoreB(Carbon $now): void
    {
        // Idempotent: use existing Store B if already seeded
        $existing = DB::table('stores')->where('slug', 'uat-store-b')->first();
        if ($existing) {
            $this->storeBId = $existing->id;
            return;
        }

        $this->storeBId = DB::table('stores')->insertGetId([
            'name'             => 'UAT Test Store B',
            'slug'             => 'uat-store-b',
            'viber_number'     => '09000000001',
            'telegram_username' => 'uat_store_b',
            'is_active'        => true,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        $this->command?->info('  └─ Store B created (isolation UAT)');
    }

    private function createStoreBData(Carbon $now): void
    {
        // 1 category
        $catBId = DB::table('categories')->insertGetId([
            'store_id'   => $this->storeBId,
            'name'       => 'Test Category B (Isolation UAT)',
            'slug'       => 'test-category-b',
            'description' => 'Store B only — used for cross-store isolation testing.',
            'image_path'  => null,
            'created_at' => $now,
            'updated_at' => $now,
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
    /*  Audit Logs                                                         */
    /* ------------------------------------------------------------------ */

    private function createAuditLogs(Carbon $now): void
    {
        $logs = [
            [
                'store_id'    => $this->storeId,
                'actor_id'    => $this->ownerId, // U Ba (Platform Owner / Manager)
                'action'      => 'bulk_price_updated',
                'entity_type' => 'product',
                'entity_id'   => 1,
                'metadata'    => json_encode([
                    'product_name' => 'Samsung Galaxy S24 Ultra (256GB)',
                    'old_price'    => 3850000,
                    'new_price'    => 3900000,
                    'reason'       => 'USD exchange rate adjustment (+50,000 Ks)',
                    'affected_qty' => 5,
                ]),
                'ip_address'  => '192.168.1.100',
                'created_at'  => $now->copy()->subMinutes(15),
            ],
            [
                'store_id'    => $this->storeId,
                'actor_id'    => $this->managerId, // Daw Mya (Store Manager)
                'action'      => 'inventory_adjustment_posted',
                'entity_type' => 'inventory_adjustment',
                'entity_id'   => 101,
                'metadata'    => json_encode([
                    'ref_number' => 'ADJ-20260825-01',
                    'item'       => 'Type-C Fast Charging Cable 65W',
                    'qty_change' => -2,
                    'type'       => 'damage_loss',
                    'reason'     => 'Damaged during showcase demo',
                ]),
                'ip_address'  => '192.168.1.102',
                'created_at'  => $now->copy()->subHours(1),
            ],
            [
                'store_id'    => $this->storeId,
                'actor_id'    => $this->staffId, // Ko Kyaw (Staff)
                'action'      => 'pos_receipt_reprinted',
                'entity_type' => 'pos_sale',
                'entity_id'   => 201,
                'metadata'    => json_encode([
                    'voucher_no'      => 'INV-20260825-0042',
                    'customer_name'   => 'Walk-in Customer',
                    'total_amount'    => 45000,
                    'reprint_reason'  => 'Customer requested duplicate slip for warranty claim',
                    'original_printed'=> $now->copy()->subHours(3)->toDateTimeString(),
                ]),
                'ip_address'  => '192.168.1.105',
                'created_at'  => $now->copy()->subHours(2),
            ],
            [
                'store_id'    => $this->storeId,
                'actor_id'    => $this->managerId,
                'action'      => 'financial_transaction_approved',
                'entity_type' => 'financial_transaction',
                'entity_id'   => 501,
                'metadata'    => json_encode([
                    'tx_type'     => 'cash_withdrawal',
                    'amount'      => 150000,
                    'account'     => 'Main Cash Drawer',
                    'category'    => 'Store Utility & Refreshments',
                    'approved_by' => 'Daw Mya (Manager)',
                ]),
                'ip_address'  => '192.168.1.102',
                'created_at'  => $now->copy()->subHours(4),
            ],
            [
                'store_id'    => $this->storeId,
                'actor_id'    => $this->managerId,
                'action'      => 'daily_closing_approved',
                'entity_type' => 'daily_closing',
                'entity_id'   => 301,
                'metadata'    => json_encode([
                    'closing_date'      => $now->copy()->subDay()->toDateString(),
                    'system_total'      => 1850000,
                    'actual_cash_count' => 1850000,
                    'discrepancy'       => 0,
                    'status'            => 'balanced',
                ]),
                'ip_address'  => '192.168.1.102',
                'created_at'  => $now->copy()->subDay()->setHour(21)->setMinute(30),
            ],
            [
                'store_id'    => $this->storeId,
                'actor_id'    => $this->ownerId,
                'action'      => 'staff_role_assigned',
                'entity_type' => 'user',
                'entity_id'   => $this->staffId,
                'metadata'    => json_encode([
                    'target_user' => 'Ko Kyaw (Staff)',
                    'role_name'   => 'Cashier & Sales Rep',
                    'permissions' => ['pos_sale', 'view_catalog', 'issue_receipt'],
                    'assigned_by' => 'U Ba (Platform Owner)',
                ]),
                'ip_address'  => '192.168.1.100',
                'created_at'  => $now->copy()->subDays(2),
            ],
            [
                'store_id'    => $this->storeId,
                'actor_id'    => $this->staffId,
                'action'      => 'customer_debt_collected',
                'entity_type' => 'customer_debt',
                'entity_id'   => 401,
                'metadata'    => json_encode([
                    'customer_name'   => 'U Tun (Regular Wholesale)',
                    'amount_collected'=> 250000,
                    'payment_method'  => 'KPay Transfer',
                    'remaining_debt'  => 50000,
                ]),
                'ip_address'  => '192.168.1.105',
                'created_at'  => $now->copy()->subDays(3),
            ],
            [
                'store_id'    => $this->storeId,
                'actor_id'    => null,
                'action'      => 'pos_pin_failed',
                'entity_type' => 'pos_terminal',
                'entity_id'   => 1,
                'metadata'    => json_encode([
                    'terminal'    => 'POS-Counter-01',
                    'attempt'     => 'Manager PIN required for 15% discount',
                    'result'      => 'Invalid PIN entered',
                    'throttled'   => false,
                ]),
                'ip_address'  => '192.168.1.105',
                'created_at'  => $now->copy()->subDays(4),
            ],
        ];

        DB::table('audit_logs')->insert($logs);
    }
}

