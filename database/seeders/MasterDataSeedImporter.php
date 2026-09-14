<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductMasterPreset;
use App\Models\Store;
use App\Models\VariantPreset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MasterDataSeedImporter
 *
 * Myanmar tech business seed data (idempotent via updateOrCreate).
 * Called from ProductMasterDataController::seedImport().
 * Each group can be toggled independently.
 */
class MasterDataSeedImporter
{
    // =====================================================================
    // BUSINESS TYPES
    // =====================================================================

    /** Available business type keys => metadata */
    public static function businessTypes(): array
    {
        return [
            'tech'    => ['label' => 'Tech / Mobile Shop',        'icon' => '📱', 'color' => 'sky',    'desc' => 'Phone, Accessories, CCTV, Computer, Network'],
            'fashion' => ['label' => 'Fashion / Clothing Shop',   'icon' => '👗', 'color' => 'pink',   'desc' => 'Clothing, Footwear, Bags, Jewelry, Accessories'],
            'general' => ['label' => 'General Retail Store',      'icon' => '🛒', 'color' => 'amber',  'desc' => 'Mixed retail — basic shared data only'],
        ];
    }

    // =====================================================================
    // PUBLIC API
    // =====================================================================

    /**
     * Returns preview metadata (counts) for the seed-data tab UI.
     * @param string $businessType  'tech' | 'fashion' | 'general'
     */
    public function getPreviewData(string $businessType = 'tech'): array
    {
        $brands          = $this->brands($businessType);
        $categories      = $this->categoryTree($businessType);
        $variantPresets  = $this->variantPresets($businessType);

        return [
            'brands'          => [
                'count'  => count($brands),
                'label'  => 'အမှတ်တံဆိပ်များ (Brands)',
                'icon'   => '🏷️',
                'sample' => array_slice($brands, 0, 10),
            ],
            'categories'      => [
                'count'  => count($categories),
                'label'  => 'အမျိုးအစား အဖိုးများ (Categories)',
                'icon'   => '📂',
                'sample' => $categories,
            ],
            'shelves'         => ['count' => count($this->shelves($businessType)),         'label' => 'ကုန်ပစ္စည်း ထားရှိရာ စင် (Shelves)', 'icon' => '🗄️'],
            'warranties'      => ['count' => count($this->warranties($businessType)),      'label' => 'အာမခံ Template များ (Warranties)',  'icon' => '🛡️'],
            'return_policies' => ['count' => count($this->returnPolicies($businessType)),  'label' => 'ပြန်လဲမူဝါဒ (Return Policies)',     'icon' => '🔄'],
            'variant_presets' => [
                'count'  => count($variantPresets),
                'label'  => 'Variant Preset Matrix',
                'icon'   => '⚡',
                'sample' => $variantPresets,
            ],
        ];
    }


    /**
     * Import selected groups for a given store.
     *
     * @param  Store    $store
     * @param  string[] $groups        e.g. ['brands','categories',...]
     * @param  string   $businessType  'tech' | 'fashion' | 'general'
     * @param  string   $cleanMode     'none' | 'master_only' | 'full'
     * @return array<string,int> group => rows_upserted
     */
    public function importForStore(Store $store, array $groups, string $businessType = 'tech', string $cleanMode = 'none'): array
    {
        $result = [];

        DB::transaction(function () use ($store, $groups, $businessType, $cleanMode, &$result) {
            if ($cleanMode === 'full') {
                $this->purgeFullStoreData($store);
            } elseif ($cleanMode === 'master_only') {
                $this->purgeMasterDataOnly($store, $groups);
            }

            if (in_array('brands', $groups, true)) {
                $result['brands'] = $this->importBrands($store, $businessType);
            }
            if (in_array('categories', $groups, true)) {
                $result['categories'] = $this->importCategories($store, $businessType);
            }
            if (in_array('shelves', $groups, true)) {
                $result['shelves'] = $this->importPresets($store, 'shelf_location', $this->shelves($businessType));
            }
            if (in_array('warranties', $groups, true)) {
                $result['warranties'] = $this->importPresets($store, 'warranty', $this->warranties($businessType));
            }
            if (in_array('return_policies', $groups, true)) {
                $result['return_policies'] = $this->importPresets($store, 'return_policy', $this->returnPolicies($businessType));
            }
            if (in_array('variant_presets', $groups, true)) {
                $result['variant_presets'] = $this->importVariantPresets($store, $businessType);
            }
        });

        return $result;
    }

    /**
     * Purge master presets only (brands, categories, presets, variant matrix).
     */
    public function purgeMasterDataOnly(Store $store, array $groups = []): void
    {
        $all = empty($groups);
        if ($all || in_array('brands', $groups, true)) {
            Brand::where('store_id', $store->id)->delete();
        }
        if ($all || in_array('categories', $groups, true)) {
            Category::where('store_id', $store->id)->delete();
        }
        if ($all || in_array('shelves', $groups, true)) {
            ProductMasterPreset::where('store_id', $store->id)->where('type', 'shelf_location')->delete();
        }
        if ($all || in_array('warranties', $groups, true)) {
            ProductMasterPreset::where('store_id', $store->id)->where('type', 'warranty')->delete();
        }
        if ($all || in_array('return_policies', $groups, true)) {
            ProductMasterPreset::where('store_id', $store->id)->where('type', 'return_policy')->delete();
        }
        if ($all || in_array('variant_presets', $groups, true)) {
            VariantPreset::where('store_id', $store->id)->delete();
        }
    }

    /**
     * Purge all old store test data (orders, sales, inventory, products) + master presets.
     */
    public function purgeFullStoreData(Store $store): void
    {
        if (class_exists(\App\Services\DemoBusinessScenarioService::class)) {
            app(\App\Services\DemoBusinessScenarioService::class)->cleanStoreData($store);
        }

        ProductMasterPreset::where('store_id', $store->id)->delete();
        VariantPreset::where('store_id', $store->id)->delete();
    }


    // =====================================================================
    // IMPORT HELPERS
    // =====================================================================

    private function importBrands(Store $store, string $businessType = 'tech'): int
    {
        $count = 0;
        foreach ($this->brands($businessType) as $b) {
            $slug = Str::slug($b['name']) . '-' . $store->id;
            Brand::updateOrCreate(
                ['store_id' => $store->id, 'name' => $b['name']],
                ['code' => $b['code'] ?? null, 'slug' => $slug]
            );
            $count++;
        }
        return $count;
    }

    private function importCategories(Store $store, string $businessType = 'tech'): int
    {
        $count = 0;
        foreach ($this->categoryTree($businessType) as $parentItem) {
            $parentSlug = Str::slug($parentItem['name']) . '-' . $store->id;
            $parent = Category::updateOrCreate(
                ['store_id' => $store->id, 'name' => $parentItem['name']],
                [
                    'code'       => $parentItem['code'],
                    'icon'       => $parentItem['icon'] ?? null,
                    'slug'       => $parentSlug,
                    'image_path' => $parentItem['image_path'] ?? null,
                    'parent_id'  => null,
                ]
            );
            $count++;
            foreach ($parentItem['subs'] ?? [] as $subItem) {
                $subSlug = Str::slug($subItem['name']) . '-' . $store->id . '-' . $parent->id;
                Category::updateOrCreate(
                    ['store_id' => $store->id, 'name' => $subItem['name'], 'parent_id' => $parent->id],
                    [
                        'code'       => $subItem['code'],
                        'icon'       => $subItem['icon'] ?? null,
                        'slug'       => $subSlug,
                        'image_path' => $subItem['image_path'] ?? null,
                    ]
                );
                $count++;
            }
        }
        return $count;
    }

    private function importPresets(Store $store, string $type, array $items): int
    {
        $count = 0;
        foreach ($items as $idx => $p) {
            ProductMasterPreset::updateOrCreate(
                ['store_id' => $store->id, 'type' => $type, 'name' => $p['name']],
                [
                    'code'       => $p['code'] ?? null,
                    'color_hex'  => $p['color_hex'] ?? null,
                    'content'    => $p['content'] ?? null,
                    'sort_order' => $idx,
                    'is_active'  => true,
                ]
            );
            $count++;
        }
        return $count;
    }

    private function importVariantPresets(Store $store, string $businessType = 'tech'): int
    {
        $count = 0;
        foreach ($this->variantPresets($businessType) as $sortOrder => $preset) {
            VariantPreset::updateOrCreate(
                ['store_id' => $store->id, 'name' => $preset['name']],
                [
                    'category_family' => $preset['category_family'],
                    'options'         => $preset['options'],
                    'sort_order'      => $sortOrder,
                ]
            );
            $count++;
        }
        return $count;
    }

    // =====================================================================
    // SEED DATA DEFINITIONS — Dispatcher by business type
    // =====================================================================

