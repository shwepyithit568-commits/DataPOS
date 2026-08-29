<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Role;
use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\POS\Services\StoreLocationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StoreOnboardingService
{
    /**
     * Edition configuration manifests.
     */
    public const EDITIONS = [
        'mobile_electronics' => [
            'name_en'      => 'Mobile & Electronics Edition',
            'name_mm'      => 'ဖုန်း၊ ကွန်ပျူတာနှင့် လျှပ်စစ်ပစ္စည်း အရောင်း/ပြင်ဆိုင်',
            'description'  => 'Equipped with Glass Finder, IMEI tracking, Repair jobs, and Wholesale workflows.',
            'profile'      => 'mobile_electronics',
            'font_preset'  => 'outfit',
            'grid_density' => 'compact',
            'categories'   => [
                ['name' => 'Mobile Phones', 'slug' => 'mobile-phones', 'description' => 'Smartphones and button phones', 'icon' => '📱'],
                ['name' => 'Phone Accessories', 'slug' => 'phone-accessories', 'description' => 'Cases, chargers, cables, earbuds', 'icon' => '🎧'],
                ['name' => 'CCTV & Security', 'slug' => 'cctv-security', 'description' => 'Cameras, NVRs, and security equipment', 'icon' => '📹'],
                ['name' => 'Computer & Laptop', 'slug' => 'computer-laptop', 'description' => 'Laptops, monitors, and parts', 'icon' => '💻'],
                ['name' => 'Spare Parts & Glass', 'slug' => 'spare-parts-glass', 'description' => 'Touch screens, LCDs, batteries', 'icon' => '🔧'],
            ],
            'brands'       => ['Apple', 'Samsung', 'Xiaomi', 'Realme', 'Vivo', 'Oppo', 'Anker', 'Hikvision'],
        ],
        'general_retail' => [
            'name_en'      => 'General Retail & Mart Edition',
            'name_mm'      => 'ကုန်စုံဆိုင်၊ လူသုံးကုန်နှင့် နေ့စဉ်သုံး လက်လီ/လက်ကား',
            'description'  => 'Optimized for marts, grocery stores, daily retail with fast barcode scanning and customer loyalty.',
            'profile'      => 'general_retail',
            'font_preset'  => 'inter',
            'grid_density' => 'comfortable',
            'categories'   => [
                ['name' => 'Beverages & Drinks', 'slug' => 'beverages-drinks', 'description' => 'Juices, soft drinks, tea, coffee', 'icon' => '🥤'],
                ['name' => 'Snacks & Food', 'slug' => 'snacks-food', 'description' => 'Biscuits, noodles, dried food, snacks', 'icon' => '🍜'],
                ['name' => 'Household & Cleaning', 'slug' => 'household-cleaning', 'description' => 'Detergent, tissues, cleaning goods', 'icon' => '🧼'],
                ['name' => 'Personal Care', 'slug' => 'personal-care', 'description' => 'Soap, shampoo, skincare', 'icon' => '🧴'],
                ['name' => 'Stationery & Office', 'slug' => 'stationery-office', 'description' => 'Pens, books, office supplies', 'icon' => '✏️'],
            ],
            'brands'       => ['Nestle', 'Unilever', 'Premier', 'CP', 'Lotte', 'Colgate'],
        ],
        'pharmacy_healthcare' => [
            'name_en'      => 'Pharmacy & Healthcare Edition',
            'name_mm'      => 'ဆေးဆိုင်နှင့် ကျန်းမာရေး အထောက်အကူပြုပစ္စည်း',
            'description'  => 'Configured for pharmacies, drug stores, and clinics with medical batches and unit sales.',
            'profile'      => 'pharmacy',
            'font_preset'  => 'inter',
            'grid_density' => 'compact',
            'categories'   => [
                ['name' => 'Prescription Medicine', 'slug' => 'prescription-medicine', 'description' => 'Antibiotics, chronic disease medications', 'icon' => '💊'],
                ['name' => 'OTC & Pain Relief', 'slug' => 'otc-pain-relief', 'description' => 'Fever, cough, cold, and pain relief', 'icon' => '🩹'],
                ['name' => 'Vitamins & Supplements', 'slug' => 'vitamins-supplements', 'description' => 'Dietary supplements, health tonics', 'icon' => '✨'],
                ['name' => 'Medical Devices', 'slug' => 'medical-devices', 'description' => 'Thermometers, BP monitors, first-aid kits', 'icon' => '🩺'],
                ['name' => 'Baby & Mother Care', 'slug' => 'baby-mother-care', 'description' => 'Diapers, formula, baby skincare', 'icon' => '🍼'],
            ],
            'brands'       => ['Mega We Care', 'Blackmores', 'Biochemic', 'FAME', 'GSK', 'Sanofi'],
        ],
    ];

    public function __construct(
        protected StoreLocationService $locationService
    ) {}

    /**
     * Provision a new store with the chosen industry edition preset, settings, and owner account.
     *
     * @param array<string, mixed> $data
     * @return Store
     */
    public function provisionStore(array $data): Store
    {
        $editionKey = $data['edition'] ?? 'mobile_electronics';
        $edition = self::EDITIONS[$editionKey] ?? self::EDITIONS['mobile_electronics'];

        // Recommended theme comes from the business profile (ThemePlan §7),
        // never from the edition key — single source of truth (T6).
        $themePreset = \App\Themes\ThemeRecommendation::recommendForProfile($edition['profile']);

        return DB::transaction(function () use ($data, $edition, $themePreset) {
            // 1. Create Store Record
            $store = Store::create([
                'name'              => trim($data['name']),
                'slug'              => trim($data['slug']),
                'business_profile'  => $edition['profile'],
                'viber_number'      => $data['viber_number'] ?? null,
                'telegram_username' => $data['telegram_username'] ?? null,
                'is_active'         => $data['is_active'] ?? true,
                'is_primary'        => $data['is_primary'] ?? false,
            ]);

            // 2. Ensure default branch and warehouse
            $this->locationService->ensureDefaults($store);

            // 3. Create Storefront Settings
            $themeColors = \App\Themes\ThemeRegistry::get($themePreset)->colors;
            StorefrontSetting::create([
                'store_id'            => $store->id,
                'store_name'          => trim($data['name']),
                'phone'               => $data['phone'] ?? null,
                'viber_number'        => $data['viber_number'] ?? null,
                'telegram_username'   => $data['telegram_username'] ?? null,
                'address'             => $data['address'] ?? null,
                'opening_hours'       => $data['opening_hours'] ?? '9:00 AM - 8:00 PM (Daily)',
                'delivery_info'       => $data['delivery_info'] ?? null,
                'payment_info'        => $data['payment_info'] ?? null,
                'default_language'    => $data['default_language'] ?? 'my',
                'theme_preset'        => $themePreset,
                'theme_primary_color' => $themeColors['primary'],
                'theme_accent_color'  => $themeColors['accent'],
                'theme_header_bg'     => $themeColors['header_bg'],
                'theme_body_bg'       => $themeColors['body_bg'],
                'theme_glow_style'    => $themeColors['glow_style'],
                'theme_dark_mode'     => $themeColors['dark_mode'],
                'font_preset'         => $edition['font_preset'],
                'grid_density'        => $edition['grid_density'],
            ]);

            // 4. Seed Edition Categories
            if (!empty($edition['categories'])) {
                foreach ($edition['categories'] as $catData) {
                    Category::create([
                        'store_id'    => $store->id,
                        'name'        => $catData['name'],
                        'slug'        => $catData['slug'],
                        'description' => $catData['description'],
                        'icon'        => $catData['icon'] ?? null,
                    ]);
                }
            }

            // 5. Seed Edition Brands
            if (!empty($edition['brands'])) {
                foreach ($edition['brands'] as $brandName) {
                    Brand::create([
                        'store_id' => $store->id,
                        'name'     => $brandName,
                        'slug'     => Str::slug($brandName),
                    ]);
                }
            }

            // 6. Provision Store Owner Account
            $this->provisionOwnerAccount($store, $data);

            return $store;
        });
    }

    /**
     * Create or link a dedicated Store Owner account.
     *
     * @param Store $store
     * @param array<string, mixed> $data
     * @return User|null
     */
    public function provisionOwnerAccount(Store $store, array $data): ?User
    {
        $ownerPhone = trim($data['owner_phone'] ?? '');
        if (empty($ownerPhone)) {
            return null;
        }

        $ownerName = trim($data['owner_name'] ?? $store->name . ' Owner');
        $ownerPassword = !empty($data['owner_password']) ? Hash::make($data['owner_password']) : Hash::make('password');
        $posPin = !empty($data['owner_pos_pin']) ? Hash::make($data['owner_pos_pin']) : Hash::make('1234');

        $owner = User::firstOrCreate(
            ['phone' => $ownerPhone],
            [
                'name'     => $ownerName,
                'email'    => $data['owner_email'] ?? ($store->slug . '.owner@datapos.local'),
                'password' => $ownerPassword,
                'pos_pin'  => $posPin,
                'role'     => 'customer',
            ]
        );

        // Ensure default roles exist for this store and fetch store_owner role
        \App\Models\StaffRole::bootstrapDefaultRoles($store);
        $ownerRoleId = \App\Models\StaffRole::where('store_id', $store->id)
            ->where('slug', 'store_owner')
            ->value('id');

        $store->users()->syncWithoutDetaching([
            $owner->id => [
                'role'          => 'store_owner',
                'staff_role_id' => $ownerRoleId,
                'status'        => 'active',
            ],
        ]);

        return $owner;
    }
}