    private function brands(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionBrands(),
            'general' => $this->generalBrands(),
            default   => $this->techBrands(),
        };
    }

    private function categoryTree(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionCategoryTree(),
            'general' => $this->generalCategoryTree(),
            default   => $this->techCategoryTree(),
        };
    }

    private function shelves(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionShelves(),
            default   => $this->techShelves(),
        };
    }

    private function warranties(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionWarranties(),
            default   => $this->techWarranties(),
        };
    }

    private function returnPolicies(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionReturnPolicies(),
            default   => $this->techReturnPolicies(),
        };
    }

    private function variantPresets(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionVariantPresets(),
            default   => $this->techVariantPresets(),
        };
    }

    // =====================================================================
    // TECH SHOP — Myanmar tech/mobile/CCTV/computer shop context
    // =====================================================================

    private function techBrands(): array
    {
        return [
            // === 1. Major Smartphone Brands (ဖုန်းအမှတ်တံဆိပ်များ) ===
            ['name' => 'Apple / iPhone',      'code' => 'APL'],
            ['name' => 'Samsung',              'code' => 'SAM'],
            ['name' => 'Xiaomi / Redmi',       'code' => 'RM'],
            ['name' => 'Huawei',               'code' => 'HW'],
            ['name' => 'OPPO',                 'code' => 'OP'],
            ['name' => 'Vivo',                 'code' => 'VV'],
            ['name' => 'Realme',               'code' => 'RL'],
            ['name' => 'Infinix',              'code' => 'INF'],
            ['name' => 'Tecno',                'code' => 'TCN'],
            ['name' => 'Itel',                 'code' => 'ITEL'],
            ['name' => 'Nokia',                'code' => 'NOK'],
            ['name' => 'OnePlus',              'code' => 'OP1'],
            ['name' => 'Kenbo',                'code' => 'KENBO'],

            // === 2. Mobile Accessories & Gadget Brands (AlinnThit Mobile Shop Verified) ===
            ['name' => 'Hoco',                 'code' => 'HOCO'],
            ['name' => 'Remax',                'code' => 'REMAX'],
            ['name' => 'Joyroom',              'code' => 'JOYROOM'],
            ['name' => 'Baseus',               'code' => 'BASEUS'],
            ['name' => 'Anker',                'code' => 'ANKER'],
            ['name' => 'Ugreen',               'code' => 'UGREEN'],
            ['name' => 'Denmen',               'code' => 'DENMEN'],
            ['name' => 'Jeqane',               'code' => 'JEQANE'],
            ['name' => 'Pinjie',               'code' => 'PINJIE'],
            ['name' => 'Pixi',                 'code' => 'PIXI'],
            ['name' => 'RELKA',                'code' => 'RELKA'],
            ['name' => 'MANVE',                'code' => 'MANVE'],
            ['name' => 'Revo',                 'code' => 'REVO'],
            ['name' => 'U-Winn',               'code' => 'UWINN'],
            ['name' => 'Yk / YK Audio',        'code' => 'YK'],
            ['name' => 'Yd',                   'code' => 'YD'],
            ['name' => '5G Epoch',             'code' => '5G_EPOCH'],
            ['name' => '9D Glass',             'code' => '9D'],
            ['name' => 'Antclan',              'code' => 'ANTCLAN'],
            ['name' => 'Baivati',              'code' => 'BAIVATI'],
            ['name' => 'Dac',                  'code' => 'DAC'],
            ['name' => 'Eap',                  'code' => 'EAP'],
            ['name' => 'Etb',                  'code' => 'ETB'],
            ['name' => 'Fast',                 'code' => 'FAST'],
            ['name' => 'Hc',                   'code' => 'HC'],
            ['name' => 'Hd Plus',              'code' => 'HD_PLUS'],
            ['name' => 'Huang',                'code' => 'HUANG'],
            ['name' => 'Ipka',                 'code' => 'IPKA'],
            ['name' => 'Kmib',                 'code' => 'KMIB'],
            ['name' => 'Kt',                   'code' => 'KT'],
            ['name' => 'Rotary',               'code' => 'ROTARY'],
            ['name' => 'Smart Three',          'code' => 'SMART_THREE'],
            ['name' => 'Sport',                'code' => 'SPORT'],
            ['name' => 'Stereo',               'code' => 'STEREO'],
            ['name' => 'Super X',              'code' => 'SUPER_X'],
            ['name' => 'Tdk',                  'code' => 'TDK'],
            ['name' => 'Vdm',                  'code' => 'VDM'],
            ['name' => 'Wster',                'code' => 'WSTER'],
            ['name' => 'X Cable',              'code' => 'X_CABLE'],
            ['name' => 'Xinger',               'code' => 'XINGER'],
            ['name' => 'Xinude',               'code' => 'XINUDE'],
            ['name' => 'Rock',                 'code' => 'ROCK'],
            ['name' => 'WK Design',            'code' => 'WK'],
            ['name' => 'Kaku',                 'code' => 'KAKU'],
            ['name' => 'Bavinto',              'code' => 'BVT'],
            ['name' => 'Other / Generic',      'code' => 'OTHER'],

            // === 3. Memory & Storage Brands (မန်မိုရီနှင့် သိုလှောင်မှု) ===
            ['name' => 'Kingston',             'code' => 'KST'],
            ['name' => 'SanDisk',              'code' => 'SDK'],
            ['name' => 'Samsung Storage',      'code' => 'SAM_ST'],
            ['name' => 'Orico',                'code' => 'ORICO'],
            ['name' => 'Transcend',            'code' => 'TRS'],

            // === 4. CCTV & Security Brands (လုံခြုံရေးကင်မရာ) ===
            ['name' => 'Hikvision',            'code' => 'HIK'],
            ['name' => 'Dahua',                'code' => 'DAHUA'],
            ['name' => 'Imou',                 'code' => 'IMOU'],
            ['name' => 'TP-Link',              'code' => 'TPLINK'],
            ['name' => 'Mikrotik',             'code' => 'MKT'],
            ['name' => 'Ubiquiti',             'code' => 'UBQ'],

            // === 5. Computer & Laptop Brands (ကွန်ပျူတာနှင့် ဆက်စပ်) ===
            ['name' => 'Dell',                 'code' => 'DELL'],
            ['name' => 'Lenovo',               'code' => 'LNV'],
            ['name' => 'HP',                   'code' => 'HP'],
            ['name' => 'ASUS',                 'code' => 'ASUS'],
            ['name' => 'Acer',                 'code' => 'ACER'],

            // === 6. Digital, Telecom, Software & Service Brands ===
            ['name' => 'Apple ID / iTunes',    'code' => 'APL_ID'],
            ['name' => 'Google Play',          'code' => 'GGL_PLAY'],
            ['name' => 'Mobile Legends (Moonton)', 'code' => 'MLBB'],
            ['name' => 'PUBG Mobile',          'code' => 'PUBG'],
            ['name' => 'MPT Telecom',          'code' => 'MPT'],
            ['name' => 'ATOM Myanmar',         'code' => 'ATOM'],
            ['name' => 'Kaspersky Lab',        'code' => 'KASP'],
            ['name' => 'DataPOS Service Center','code' => 'SVC_CTR'],
            ['name' => 'Project Service',      'code' => 'PRJ_SVC'],
        ];
    }

    private function techCategoryTree(): array
    {
        return [
            [
                'name' => 'Smartphones & Tablets',  'code' => 'PHN', 'icon' => '📱',
                'subs' => [
                    ['name' => 'iOS / iPhone',       'code' => 'PHN_IOS', 'icon' => '🍎'],
                    ['name' => 'Android Phone', 'code' => 'PHN_AND', 'icon' => '🤖'],
                    ['name' => 'Tablet / iPad',        'code' => 'PHN_TAB', 'icon' => '📱'],
                    ['name' => 'Keypad Phone',      'code' => 'PHN_KPD', 'icon' => '📞'],
                    ['name' => 'Smart Watch',          'code' => 'PHN_WAT', 'icon' => '⌚'],
                ],
            ],
            [
                'name' => 'Cable & Charger',  'code' => 'CBCH', 'icon' => '🔌',
                'image_path' => 'categories/MF4ODlNndeKmXTgr9u7LzRAyFBaSqDZ29rQj8FyM.webp',
                'subs' => [
                    ['name' => 'Charging Cable',     'code' => 'CB',   'icon' => '🔌', 'image_path' => 'categories/I4CEBPaEb1ae6qyvErlf41FzsE4dRzt3Jc72iHc2.webp'],
                    ['name' => 'Charger Adapter',         'code' => 'CH',   'icon' => '⚡', 'image_path' => 'categories/pJ5pTKhHVmhXUjNc2RT2ucLjq76T7E7j17feMwm9.webp'],
                    ['name' => 'Charger Set', 'code' => 'CHS',  'icon' => '🔌', 'image_path' => 'categories/old15boefSMbUdtCkTbYqSO4muee4Jge7EBtlNPB.webp'],
                    ['name' => 'Car Charger',        'code' => 'CCH',  'icon' => '🚗', 'image_path' => 'categories/gydjEkXxerukrjBE2HZFJVCrQzexRu57km4SaZps.webp'],
                    ['name' => 'Wireless Charger',                 'code' => 'WLC',  'icon' => '📶'],
                    ['name' => 'Power Adapter',                    'code' => 'PADP', 'icon' => '🔌'],
                ],
            ],
            [
                'name' => 'Audio & Sound',  'code' => 'AUD', 'icon' => '🎧',
                'image_path' => 'categories/rH4Z5WRpYuUksNxhXPOrHQ3ZRMiZZmIkYHhjdQsb.webp',
                'subs' => [
                    ['name' => 'Wired Earphone',     'code' => 'EP',   'icon' => '🎧', 'image_path' => 'categories/MyLFwPrGzAlfuUMkmCNmQWlOufS0gSJ8VbFNLLz8.webp'],
                    ['name' => 'Bluetooth Earphone / TWS', 'code' => 'BEP',  'icon' => '📶', 'image_path' => 'categories/KAYk4HOqgIxJl034ON2yqdx5NaC00qHpsQ8TvZ45.webp'],
                    ['name' => 'Bluetooth Speaker',        'code' => 'SPK',  'icon' => '🔊', 'image_path' => 'categories/HlLsUa13MJ1idXJjvYzo6L0xaDX4x51ylrvYbaWO.webp'],
                    ['name' => 'Microphone',         'code' => 'Mic',  'icon' => '🎙️', 'image_path' => 'categories/lgwS7HZVajy0UQpsVf7Ph4m4pvehBQZ8BndBWm16.webp'],
                    ['name' => 'Wired Headset / Headphone',        'code' => 'WHS',  'icon' => '🎧'],
                ],
            ],
            [
                'name' => 'Power & Storage',  'code' => 'PWR', 'icon' => '🔋',
                'image_path' => 'categories/Q8haJ85U9TPhINqd25G530XGnra5E2WHKXhoNnTk.webp',
                'subs' => [
                    ['name' => 'Power Bank',     'code' => 'PB',  'icon' => '🔋', 'image_path' => 'categories/H9DSKBR0jJRLMYVt8Cn1kLGBXZuf2TvTOATiA4RD.webp'],
                    ['name' => 'Memory Card',   'code' => 'SD',  'icon' => '💾', 'image_path' => 'categories/xoJbPXzXGVPGGkBMeuBJQRwPWyx79anhpMeM4fjL.webp'],
                    ['name' => 'USB Flash Drive',            'code' => 'USB', 'icon' => '💾'],
                    ['name' => 'SSD / Hard Drive',           'code' => 'SSD', 'icon' => '💾'],
                    ['name' => 'USB Hub / Card Reader',      'code' => 'HUB', 'icon' => '🔌'],
                ],
            ],
            [
                'name' => 'Screen & LCD',  'code' => 'SCR', 'icon' => '📱',
                'subs' => [
                    ['name' => 'Touch LCD',    'code' => 'TL',     'icon' => '📱', 'image_path' => 'categories/6KFlGA2qhCqc9ISXl1Nnbu6wl3YEhMoecI8rzlsY.webp'],
                    ['name' => 'Original Touch LCD',               'code' => 'TL_ORG', 'icon' => '📱'],
                    ['name' => 'Touch Screen',          'code' => 'TS',     'icon' => '📱', 'image_path' => 'categories/HOAqeV2FAuAU9l1esOafPNxZBpVyo4k9tMaVWlvS.webp'],
                    ['name' => 'Front Glass',            'code' => 'GLS',    'icon' => '🛡️'],
                    ['name' => 'OCA Glass',                        'code' => 'GLS',    'icon' => '🛡️', 'image_path' => 'categories/yDkDp6QkaYrNEr0gtmVznzmpr2qtd9Abo3U88Frx.webp'],
                    ['name' => 'Screen Protector',       'code' => 'SG',     'icon' => '🛡️', 'image_path' => 'categories/iUKHo5ShXD5ZcKC2xPXcwCf5CNaxKLidfb1qrnyP.webp'],
                    ['name' => 'Privacy Filter',                   'code' => 'PVF',    'icon' => '🛡️'],
                ],
            ],
            [
                'name' => 'Phone Case & Cover',  'code' => 'CV', 'icon' => '🛡️',
                'image_path' => 'categories/Os1tuu7hDIrXUJQztc7pACD2WI7UXwZgU2VTaFNo.webp',
                'subs' => [
                    ['name' => 'Phone Cover / Case',           'code' => 'CV', 'icon' => '🛡️', 'image_path' => 'categories/M16Tk9it3VNuPel6uskFei2lS3nalqbMcFWQfBzs.webp'],
                    ['name' => 'Silicone Case',       'code' => 'SIL', 'icon' => '🛡️'],
                    ['name' => 'Clear Case',       'code' => 'CLR', 'icon' => '🛡️'],
                    ['name' => 'Leather Case',           'code' => 'LTH', 'icon' => '🛡️'],
                    ['name' => 'Bumper / Shockproof Case',       'code' => 'BMP', 'icon' => '🛡️'],
                    ['name' => 'Card Holder Case',               'code' => 'CDH', 'icon' => '💳'],
                    ['name' => 'Sticker',               'code' => 'STK', 'icon' => '🎨', 'image_path' => 'categories/ECw8TwNo9DPUr59kSEcEle9pmLrx8IdAogEWw1I9.webp'],
                    ['name' => 'Water Bag',          'code' => 'WTP', 'icon' => '💧'],
                ],
            ],
            [
                'name' => 'Battery',  'code' => 'BT', 'icon' => '🔋',
                'subs' => [
                    ['name' => 'Phone Battery', 'code' => 'BT', 'icon' => '🔋', 'image_path' => 'categories/yDSrXGv6HgX1UvdEgZhcS1vM2HWRw6K2GBlpG8jX.webp'],
                    ['name' => 'High Capacity Battery',      'code' => 'BT_HI',  'icon' => '🔋'],
                    ['name' => 'Standard Replacement Battery', 'code' => 'BT_STD', 'icon' => '🔋'],
                ],
            ],
            [
                'name' => 'Body & Back Cover',  'code' => 'BC', 'icon' => '📱',
                'image_path' => 'categories/YealuxjTGsKzHckTTCYb1wyZ7c5qjCxkDZfdbXp0.webp',
                'subs' => [
                    ['name' => 'Back Glass', 'code' => 'BC_GLS', 'icon' => '📱', 'image_path' => 'categories/GO5w6kVQmUeDuKVJvUnSuBeBaJYXguVU4jMJFuet.webp'],
                    ['name' => 'Back Cover',     'code' => 'BC',     'icon' => '📱'],
                    ['name' => 'Body Frame',          'code' => 'BD',     'icon' => '🗜️', 'image_path' => 'categories/i69bZAj3d9ptRlexO8KiPkehTe3tNETyraC3ZjRS.webp'],
                    ['name' => 'Mid Frame',                   'code' => 'MDF',    'icon' => '🗜️'],
                    ['name' => 'Housing Set',                 'code' => 'HS',    'icon' => '📱'],
                ],
            ],
            [
                'name' => 'Phone Spare Parts',  'code' => 'PRT', 'icon' => '🔧',
                'image_path' => 'categories/oUnSf1QNm4MqDW6UXai576svfniAvDKXI94oKRg5.webp',
                'subs' => [
                    ['name' => 'Charging Port', 'code' => 'USB FIX', 'icon' => '🔌', 'image_path' => 'categories/cbzVCEl5s0Hcqc4n1V6WiFVYjlFlyeVqrEKI7WDv.webp'],
                    ['name' => 'Power Switch / Flex',           'code' => 'Power Swift',  'icon' => '🔘', 'image_path' => 'categories/PQp2A4lKQb0o1bYEmAGaOM0MnPLEMcQeOK8b2hDm.webp'],
                    ['name' => 'Volume Button Flex',            'code' => 'VOLSW',  'icon' => '🔘'],
                    ['name' => 'Camera Module',                 'code' => 'LENS',   'icon' => '📷'],
                    ['name' => 'Ear Speaker',                   'code' => 'EARSPK', 'icon' => '🔊'],
                    ['name' => 'Loud Speaker',                  'code' => 'LDSPK',  'icon' => '🔊'],
                    ['name' => 'Vibrator Motor',                'code' => 'VIB',    'icon' => '🔧'],
                    ['name' => 'Flex Ribbon Cable',             'code' => 'FLEX',   'icon' => '🧰'],
                    ['name' => 'IC Chip / Board',               'code' => 'IC',     'icon' => '🔬'],
                    ['name' => 'Technician Tools & Supplies',   'code' => 'TOOL',   'icon' => '🧰'],
                    ['name' => 'Other Spare Parts',             'code' => 'SPARE',  'icon' => '🔧'],
                ],
            ],
            [
                'name' => 'Phone Stand & Holder',  'code' => 'HLD_GRP', 'icon' => '🧲',
                'image_path' => 'categories/ap9Aaj2mOmBxCErrz4YrckBAWLo4IoG5y40GnqqD.webp',
                'subs' => [
                    ['name' => 'Phone Holder / Stand', 'code' => 'HLD',    'icon' => '🧲', 'image_path' => 'categories/ap9Aaj2mOmBxCErrz4YrckBAWLo4IoG5y40GnqqD.webp'],
                    ['name' => 'Car Mount Holder',     'code' => 'CARHLD', 'icon' => '🚗'],
                    ['name' => 'Desk Stand / Tripod',  'code' => 'TRPD',   'icon' => '🧲'],
                    ['name' => 'Selfie Stick',         'code' => 'SLFI',   'icon' => '📸'],
                    ['name' => 'Ring Stand',           'code' => 'RNG2',   'icon' => '💍'],
                ],
            ],
            [
                'name' => 'CCTV & Security',  'code' => 'CCTV', 'icon' => '📹',
                'image_path' => 'categories/0WmiX4bfCLDOlyFJxsbSN7iqI3DOELp3T9KiXHYN.webp',
                'subs' => [
                    ['name' => 'CCTV Camera',          'code' => 'CAM',     'icon' => '📹', 'image_path' => 'categories/dtRipoSfsFqd0N14FA8VWnb37Al9iCsZuvFdVQAZ.webp'],
                    ['name' => 'CCTV Accessories', 'code' => 'CCTVACC', 'icon' => '🔌', 'image_path' => 'categories/0Fksd3cTp9AVHebL1sjUz1AOPnYUAjKbcxw4qoPM.webp'],
                    ['name' => 'CCTV IP Camera',       'code' => 'IPCAM',   'icon' => '📹'],
                    ['name' => 'CCTV Analog Camera',            'code' => 'ANACAM',  'icon' => '📹'],
                    ['name' => 'DVR / NVR Recorder',            'code' => 'DVR',     'icon' => '🖥️'],
                    ['name' => 'Hard Disk (CCTV/NAS)',          'code' => 'HDD',     'icon' => '💾'],
                    ['name' => 'CCTV Power Supply',             'code' => 'CCTVPWR', 'icon' => '⚡'],
                ],
            ],
            [
                'name' => 'Network & Connectivity',  'code' => 'NET', 'icon' => '🌐',
                'subs' => [
                    ['name' => 'WiFi Router',        'code' => 'ROUTER', 'icon' => '📡'],
                    ['name' => 'Network Switch',     'code' => 'SWITCH', 'icon' => '🌐'],
                    ['name' => 'Network Cable (UTP)', 'code' => 'UTP',   'icon' => '🔌'],
                    ['name' => 'SFP Module',         'code' => 'SFP',    'icon' => '🌐'],
                    ['name' => 'Access Point (AP)',  'code' => 'AP',     'icon' => '📡'],
                    ['name' => 'Fiber Cable',        'code' => 'FIBER',  'icon' => '🌐'],
                ],
            ],
            [
                'name' => 'Electronics & Gadgets',  'code' => 'ELEC', 'icon' => '💻',
                'image_path' => 'categories/QiXfdYRyeWut7nugNNr76PdscaKmB2RYRErjTgNh.webp',
                'subs' => [
                    ['name' => 'Mini Fan',         'code' => 'FAN',   'icon' => '💨', 'image_path' => 'categories/dfWcbjbmds0dkEIrZizb70e49URI736F2Ps6xJzu.webp'],
                    ['name' => 'Mouse',              'code' => 'Mouse',   'icon' => '🖱️', 'image_path' => 'categories/hMhHwoUUWWku89q6MtVv9rrzFD5GL7Uv8hwPhsOg.webp'],
                    ['name' => 'LED Light', 'code' => 'LED',   'icon' => '💡', 'image_path' => 'categories/q7ojp3SVmnxlXawNfyNgxVnJfVrq8bl6b80Cajym.webp'],
                    ['name' => 'Desktop PC / All-in-One',   'code' => 'PC',    'icon' => '🖥️'],
                    ['name' => 'Laptop / Notebook',         'code' => 'LAPTOP', 'icon' => '💻'],
                    ['name' => 'Monitor / Display', 'code' => 'MON', 'icon' => '🖥️'],
                    ['name' => 'Keyboard',        'code' => 'KBD',   'icon' => '⌨️'],
                    ['name' => 'Webcam',                    'code' => 'WCM',   'icon' => '📷'],
                    ['name' => 'USB Cable / Adapter',       'code' => 'USBAD', 'icon' => '🔌'],
                    ['name' => 'HDMI Display Cable',        'code' => 'HDMI',  'icon' => '🖥️'],
                ],
            ],
            [
                'name' => 'Service & Repair',  'code' => 'SVC', 'icon' => '🛠️',
                'subs' => [
                    ['name' => 'Screen Repair / LCD Service',     'code' => 'SVC_SCR',  'icon' => '🛠️'],
                    ['name' => 'Battery Replacement',           'code' => 'SVC_BT',   'icon' => '🔋'],
                    ['name' => 'Charging Port Repair',          'code' => 'SVC_CHG',  'icon' => '🔌'],
                    ['name' => 'Software / Flash / Update',     'code' => 'SVC_SW',   'icon' => '💻'],
                    ['name' => 'Water Damage Repair',           'code' => 'SVC_WD',   'icon' => '💧'],
                    ['name' => 'Back Glass Replacement',        'code' => 'SVC_BK',   'icon' => '🛡️'],
                    ['name' => 'CCTV Installation Service',     'code' => 'SVC_CCTV', 'icon' => '📹'],
                    ['name' => 'Network Setup Service',         'code' => 'SVC_NET',  'icon' => '🌐'],
                    ['name' => 'General Diagnostic',            'code' => 'SVC_GEN',  'icon' => '🔍'],
                ],
            ],
            [
                'name' => 'Digital & Gift Cards',  'code' => 'DIG', 'icon' => '💻',
                'subs' => [
                    ['name' => 'Apple ID / iTunes Gift Card',  'code' => 'DIG_APL', 'icon' => '🍎'],
                    ['name' => 'Google Play Gift Card',        'code' => 'DIG_GGL', 'icon' => '🎮'],
                    ['name' => 'MLBB Diamond',                 'code' => 'DIG_MLB', 'icon' => '💎'],
                    ['name' => 'PUBG UC',                      'code' => 'DIG_PBG', 'icon' => '🎮'],
                    ['name' => 'Free Fire Diamond',            'code' => 'DIG_FF',  'icon' => '🔥'],
                    ['name' => 'Mobile Top-Up / E-Pin',        'code' => 'DIG_TOP', 'icon' => '📱'],
                    ['name' => 'Steam Wallet Code',            'code' => 'DIG_STM', 'icon' => '🎮'],
                    ['name' => 'VPN / Software License',       'code' => 'DIG_VPN', 'icon' => '🔐'],
                ],
            ],
        ];
    }

    private function techShelves(): array
    {
        return [
            ['code' => 'CTR-A',       'name' => 'Counter Glass A',          'content' => 'Main counter glass showcase section A'],
            ['code' => 'CTR-B',       'name' => 'Counter Glass B',          'content' => 'Main counter glass showcase section B'],
            ['code' => 'CAB-A1',      'name' => 'Phone Cabinet A1',             'content' => 'High-value smartphone cabinet level 1'],
            ['code' => 'CAB-A2',      'name' => 'Phone Cabinet A2',             'content' => 'Smartphone display cabinet level 2'],
            ['code' => 'CAB-A3',      'name' => 'Phone Cabinet A3',             'content' => 'Mid-range phone cabinet level 3'],
            ['code' => 'CAB-A4',      'name' => 'Phone Cabinet A4',             'content' => 'Budget phone cabinet level 4'],
            ['code' => 'CAB-T1',      'name' => 'Tablet Showcase T1',             'content' => 'iPad & Tablet showcase cabinet'],
            ['code' => 'CAB-W1',      'name' => 'Watch Showcase W1',                 'content' => 'Smartwatch showcase cabinet'],
            ['code' => 'CAB-SD',      'name' => 'Storage Cabinet SD',              'content' => 'Secure cabinet for SD & USB cards'],
            ['code' => 'A-01',        'name' => 'Wall Shelf A1 — Top Level',           'content' => 'Wall-mounted display shelf level 1'],
            ['code' => 'A-02',        'name' => 'Wall Shelf A2 — Mid Level',           'content' => 'Wall-mounted display shelf level 2'],
            ['code' => 'A-03',        'name' => 'Wall Shelf A3 — Bottom Level',          'content' => 'Wall-mounted display shelf level 3'],
            ['code' => 'B-01',        'name' => 'Side Shelf B1 — Top Level',           'content' => 'Side display rack level 1'],
            ['code' => 'B-02',        'name' => 'Side Shelf B2 — Bottom Level',          'content' => 'Side display rack level 2'],
            ['code' => 'HOOK-R',      'name' => 'Hook Rail Right',            'content' => 'Right-side hanging peg hook rail'],
            ['code' => 'HOOK-L',      'name' => 'Hook Rail Left',            'content' => 'Left-side hanging peg hook rail'],
            ['code' => 'RACK-CHG',    'name' => 'Charger Rack',                  'content' => 'Chargers and fast power accessories rack'],
            ['code' => 'RACK-CBL',    'name' => 'Cable Rack',                       'content' => 'Data & charging cables peg rack'],
            ['code' => 'RACK-PB',     'name' => 'Power Bank Rack',                'content' => 'Power bank display rack'],
            ['code' => 'RACK-AUD',    'name' => 'Audio Rack',                     'content' => 'Headphone, TWS, and speaker rack'],
            ['code' => 'RACK-GLS',    'name' => 'Glass & Screen Rack',             'content' => 'Tempered glass and film display rack'],
            ['code' => 'RACK-CASE',   'name' => 'Case & Cover Rack',                 'content' => 'Phone cases display rack'],
            ['code' => 'RACK-STND',   'name' => 'Holder & Stand Rack',          'content' => 'Car mounts and desk stands display rack'],
            ['code' => 'CAB-01',      'name' => 'Cabinet Store A',              'content' => 'Storage cabinet for overstock / offline items'],
            ['code' => 'RP-RACK',     'name' => 'Repair Bench Rack',          'content' => 'Service bench spare parts rack'],
            ['code' => 'SPARE-LCD',   'name' => 'Spare LCD Cabinet',      'content' => 'Technician LCD screen storage cabinet'],
            ['code' => 'SPARE-BAT',   'name' => 'Spare Battery Bin',            'content' => 'Technician phone battery storage bin'],
            ['code' => 'SPARE-PRT',   'name' => 'Small Parts Drawer',          'content' => 'Technician flex cables and charging port parts'],
            ['code' => 'SPARE-TOOL',  'name' => 'Repair Tools Shelf',        'content' => 'Technician repair kits and precision screwdrivers'],
            ['code' => 'SERVICE-DESK','name' => 'Service & Intake Desk',   'content' => 'Service center customer receiving desk'],
            ['code' => 'DIGITAL-VAULT','name'=> 'Digital Code Vault',         'content' => 'Instant electronic delivery vault'],
            ['code' => 'WH-MAIN',     'name' => 'Warehouse Rack — Main',  'content' => 'Main warehouse bulk storage rack'],
            ['code' => 'WH-B',        'name' => 'Warehouse Bin B',         'content' => 'Secondary bin/area in warehouse'],
            ['code' => 'ONLINE',      'name' => 'Online Stock Only',               'content' => 'Virtual stock for e-commerce listings only'],
        ];
    }

    private function techWarranties(): array
    {
        return [
            ['code' => null, 'name' => 'No Warranty — Check on Purchase',
                'content' => 'ပစ္စည်းဝယ်ယူချိန်တွင် ချက်ချင်း စစ်ဆေးပြီး ယူဆောင်ရမည်ဖြစ်ပြီး အာမခံ မပါရှိပါ။'],
            ['code' => null, 'name' => '1 Year Official Apple Warranty',
                'content' => 'Apple တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '1 Year Official Samsung Myanmar Warranty',
                'content' => 'Samsung Myanmar တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '1 Year Official MI Warranty',
                'content' => 'Xiaomi Myanmar တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '1 Year Official Vivo Warranty',
                'content' => 'Vivo Myanmar တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '1 Year Official OPPO Warranty',
                'content' => 'OPPO Myanmar တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '18 Months Official Anker Warranty',
                'content' => 'Anker တရားဝင် ၁၈ လ အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '6 Months Replacement Warranty',
                'content' => '၆ လ အတွင်း ပစ္စည်းချို့ယွင်းပါက အသစ်လဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => '3 Months Replacement Warranty',
                'content' => '၃ လ အတွင်း ပစ္စည်းချို့ယွင်းပါက အသစ်လဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => '3 Months Workmanship Warranty',
                'content' => 'စက်ပြင်ဆင်မှုအတွက် ၃ လ လက်ခအာမခံ ပေးပါသည်။'],
            ['code' => null, 'name' => 'Touch Screen Test Before Install Warranty',
                'content' => 'မတပ်ဆင်မီ စမ်းသပ်စစ်ဆေးပြီးမှ အာမခံ အကျုံးဝင်ပါသည်။'],
            ['code' => null, 'name' => '6 Months Battery Health Warranty',
                'content' => '၆ လ အတွင်း Battery ကျန်းမာရေးအတွက် အာမခံ ပေးပါသည်။'],
            ['code' => null, 'name' => 'Lifetime Official Warranty',
                'content' => 'ထုတ်လုပ်သူ သက်တမ်းတစ်လျှောက် တရားဝင် အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '5 Years Official Warranty',
                'content' => 'တရားဝင် ၅ နှစ် အာမခံ ပါဝင်သည်။'],
            ['code' => null, 'name' => '100% Genuine Digital Code Guarantee',
                'content' => '၁၀၀% စစ်မှန်သော တရားဝင် ဒစ်ဂျစ်တယ်ကုတ် ဖြစ်ကြောင်း အာမခံပါသည်။'],
            ['code' => null, 'name' => '3 Days Testing Warranty',
                'content' => 'ဝယ်ယူပြီး ၃ ရက်အတွင်း စက်ချို့ယွင်းချက် (Factory Defect) ဖြစ်ပါက စစ်ဆေးပြင်ဆင်ပေးပါသည်။'],
            ['code' => null, 'name' => '7 Days Testing Warranty',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း Factory Defect ဖြစ်ပေါ်ပါက အသစ်လဲလှယ်ပေးပါသည်။ ရေဝင်ခြင်း၊ ပြုတ်ကျကွဲရှခြင်း အကျုံးမဝင်ပါ။'],
            ['code' => null, 'name' => '1 Month Free Service',
                'content' => 'ဝယ်ယူပြီး ၁ လအတွင်း ပစ္စည်းစစ်ဆေးပြင်ဆင်ခ — လက်ခ အခမဲ့ ဝန်ဆောင်မှုပေးပါသည်။'],
            ['code' => null, 'name' => '3 Months Official Warranty',
                'content' => '၃ လ အာမခံ ပါဝင်သည်။ စက်ပစ္စည်း ချို့ယွင်းမှုများအတွက် အခမဲ့ စစ်ဆေး ပြုပြင် လဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => '6 Months Service Warranty',
                'content' => '၆ လ အတွင်း ဆိုင်အာမခံ ဖြင့် စစ်ဆေးပြင်ဆင်ခွင့် ရပါသည်။'],
            ['code' => null, 'name' => '6 Months Official Warranty',
                'content' => 'ကုမ္ပဏီ တရားဝင် ၆ လ အာမခံ ပါဝင်သည်။ Warranty Card ပြသ၍ ဝင်ရောက် စစ်ဆေးနိုင်ပါသည်။'],
            ['code' => null, 'name' => '1 Year Official Warranty',
                'content' => 'ကုမ္ပဏီ တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။ Warranty Card သိမ်းဆည်းထားပါ။'],
            ['code' => null, 'name' => '2 Years Brand Warranty',
                'content' => 'Manufacturer ၂ နှစ် အာမခံ ပါဝင်သည်။ Global/Local Warranty စစ်ဆေးပါ။'],
            ['code' => null, 'name' => 'Screen Protector — No Warranty',
                'content' => 'မျက်နှာပြင် ကာမှန်ချပ် Tempered Glass တပ်ဆင်ပြီးသည်နှင့် ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'CCTV 2 Years Warranty',
                'content' => 'CCTV ကင်မရာနှင့် DVR/NVR တွင် ၂ နှစ် ကုမ္ပဏီ အာမခံ ပါဝင်သည်။'],
        ];
    }

    private function techReturnPolicies(): array
    {
        return [
            ['code' => null, 'name' => 'No Return / No Exchange',
                'content' => 'ဝယ်ယူပြီးသည့်နောက် ပြန်လဲ/ပြန်လည်ရောင်းချ မခွင့်ပြုပါ။ ဝယ်ချင်မှ ဝယ်ပါ။'],
            ['code' => null, 'name' => '7 Days Defect Exchange — Box Intact',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ဘူးခွံ၊ ဘားကုဒ်နှင့် ဆက်စပ်ပစ္စည်းများ အကောင်းပကတိ ရှိပါက တန်ဖိုးတူ ပစ္စည်းနှင့် လဲနိုင်ပါသည်။'],
            ['code' => null, 'name' => 'Defective Exchange within Warranty',
                'content' => 'အာမခံသက်တမ်းအတွင်း စက်ချို့ယွင်းပါက စစ်ဆေးလဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => 'Defective Exchange within 7 Days',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ချို့ယွင်းချက်ရှိပါက အသစ်လဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => 'Spare Parts — Test Before Purchase',
                'content' => 'ဖုန်းအပိုပစ္စည်းများကို ဝယ်ယူချိန်တွင် ချက်ချင်းစမ်းသပ်ပြီး ယူဆောင်ရမည်ဖြစ်ပြီး ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Service Satisfaction Guaranteed',
                'content' => 'ပြုပြင်ရေး ဝန်ဆောင်မှု စိတ်တိုင်းကျစေရန် တာဝန်ယူပေးပါသည်။'],
            ['code' => null, 'name' => 'Non-refundable once code is redeemed',
                'content' => 'ဒစ်ဂျစ်တယ်ကုတ်ကို ပေးပို့ပြီးပါက သို့မဟုတ် Redeem ပြုလုပ်ပြီးပါက ငွေပြန်မအမ်းပါ။'],
            ['code' => null, 'name' => 'Direct in-game credit - Final Sale',
                'content' => 'ဂိမ်းအကောင့်ထဲသို့ တိုက်ရိုက်ငွေဖြည့်ပြီးပါက ပြန်လည်ပြင်ဆင်ခွင့်မရှိပါ။'],
            ['code' => null, 'name' => '3 Days Factory Defect Exchange',
                'content' => 'ဝယ်ယူပြီး ၃ ရက်အတွင်း စက်ချို့ယွင်းချက် (Factory Defect) ဖြစ်ပါက တန်ဖိုးတူ ပစ္စည်းနှင့် လဲလှယ်ပေးပါသည်။ ရေဝင်/ကွဲ/ပြုတ် မပါ။'],
            ['code' => null, 'name' => '7 Days Box-Condition Exchange',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ဘူးခွံ၊ ဘားကုဒ်နှင့် ဆက်စပ်ပစ္စည်းများ အကောင်းပကတိ ရှိပါက တန်ဖိုးတူ ပစ္စည်းနှင့် လဲနိုင်ပါသည်။'],
            ['code' => null, 'name' => 'Touch LCD / Screen — Test Before Purchase',
                'content' => 'Touch LCD နှင့် မှန်ချပ်များကို ဖုန်းတွင် ကော်မကပ်မီ ပြင်ပမှ ကြိုးထိုး စမ်းသပ်ပေးရပါမည်။ ကော်ကပ်ပြီး/ဖလင်ခွာပြီး/ဘားကုဒ်ပျက်ပါက ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Accessories — Defective Exchange Only',
                'content' => 'ကြိုး၊ အားသွင်းခေါင်း၊ Case/Cover စသည့် Accessories များ ချို့ယွင်းပါက ဘောက်ချာပြသ၍ စစ်ဆေးပြီး အသစ်လဲလှယ်ပေးပါသည်။ Defect မဟုတ်ပါက ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Spare Parts — No Return',
                'content' => 'Spare Part / IC / Flex / Battery များကို ဝယ်ယူချိန်တွင် စစ်ဆေးပြီး ယူဆောင်ရမည်ဖြစ်ပြီး ပြည်တင်ပြီးနောက် ပြန်မခွင့်ပြုပါ။'],
            ['code' => null, 'name' => 'Service — Fee Charged Regardless of Outcome',
                'content' => 'စစ်ဆေး/ပြင်ဆင်ပြီးသည့်နောက် ပြင်မရ/ဆက်မပြင်ဆိုပါက စစ်ဆေးခ (Diagnostic Fee) ကောက်ခံပါသည်။'],
            ['code' => null, 'name' => 'CCTV — Installation No Refund',
                'content' => 'CCTV တပ်ဆင်မှု ပြီးစီးပြီးနောက် ငွေ ပြန်မပေးပါ။ ကင်မရာ/DVR ချို့ယွင်းပါက Warranty ဖြင့် ပြန်စစ်ဆေးပေးပါသည်။'],
            ['code' => null, 'name' => 'Digital Code — No Refund Once Delivered',
                'content' => 'Gift Card / Voucher Code / Top-up ပေးပို့ပြီးသည့်နောက် ငွေပြန်မပေးပါ။ ပြည်တင်မပြုမီ စစ်ဆေးပါ။'],
        ];
    }

    private function techVariantPresets(): array
    {
        return [
            [
                'name' => 'Connector Types',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => 'Type-C',             'sku_suffix' => 'TC',    'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => 'Micro USB',           'sku_suffix' => 'MC',    'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => 'Lightning / iPhone',  'sku_suffix' => 'IP',    'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '3.5mm Aux',           'sku_suffix' => '3.5MM', 'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '3-in-1 Multi',        'sku_suffix' => '3IN1',  'retail_price_adjustment' => 1500, 'wholesale_price_adjustment' => 1000, 'stock_status' => 'in_stock'],
                    ['name' => 'OTG Adapter',         'sku_suffix' => 'OTG',   'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Colors',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => 'Black',          'sku_suffix' => 'BLK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'White',           'sku_suffix' => 'WHT', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Blue',            'sku_suffix' => 'BLU', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Red',              'sku_suffix' => 'RED', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Gold',             'sku_suffix' => 'GLD', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Silver',            'sku_suffix' => 'SLV', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Gray',          'sku_suffix' => 'GRY', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Purple',          'sku_suffix' => 'PUR', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Green',          'sku_suffix' => 'GRN', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Pink',        'sku_suffix' => 'PNK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Rose Gold', 'sku_suffix' => 'RGD', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Power Bank Capacity',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => '10000mAh', 'sku_suffix' => '10K',   'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '20000mAh', 'sku_suffix' => '20K',   'retail_price_adjustment' => 12000, 'wholesale_price_adjustment' => 9000, 'stock_status' => 'in_stock'],
                    ['name' => '30000mAh', 'sku_suffix' => '30K',   'retail_price_adjustment' => 25000, 'wholesale_price_adjustment' => 20000, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Memory Storage Size (SD/USB/SSD)',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => '32GB',  'sku_suffix' => '32G',  'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,     'stock_status' => 'in_stock'],
                    ['name' => '64GB',  'sku_suffix' => '64G',  'retail_price_adjustment' => 6000,  'wholesale_price_adjustment' => 4500,  'stock_status' => 'in_stock'],
                    ['name' => '128GB', 'sku_suffix' => '128G', 'retail_price_adjustment' => 15000, 'wholesale_price_adjustment' => 12000, 'stock_status' => 'in_stock'],
                    ['name' => '256GB', 'sku_suffix' => '256G', 'retail_price_adjustment' => 35000, 'wholesale_price_adjustment' => 28000, 'stock_status' => 'in_stock'],
                    ['name' => '512GB', 'sku_suffix' => '512G', 'retail_price_adjustment' => 70000, 'wholesale_price_adjustment' => 55000, 'stock_status' => 'in_stock'],
                    ['name' => '1TB',   'sku_suffix' => '1TB',  'retail_price_adjustment' => 140000,'wholesale_price_adjustment' => 110000,'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Charging Speed / Wattage',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => '18W Normal',        'sku_suffix' => '18W',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '20W Fast Charge',   'sku_suffix' => '20W',  'retail_price_adjustment' => 4000, 'wholesale_price_adjustment' => 3000, 'stock_status' => 'in_stock'],
                    ['name' => '33W Super Charge',  'sku_suffix' => '33W',  'retail_price_adjustment' => 8000, 'wholesale_price_adjustment' => 6000, 'stock_status' => 'in_stock'],
                    ['name' => '65W GaN Fast',      'sku_suffix' => '65W',  'retail_price_adjustment' => 18000,'wholesale_price_adjustment' => 13000,'stock_status' => 'in_stock'],
                    ['name' => '100W PD Ultra Fast', 'sku_suffix' => '100W','retail_price_adjustment' => 28000,'wholesale_price_adjustment' => 20000,'stock_status' => 'in_stock'],
                    ['name' => '120W Hyper Charge', 'sku_suffix' => '120W', 'retail_price_adjustment' => 35000,'wholesale_price_adjustment' => 26000,'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Quality Grade',
                'category_family' => 'mobile',
                'options' => [
                    ['name' => 'Original',  'sku_suffix' => 'ORG', 'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,     'stock_status' => 'in_stock'],
                    ['name' => 'AAA+ Quality',            'sku_suffix' => 'AAA', 'retail_price_adjustment' => -8000, 'wholesale_price_adjustment' => -5500, 'stock_status' => 'in_stock'],
                    ['name' => 'AA Quality',              'sku_suffix' => 'AA',  'retail_price_adjustment' => -15000,'wholesale_price_adjustment' => -10000,'stock_status' => 'in_stock'],
                    ['name' => 'MA Quality',      'sku_suffix' => 'MA',  'retail_price_adjustment' => -22000,'wholesale_price_adjustment' => -15000,'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'CCTV Resolution',
                'category_family' => 'cctv',
                'options' => [
                    ['name' => '2MP / 1080p HD',   'sku_suffix' => '2MP',   'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '4MP / 2.5K',       'sku_suffix' => '4MP',   'retail_price_adjustment' => 20000,'wholesale_price_adjustment' => 15000,'stock_status' => 'in_stock'],
                    ['name' => '5MP / 3K',         'sku_suffix' => '5MP',   'retail_price_adjustment' => 35000,'wholesale_price_adjustment' => 25000,'stock_status' => 'in_stock'],
                    ['name' => '8MP / 4K Ultra HD','sku_suffix' => '8MP',   'retail_price_adjustment' => 60000,'wholesale_price_adjustment' => 45000,'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Phone iPhone Storage (iPhone Storage)',
                'category_family' => 'mobile',
                'options' => [
                    ['name' => '128GB',  'sku_suffix' => '128G', 'retail_price_adjustment' => 0,       'wholesale_price_adjustment' => 0,       'stock_status' => 'in_stock'],
                    ['name' => '256GB',  'sku_suffix' => '256G', 'retail_price_adjustment' => 130000,  'wholesale_price_adjustment' => 100000,  'stock_status' => 'in_stock'],
                    ['name' => '512GB',  'sku_suffix' => '512G', 'retail_price_adjustment' => 280000,  'wholesale_price_adjustment' => 220000,  'stock_status' => 'in_stock'],
                    ['name' => '1TB',    'sku_suffix' => '1TB',  'retail_price_adjustment' => 450000,  'wholesale_price_adjustment' => 360000,  'stock_status' => 'in_stock'],
                ],
            ],
        ];
    }

    // =====================================================================
    // FASHION SHOP — Myanmar fashion/clothing/accessories context
    // =====================================================================

    private function fashionBrands(): array
    {
        return [
            // === International Fashion ===
            ['name' => 'Zara',               'code' => 'ZARA'],
            ['name' => 'H&M',                'code' => 'HM'],
            ['name' => 'Uniqlo',             'code' => 'UNQ'],
            ['name' => 'Penshoppe',          'code' => 'PEN'],
            ['name' => 'Giordano',           'code' => 'GDN'],
            ['name' => 'Esprit',             'code' => 'ESP'],
            ['name' => 'Cotton On',          'code' => 'CON'],
            // === Sports ===
            ['name' => 'Nike',               'code' => 'NIKE'],
            ['name' => 'Adidas',             'code' => 'ADI'],
            ['name' => 'Puma',               'code' => 'PUMA'],
            ['name' => 'Vans',               'code' => 'VANS'],
            ['name' => 'Converse',           'code' => 'CONV'],
            ['name' => 'New Balance',        'code' => 'NB'],
            ['name' => 'Skechers',           'code' => 'SKC'],
            // === Footwear ===
            ['name' => 'Bata',               'code' => 'BATA'],
            ['name' => 'Charles & Keith',    'code' => 'CK'],
            ['name' => 'Pedro',              'code' => 'PDR'],
            // === Bags / Accessories ===
            ['name' => 'Fossil',             'code' => 'FSL'],
            ['name' => 'Guess',              'code' => 'GUESS'],
            ['name' => 'Pandora',            'code' => 'PAN'],
            // === Myanmar Local Brands ===
            ['name' => 'Nayone Collection',  'code' => 'NYC'],
            ['name' => 'Shwe Man Fashion',   'code' => 'SMF'],
            ['name' => 'Royal Myanmar',      'code' => 'RMM'],
            ['name' => 'EverGreen',          'code' => 'EVG'],
            ['name' => 'Yangon Couture',     'code' => 'YGC'],
            // === Tailoring, Fabric & Sewing Notions ===
            ['name' => 'KL Custom Tailor',   'code' => 'KLTLR'],
            ['name' => 'Shwe Zin',   'code' => 'SZIN'],
            ['name' => 'YKK',                'code' => 'YKK'],
            ['name' => 'Brother / Juki',     'code' => 'BJUK'],
            ['name' => 'Flying Wheel', 'code' => 'FLW'],
            ['name' => 'Singer',             'code' => 'SNG'],
            ['name' => 'Organ Needles',      'code' => 'ORGN'],
            ['name' => 'Moon Thread',        'code' => 'MOON'],
            // === Kids / Innerwear ===
            ["name" => "Carter's",           'code' => 'CRT'],
            ['name' => 'Wacoal',             'code' => 'WCL'],
            ['name' => 'Jockey',             'code' => 'JCK'],
        ];
    }

    private function fashionCategoryTree(): array
    {
        return [
            [
                'name' => 'Tailoring Service', 'code' => 'SVC_TLR', 'icon' => '✂️',
                'subs' => [
                    ['name' => "Women's Dressmaking", 'code' => 'TLR_WD', 'icon' => '👗'],
                    ['name' => "Men's Tailoring",         'code' => 'TLR_MT', 'icon' => '👔'],
                    ['name' => 'Bridal & Formal Design',          'code' => 'TLR_BR', 'icon' => '👰'],
                    ['name' => 'Alteration & Hemming',      'code' => 'TLR_AL', 'icon' => '✂️'],
                    ['name' => 'Custom Embroidery',       'code' => 'TLR_EM', 'icon' => '🪡'],
                ],
            ],
            [
                'name' => 'Fabrics & Textiles', 'code' => 'FAB', 'icon' => '🧵',
                'subs' => [
                    ['name' => 'Silk & Satin',             'code' => 'FAB_SLK', 'icon' => '👘'],
                    ['name' => 'Cotton & Linen',         'code' => 'FAB_CTN', 'icon' => '🧵'],
                    ['name' => 'Denim & Twill',             'code' => 'FAB_DNM', 'icon' => '👖'],
                    ['name' => 'Chiffon & Georgette',    'code' => 'FAB_CFN', 'icon' => '🥻'],
                    ['name' => 'Lace & Brocade',            'code' => 'FAB_LAC', 'icon' => '✨'],
                    ['name' => 'Traditional Fabric',         'code' => 'FAB_TRD', 'icon' => '🎎'],
                ],
            ],
            [
                'name' => 'Sewing Accessories', 'code' => 'NOT', 'icon' => '🪡',
                'subs' => [
                    ['name' => 'Sewing Threads',                 'code' => 'NOT_THR', 'icon' => '🧵'],
                    ['name' => 'Zippers & Fasteners',        'code' => 'NOT_ZIP', 'icon' => '🤐'],
                    ['name' => 'Buttons & Buckles',        'code' => 'NOT_BTN', 'icon' => '🔘'],
                    ['name' => 'Machine Needles & Parts', 'code' => 'NOT_NDL', 'icon' => '🪡'],
                    ['name' => 'Tailor Tools & Scissors',       'code' => 'NOT_TOL', 'icon' => '✂️'],
                    ['name' => 'Interlining & Elastic', 'code' => 'NOT_INT', 'icon' => '📦'],
                    ['name' => 'Sewing Machine Oil',                    'code' => 'NOT_OIL', 'icon' => '🛢️'],
                ],
            ],
            [
                'name' => 'Clothing', 'code' => 'CLO', 'icon' => '👗',
                'subs' => [
                    ['name' => "Women's Top",    'code' => 'WT',   'icon' => '👚'],
                    ['name' => "Women's Bottom",  'code' => 'WB',   'icon' => '👖'],
                    ['name' => "Men's Top",        'code' => 'MT',   'icon' => '👔'],
                    ['name' => "Men's Bottom",     'code' => 'MB',   'icon' => '👖'],
                    ['name' => 'Dress',                         'code' => 'DRS',  'icon' => '👗'],
                    ['name' => 'Jacket / Coat',           'code' => 'JKT',  'icon' => '🧥'],
                    ['name' => 'Traditional Wear',           'code' => 'TRAD', 'icon' => '🎎'],
                    ['name' => 'Sportswear',                'code' => 'SPT',  'icon' => '🏃'],
                    ['name' => "Kids Clothing",               'code' => 'KID',  'icon' => '👶'],
                ],
            ],
            [
                'name' => 'Footwear', 'code' => 'FTW', 'icon' => '👟',
                'subs' => [
                    ['name' => "Women's Shoes",  'code' => 'WS',   'icon' => '👠'],
                    ['name' => "Men's Shoes",      'code' => 'MS',   'icon' => '👞'],
                    ['name' => 'Sneakers',               'code' => 'SNK',  'icon' => '👟'],
                    ['name' => 'Sandals / Slippers',     'code' => 'SAN',  'icon' => '🩴'],
                    ['name' => 'Heels',                 'code' => 'HEL',  'icon' => '👠'],
                    ['name' => 'Boots',                'code' => 'BOT',  'icon' => '👢'],
                    ['name' => "Kids Shoes",            'code' => 'KS',   'icon' => '👟'],
                ],
            ],
            [
                'name' => 'Bags', 'code' => 'BAG', 'icon' => '👜',
                'subs' => [
                    ['name' => 'Handbag / Shoulder Bag', 'code' => 'HB',  'icon' => '👜'],
                    ['name' => 'Backpack',                   'code' => 'BP',  'icon' => '🎒'],
                    ['name' => 'Clutch / Evening Bag',                         'code' => 'CLT', 'icon' => '👜'],
                    ['name' => 'Tote Bag',                        'code' => 'TOT', 'icon' => '🛍️'],
                    ['name' => 'Wallet / Purse',                'code' => 'WLT', 'icon' => '👛'],
                    ['name' => 'Travel / Luggage',                 'code' => 'TRV', 'icon' => '🧳'],
                ],
            ],
            [
                'name' => 'Jewelry & Accessories', 'code' => 'JWL', 'icon' => '💍',
                'subs' => [
                    ['name' => 'Necklace',           'code' => 'NCK',  'icon' => '📿'],
                    ['name' => 'Earring',             'code' => 'EAR',  'icon' => '💎'],
                    ['name' => 'Bracelet / Bangle', 'code' => 'BRC',  'icon' => '💍'],
                    ['name' => 'Ring',               'code' => 'RNG',  'icon' => '💍'],
                    ['name' => 'Watch',                 'code' => 'WTC',  'icon' => '⌚'],
                    ['name' => 'Sunglasses',   'code' => 'SGL',  'icon' => '🕶️'],
                    ['name' => 'Hat / Cap',          'code' => 'HAT',  'icon' => '🧢'],
                    ['name' => 'Scarf / Stole',                'code' => 'SCF',  'icon' => '🧣'],
                    ['name' => 'Hair Accessories',     'code' => 'HAIR', 'icon' => '💇'],
                    ['name' => 'Belt',                'code' => 'BLT',  'icon' => '🔗'],
                ],
            ],
            [
                'name' => 'Innerwear / Lingerie', 'code' => 'INN', 'icon' => '🩱',
                'subs' => [
                    ['name' => "Women's Innerwear", 'code' => 'WIW', 'icon' => '🩱'],
                    ['name' => "Men's Innerwear",    'code' => 'MIW', 'icon' => '🩳'],
                    ['name' => 'Sleepwear / Pajamas',            'code' => 'SLW', 'icon' => '😴'],
                    ['name' => "Kids Innerwear",          'code' => 'KIW', 'icon' => '👶'],
                ],
            ],
            [
                'name' => 'Seasonal / Collection', 'code' => 'SEA', 'icon' => '🌸',
                'subs' => [
                    ['name' => 'New Year Collection',       'code' => 'NY',   'icon' => '🎊'],
                    ['name' => 'Summer Collection',          'code' => 'SUM',  'icon' => '☀️'],
                    ['name' => 'Thingyan / Water Festival',  'code' => 'THG',  'icon' => '💦'],
                    ['name' => 'Wedding / Formal',            'code' => 'WED',  'icon' => '💒'],
                    ['name' => 'Sale / Clearance',          'code' => 'SALE', 'icon' => '🏷️'],
                ],
            ],
        ];
    }

    private function fashionShelves(): array
    {
        return [
            ['code' => 'FA',     'name' => "Zone A — Women's Clothing",     'content' => 'Main floor zone A — womens clothing display'],
            ['code' => 'FB',     'name' => "Zone B — Men's Clothing",        'content' => 'Main floor zone B — mens clothing display'],
            ['code' => 'FC',     'name' => 'Zone C — Kids Wear',                    'content' => 'Zone C — kids and baby clothing'],
            ['code' => 'FD',     'name' => 'Zone D — Footwear',                       'content' => 'Zone D — footwear display racks'],
            ['code' => 'FE',     'name' => 'Zone E — Bags & Accessories',        'content' => 'Zone E — handbags, accessories, jewelry'],
            ['code' => 'FHK',    'name' => 'Clothing Rail',                           'content' => 'Hanging garment rail / clothes hanger rack'],
            ['code' => 'FWND',   'name' => 'Window Display',                           'content' => 'Front window showcase / mannequin display'],
            ['code' => 'FMNN',   'name' => 'Mannequin Stand',                              'content' => 'In-store mannequin display stand'],
            ['code' => 'FSALE',  'name' => 'Sale Rack',                            'content' => 'Clearance and sale items hanging rack'],
            ['code' => 'FBOX',   'name' => 'Footwear Display Box',                 'content' => 'Footwear display shelf / shoe box rack'],
            ['code' => 'STK1',   'name' => 'Stockroom 1 — Regular Sizes', 'content' => 'Stockroom shelf for regular sizes S-XL'],
            ['code' => 'STK2',   'name' => 'Stockroom 2 — XL / Plus Sizes',  'content' => 'Stockroom for XL-XXXL plus size stock'],
            ['code' => 'STK3',   'name' => 'Stockroom 3 — New Arrivals',          'content' => 'Stockroom for new arrivals awaiting display'],
            ['code' => 'ONLINE', 'name' => 'Online Stock Only',                      'content' => 'Virtual stock — online listings only'],
        ];
    }

    private function fashionWarranties(): array
    {
        return [
            ['code' => null, 'name' => 'No Warranty — Check Before Purchase',
                'content' => 'ဝတ်စောင်းများ ဝယ်ယူချိန်တွင် စစ်ဆေးပြီး ယူဆောင်ရမည်ဖြစ်ပြီး အာမခံ မပါရှိပါ။'],
            ['code' => null, 'name' => '7 Days Exchange — Factory Defect Only',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ချုပ်ချကွဲ/ဆိုးတောင် (Factory Defect) ဖြစ်ပါက တူပစ္စည်းနှင့် လဲပေးပါသည်။'],
            ['code' => null, 'name' => '14 Days Exchange — Defective Only',
                'content' => 'ဝယ်ပြီး ၁၄ ရက်အတွင်း ပစ္စည်းချို့ယွင်းမှု ဖြစ်ပါက စစ်ဆေးပြီး လဲပေးပါသည်။'],
            ['code' => null, 'name' => 'Alteration Service Included',
                'content' => 'ဝတ်စောင်းဝယ်ယူပါက သင့်ဆိုဒ်နှင့် ကိုက်ညီစေရန် ချုပ်ပြင်ဝန်ဆောင်မှု ပေးပါသည်။'],
            ['code' => null, 'name' => 'Footwear — 7 Days Defect Exchange',
                'content' => 'ဖိနပ်ဝယ်ပြီး ၇ ရက်အတွင်း ပစ္စည်းထုတ်လုပ်မှု ချို့ယွင်းပါက အသစ်လဲပေးပါသည်။'],
            ['code' => null, 'name' => 'Bags — 7 Days Quality Guarantee',
                'content' => 'အိတ်ဝယ်ပြီး ၇ ရက်အတွင်း ဇစ်ကျိုး/ကြိုးပြတ် ဖြစ်ပါက စစ်ဆေးပြီး ပြင်/လဲပေးပါသည်။'],
        ];
    }

    private function fashionReturnPolicies(): array
    {
        return [
            ['code' => null, 'name' => 'No Return / No Exchange',
                'content' => 'ဝယ်ယူပြီးသောနောက် ပြန်လဲ၊ ပြန်ရောင်းချ မခွင့်ပြုပါ။ ဝတ်ကြည့်ပြီးမှ ဆုံးဖြတ်ပါ။'],
            ['code' => null, 'name' => 'Exchange Same Item — Defect Only',
                'content' => 'ချို့ယွင်းချက် (ချုပ်ချကွဲ) ဖြစ်ပါကသာ တူညီသောဒီဇိုင်း/ဆိုဒ်ဖြင့် လဲပေးပါသည်။'],
            ['code' => null, 'name' => '7 Days Color/Size Exchange',
                'content' => 'ဝယ်ပြီး ၇ ရက်အတွင်း ဆိုဒ် သို့မဟုတ် အရောင် မကိုက်ပါက ဝတ်မကြည့်ဘဲ ပကတိ ဖြင့် လဲနိုင်ပါသည်။'],
            ['code' => null, 'name' => 'Sale Items — No Exchange',
                'content' => 'Sale / Clearance ဈေးဖြင့် ဝယ်ယူသောပစ္စည်းများ Factory Defect မဟုတ်ပါက ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Underwear / Lingerie — No Return',
                'content' => 'အတွင်းဝတ်ပစ္စည်းများ ကျန်းမာရေးကြောင့် မည်သည့်အကြောင်းကြောင့်မဆို ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Footwear — Exchange in Box Condition',
                'content' => 'ဖိနပ်ဘူး၊ ကတ် ပကတိ ဖြင့် ၇ ရက်အတွင်း လာပါက ဆိုဒ်/ဒီဇိုင်း လဲပေးပါသည်။'],
            ['code' => null, 'name' => 'Tailored / Altered Items — No Return',
                'content' => 'ချုပ်ပြင်/ဆိုဒ်ချင်ပြင်ပြီးသောပစ္စည်းများ ပြန်မလဲပေးပါ။'],
        ];
    }

    private function fashionVariantPresets(): array
    {
        return [
            [
                'name' => 'Clothing Size — International',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'XS (Extra Small)', 'sku_suffix' => 'XS',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => 'S (Small)',         'sku_suffix' => 'S',   'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => 'M (Medium)',        'sku_suffix' => 'M',   'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => 'L (Large)',         'sku_suffix' => 'L',   'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => 'XL (Extra Large)', 'sku_suffix' => 'XL',  'retail_price_adjustment' => 1000, 'wholesale_price_adjustment' => 800,  'stock_status' => 'in_stock'],
                    ['name' => 'XXL',              'sku_suffix' => 'XXL', 'retail_price_adjustment' => 2000, 'wholesale_price_adjustment' => 1500, 'stock_status' => 'in_stock'],
                    ['name' => 'XXXL (Plus Size)', 'sku_suffix' => '3XL', 'retail_price_adjustment' => 3000, 'wholesale_price_adjustment' => 2000, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Clothing Size — Myanmar / Asian',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'Free Size', 'sku_suffix' => 'FS',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '34 (XS)',                    'sku_suffix' => '34',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '36 (S)',                     'sku_suffix' => '36',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '38 (M)',                     'sku_suffix' => '38',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '40 (L)',                     'sku_suffix' => '40',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '42 (XL)',                    'sku_suffix' => '42',  'retail_price_adjustment' => 1000, 'wholesale_price_adjustment' => 800,  'stock_status' => 'in_stock'],
                    ['name' => '44 (XXL)',                   'sku_suffix' => '44',  'retail_price_adjustment' => 2000, 'wholesale_price_adjustment' => 1500, 'stock_status' => 'in_stock'],
                    ['name' => '46 (XXXL)',                  'sku_suffix' => '46',  'retail_price_adjustment' => 3000, 'wholesale_price_adjustment' => 2000, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Shoe Size — EU',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'EU 35', 'sku_suffix' => '35', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 36', 'sku_suffix' => '36', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 37', 'sku_suffix' => '37', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 38', 'sku_suffix' => '38', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 39', 'sku_suffix' => '39', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 40', 'sku_suffix' => '40', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 41', 'sku_suffix' => '41', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 42', 'sku_suffix' => '42', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 43', 'sku_suffix' => '43', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 44', 'sku_suffix' => '44', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'EU 45', 'sku_suffix' => '45', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Kids Size',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => '3-6M (Baby)',  'sku_suffix' => '3M',  'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '6-12M',        'sku_suffix' => '6M',  'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '12-18M',       'sku_suffix' => '12M', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '2T (2 Years)', 'sku_suffix' => '2Y',  'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '3T / 4T',      'sku_suffix' => '4Y',  'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '5Y / 6Y',      'sku_suffix' => '6Y',  'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '7Y / 8Y',      'sku_suffix' => '8Y',  'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '10Y / 12Y',    'sku_suffix' => '10Y', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Jeans Waist Size — Inches',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => '26"', 'sku_suffix' => 'W26', 'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '28"', 'sku_suffix' => 'W28', 'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '30"', 'sku_suffix' => 'W30', 'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '32"', 'sku_suffix' => 'W32', 'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '34"', 'sku_suffix' => 'W34', 'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
                    ['name' => '36"', 'sku_suffix' => 'W36', 'retail_price_adjustment' => 1000, 'wholesale_price_adjustment' => 800, 'stock_status' => 'in_stock'],
                    ['name' => '38"', 'sku_suffix' => 'W38', 'retail_price_adjustment' => 2000, 'wholesale_price_adjustment' => 1500, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Fabric / Material',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'Cotton',            'sku_suffix' => 'CTN',  'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,     'stock_status' => 'in_stock'],
                    ['name' => 'Polyester',             'sku_suffix' => 'PLY',  'retail_price_adjustment' => -2000, 'wholesale_price_adjustment' => -1500, 'stock_status' => 'in_stock'],
                    ['name' => 'Cotton-Poly Blend',  'sku_suffix' => 'CTPL', 'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,     'stock_status' => 'in_stock'],
                    ['name' => 'Denim',                'sku_suffix' => 'DNM',  'retail_price_adjustment' => 3000,  'wholesale_price_adjustment' => 2000,  'stock_status' => 'in_stock'],
                    ['name' => 'Silk',               'sku_suffix' => 'SLK',  'retail_price_adjustment' => 10000, 'wholesale_price_adjustment' => 7000,  'stock_status' => 'in_stock'],
                    ['name' => 'Linen',             'sku_suffix' => 'LNN',  'retail_price_adjustment' => 5000,  'wholesale_price_adjustment' => 3500,  'stock_status' => 'in_stock'],
                    ['name' => 'Chiffon',             'sku_suffix' => 'CFN',  'retail_price_adjustment' => 4000,  'wholesale_price_adjustment' => 3000,  'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Fashion Colors',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'Black',            'sku_suffix' => 'BLK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'White',             'sku_suffix' => 'WHT', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Navy',             'sku_suffix' => 'NVY', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Beige',              'sku_suffix' => 'BGE', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Grey',            'sku_suffix' => 'GRY', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Pink',              'sku_suffix' => 'PNK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Red',                'sku_suffix' => 'RED', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Green',            'sku_suffix' => 'GRN', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Blue',              'sku_suffix' => 'BLU', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Brown / Camel',      'sku_suffix' => 'BRN', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Olive',          'sku_suffix' => 'OLV', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Multicolor / Print', 'sku_suffix' => 'MLT', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
        ];
    }

    // =====================================================================
    // GENERAL RETAIL — basic shared data (minimal)
    // =====================================================================

    private function generalBrands(): array
    {
        return [
            ['name' => 'Local Brand', 'code' => 'LOCAL'],
            ['name' => 'No Brand / Generic', 'code' => 'GEN'],
            ['name' => 'Import',              'code' => 'IMP'],
            ['name' => 'OEM',                            'code' => 'OEM'],
        ];
    }

    private function generalCategoryTree(): array
    {
        return [
            ['name' => 'General Product', 'code' => 'GEN',  'icon' => '📦', 'subs' => []],
            ['name' => 'Food & Beverage',         'code' => 'FNB',  'icon' => '🍱', 'subs' => []],
            ['name' => 'Household',          'code' => 'HH',   'icon' => '🏠', 'subs' => []],
            ['name' => 'Electronics',               'code' => 'ELEC', 'icon' => '🔌', 'subs' => []],
            ['name' => 'Service',                'code' => 'SVC',  'icon' => '🛠️', 'subs' => []],
        ];
    }
}

