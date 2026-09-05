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
            'connectors'      => ['count' => count($this->connectors($businessType)),     'label' => 'ကြိုးပေါက်/Spec Presets',           'icon' => '🔌'],
            'colors'          => ['count' => count($this->colors($businessType)),          'label' => 'အရောင် Presets (Colors)',            'icon' => '🎨'],
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
            if (in_array('connectors', $groups, true)) {
                $result['connectors'] = $this->importPresets($store, 'connector_spec', $this->connectors($businessType));
            }
            if (in_array('colors', $groups, true)) {
                $result['colors'] = $this->importPresets($store, 'color', $this->colors($businessType));
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
        if ($all || in_array('connectors', $groups, true)) {
            ProductMasterPreset::where('store_id', $store->id)->where('type', 'connector_spec')->delete();
        }
        if ($all || in_array('colors', $groups, true)) {
            ProductMasterPreset::where('store_id', $store->id)->where('type', 'color')->delete();
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

    private function connectors(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionConnectors(),
            'general' => $this->generalConnectors(),
            default   => $this->techConnectors(),
        };
    }

    private function colors(string $t = 'tech'): array
    {
        return match ($t) {
            'fashion' => $this->fashionColors(),
            default   => $this->techColors(),
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
                    ['name' => 'iOS / iPhone (အိုင်ဖုန်း)',       'code' => 'PHN_IOS', 'icon' => '🍎'],
                    ['name' => 'Android Phone (အန်ဒရွိုက်ဖုန်း)', 'code' => 'PHN_AND', 'icon' => '🤖'],
                    ['name' => 'Tablet / iPad (တက်ဘလက်)',        'code' => 'PHN_TAB', 'icon' => '📱'],
                    ['name' => 'Keypad Phone (ခလုတ်ဖုန်း)',      'code' => 'PHN_KPD', 'icon' => '📞'],
                    ['name' => 'Smart Watch (စမတ်နာရီ)',          'code' => 'PHN_WAT', 'icon' => '⌚'],
                ],
            ],
            [
                'name' => 'Cable & Charger',  'code' => 'CBCH', 'icon' => '🔌',
                'image_path' => 'categories/MF4ODlNndeKmXTgr9u7LzRAyFBaSqDZ29rQj8FyM.webp',
                'subs' => [
                    ['name' => 'Charging Cable (အားသွင်းကြိုး)',     'code' => 'CB',   'icon' => '🔌', 'image_path' => 'categories/I4CEBPaEb1ae6qyvErlf41FzsE4dRzt3Jc72iHc2.webp'],
                    ['name' => 'Charger Adapter (ခေါင်း)',         'code' => 'CH',   'icon' => '⚡', 'image_path' => 'categories/pJ5pTKhHVmhXUjNc2RT2ucLjq76T7E7j17feMwm9.webp'],
                    ['name' => 'Charger Set (ခေါင်း+ကြိုးတွဲလျက်)', 'code' => 'CHS',  'icon' => '🔌', 'image_path' => 'categories/old15boefSMbUdtCkTbYqSO4muee4Jge7EBtlNPB.webp'],
                    ['name' => 'Car Charger (ကားအားသွင်း)',        'code' => 'CCH',  'icon' => '🚗', 'image_path' => 'categories/gydjEkXxerukrjBE2HZFJVCrQzexRu57km4SaZps.webp'],
                    ['name' => 'Wireless Charger',                 'code' => 'WLC',  'icon' => '📶'],
                    ['name' => 'Power Adapter',                    'code' => 'PADP', 'icon' => '🔌'],
                ],
            ],
            [
                'name' => 'Audio & Sound',  'code' => 'AUD', 'icon' => '🎧',
                'image_path' => 'categories/rH4Z5WRpYuUksNxhXPOrHQ3ZRMiZZmIkYHhjdQsb.webp',
                'subs' => [
                    ['name' => 'Wired Earphone (ကြိုးနားကြပ်)',     'code' => 'EP',   'icon' => '🎧', 'image_path' => 'categories/MyLFwPrGzAlfuUMkmCNmQWlOufS0gSJ8VbFNLLz8.webp'],
                    ['name' => 'Bluetooth Earphone (ကြိုးမဲ့/TWS)', 'code' => 'BEP',  'icon' => '📶', 'image_path' => 'categories/KAYk4HOqgIxJl034ON2yqdx5NaC00qHpsQ8TvZ45.webp'],
                    ['name' => 'Bluetooth Speaker (စပီကာ)',        'code' => 'SPK',  'icon' => '🔊', 'image_path' => 'categories/HlLsUa13MJ1idXJjvYzo6L0xaDX4x51ylrvYbaWO.webp'],
                    ['name' => 'Microphone (မိုက်ခရိုဖုန်း)',         'code' => 'MIC',  'icon' => '🎙️', 'image_path' => 'categories/lgwS7HZVajy0UQpsVf7Ph4m4pvehBQZ8BndBWm16.webp'],
                    ['name' => 'Wired Headset / Headphone',        'code' => 'WHS',  'icon' => '🎧'],
                ],
            ],
            [
                'name' => 'Power & Storage',  'code' => 'PWR', 'icon' => '🔋',
                'image_path' => 'categories/Q8haJ85U9TPhINqd25G530XGnra5E2WHKXhoNnTk.webp',
                'subs' => [
                    ['name' => 'Power Bank (ပါဝါဘဏ်)',     'code' => 'PB',  'icon' => '🔋', 'image_path' => 'categories/H9DSKBR0jJRLMYVt8Cn1kLGBXZuf2TvTOATiA4RD.webp'],
                    ['name' => 'Memory Card (မမိုရီကတ်)',   'code' => 'SD',  'icon' => '💾', 'image_path' => 'categories/xoJbPXzXGVPGGkBMeuBJQRwPWyx79anhpMeM4fjL.webp'],
                    ['name' => 'USB Flash Drive',            'code' => 'USB', 'icon' => '💾'],
                    ['name' => 'SSD / Hard Drive',           'code' => 'SSD', 'icon' => '💾'],
                    ['name' => 'USB Hub / Card Reader',      'code' => 'HUB', 'icon' => '🔌'],
                ],
            ],
            [
                'name' => 'Screen & LCD',  'code' => 'SCR', 'icon' => '📱',
                'subs' => [
                    ['name' => 'Touch LCD (ထိသောမျက်နှာပြင်)',    'code' => 'TL',     'icon' => '📱', 'image_path' => 'categories/6KFlGA2qhCqc9ISXl1Nnbu6wl3YEhMoecI8rzlsY.webp'],
                    ['name' => 'Original Touch LCD',               'code' => 'TL_ORG', 'icon' => '📱'],
                    ['name' => 'Touch Screen (ကော်မကပ်)',          'code' => 'TS',     'icon' => '📱', 'image_path' => 'categories/HOAqeV2FAuAU9l1esOafPNxZBpVyo4k9tMaVWlvS.webp'],
                    ['name' => 'Front Glass (မှန်ချပ်)',            'code' => 'GLS',    'icon' => '🛡️'],
                    ['name' => 'OCA Glass',                        'code' => 'OCA',    'icon' => '🛡️', 'image_path' => 'categories/yDkDp6QkaYrNEr0gtmVznzmpr2qtd9Abo3U88Frx.webp'],
                    ['name' => 'Screen Protector (မှန်ကပ်)',       'code' => 'SG',     'icon' => '🛡️', 'image_path' => 'categories/iUKHo5ShXD5ZcKC2xPXcwCf5CNaxKLidfb1qrnyP.webp'],
                    ['name' => 'Privacy Filter',                   'code' => 'PVF',    'icon' => '🛡️'],
                ],
            ],
            [
                'name' => 'Phone Case & Cover',  'code' => 'ACC', 'icon' => '🛡️',
                'image_path' => 'categories/Os1tuu7hDIrXUJQztc7pACD2WI7UXwZgU2VTaFNo.webp',
                'subs' => [
                    ['name' => 'Phone Cover / Case',           'code' => 'CVR', 'icon' => '🛡️', 'image_path' => 'categories/M16Tk9it3VNuPel6uskFei2lS3nalqbMcFWQfBzs.webp'],
                    ['name' => 'Silicone Case (ဆေးကာဗာ)',       'code' => 'SIL', 'icon' => '🛡️'],
                    ['name' => 'Clear Case (ဖောင်းကြည်)',       'code' => 'CLR', 'icon' => '🛡️'],
                    ['name' => 'Leather Case (သားရေ)',           'code' => 'LTH', 'icon' => '🛡️'],
                    ['name' => 'Bumper / Shockproof Case',       'code' => 'BMP', 'icon' => '🛡️'],
                    ['name' => 'Card Holder Case',               'code' => 'CDH', 'icon' => '💳'],
                    ['name' => 'Sticker (စတစ်ကာ)',               'code' => 'STK', 'icon' => '🎨', 'image_path' => 'categories/ECw8TwNo9DPUr59kSEcEle9pmLrx8IdAogEWw1I9.webp'],
                    ['name' => 'Water Bag (ရေစိုခံအိတ်)',          'code' => 'WTP', 'icon' => '💧'],
                ],
            ],
            [
                'name' => 'Battery (ဘတ်ထရီ)',  'code' => 'BT_GRP', 'icon' => '🔋',
                'subs' => [
                    ['name' => 'Phone Battery (မူရင်း/အပို)', 'code' => 'BT_ORG', 'icon' => '🔋', 'image_path' => 'categories/yDSrXGv6HgX1UvdEgZhcS1vM2HWRw6K2GBlpG8jX.webp'],
                    ['name' => 'High Capacity Battery',      'code' => 'BT_HI',  'icon' => '🔋'],
                    ['name' => 'Standard Replacement Battery', 'code' => 'BT_STD', 'icon' => '🔋'],
                ],
            ],
            [
                'name' => 'Body & Back Cover',  'code' => 'BC_GRP', 'icon' => '📱',
                'image_path' => 'categories/YealuxjTGsKzHckTTCYb1wyZ7c5qjCxkDZfdbXp0.webp',
                'subs' => [
                    ['name' => 'Back Glass (နောက်ဖုံးမှန်)', 'code' => 'BC_GLS', 'icon' => '📱', 'image_path' => 'categories/GO5w6kVQmUeDuKVJvUnSuBeBaJYXguVU4jMJFuet.webp'],
                    ['name' => 'Back Cover (နောက်ဖုံး)',     'code' => 'BC',     'icon' => '📱'],
                    ['name' => 'Body Frame (ဘောင်)',          'code' => 'BD',     'icon' => '🗜️', 'image_path' => 'categories/i69bZAj3d9ptRlexO8KiPkehTe3tNETyraC3ZjRS.webp'],
                    ['name' => 'Mid Frame',                   'code' => 'MDF',    'icon' => '🗜️'],
                    ['name' => 'Housing Set',                 'code' => 'HSG',    'icon' => '📱'],
                ],
            ],
            [
                'name' => 'Phone Spare Parts',  'code' => 'PRT', 'icon' => '🔧',
                'image_path' => 'categories/oUnSf1QNm4MqDW6UXai576svfniAvDKXI94oKRg5.webp',
                'subs' => [
                    ['name' => 'Charging Port (အားသွင်းပေါက်)', 'code' => 'USBFIX', 'icon' => '🔌', 'image_path' => 'categories/cbzVCEl5s0Hcqc4n1V6WiFVYjlFlyeVqrEKI7WDv.webp'],
                    ['name' => 'Power Switch / Flex',           'code' => 'PWRSW',  'icon' => '🔘', 'image_path' => 'categories/PQp2A4lKQb0o1bYEmAGaOM0MnPLEMcQeOK8b2hDm.webp'],
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
                    ['name' => 'CCTV Camera (ကင်မရာ)',          'code' => 'CAM',     'icon' => '📹', 'image_path' => 'categories/dtRipoSfsFqd0N14FA8VWnb37Al9iCsZuvFdVQAZ.webp'],
                    ['name' => 'CCTV Accessories (ဆက်စပ်ပစ္စည်း)', 'code' => 'CCTVACC', 'icon' => '🔌', 'image_path' => 'categories/0Fksd3cTp9AVHebL1sjUz1AOPnYUAjKbcxw4qoPM.webp'],
                    ['name' => 'CCTV IP Camera (ပြင်ပ)',       'code' => 'IPCAM',   'icon' => '📹'],
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
                    ['name' => 'Mini Fan (ပန်ကာငယ်)',         'code' => 'FAN',   'icon' => '💨', 'image_path' => 'categories/dfWcbjbmds0dkEIrZizb70e49URI736F2Ps6xJzu.webp'],
                    ['name' => 'Mouse (မောက်စ်)',              'code' => 'MOU',   'icon' => '🖱️', 'image_path' => 'categories/hMhHwoUUWWku89q6MtVv9rrzFD5GL7Uv8hwPhsOg.webp'],
                    ['name' => 'LED Light (မီးသီး/မီးလိုင်း)', 'code' => 'LED',   'icon' => '💡', 'image_path' => 'categories/q7ojp3SVmnxlXawNfyNgxVnJfVrq8bl6b80Cajym.webp'],
                    ['name' => 'Desktop PC / All-in-One',   'code' => 'PC',    'icon' => '🖥️'],
                    ['name' => 'Laptop / Notebook',         'code' => 'LAPTOP', 'icon' => '💻'],
                    ['name' => 'Monitor / Display (မော်နီတာ)', 'code' => 'MON', 'icon' => '🖥️'],
                    ['name' => 'Keyboard (ကီးဘုတ်)',        'code' => 'KBD',   'icon' => '⌨️'],
                    ['name' => 'Webcam',                    'code' => 'WCM',   'icon' => '📷'],
                    ['name' => 'USB Cable / Adapter',       'code' => 'USBAD', 'icon' => '🔌'],
                    ['name' => 'HDMI Display Cable',        'code' => 'HDMI',  'icon' => '🖥️'],
                ],
            ],
            [
                'name' => 'Service & Repair',  'code' => 'SVC', 'icon' => '🛠️',
                'subs' => [
                    ['name' => 'Screen Repair / LCD ပြင်',     'code' => 'SVC_SCR',  'icon' => '🛠️'],
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

    private function techConnectors(): array
    {
        return [
            ['code' => 'TC',      'name' => 'Type-C',                    'content' => 'USB Type-C interface'],
            ['code' => 'MC',      'name' => 'Micro USB',                 'content' => 'Micro USB standard interface'],
            ['code' => 'IP',      'name' => 'Lightning / iPhone',        'content' => 'Apple 8-pin lightning connector'],
            ['code' => '3.5MM',   'name' => '3.5mm Aux Jack',            'content' => 'Standard audio headphone jack'],
            ['code' => '3IN1',    'name' => '3-in-1 Combo',              'content' => 'Multi-head Type-C + Micro + Lightning'],
            ['code' => 'OTG',     'name' => 'OTG Adapter',               'content' => 'On-The-Go USB converter'],
            ['code' => '20W',     'name' => '20W Fast Charge',           'content' => '20 Watt fast power delivery'],
            ['code' => '33W',     'name' => '33W Super Charge',          'content' => '33 Watt super flash charge (Xiaomi/OPPO)'],
            ['code' => '65W',     'name' => '65W GaN Fast Charge',       'content' => '65 Watt gallium nitride charger'],
            ['code' => '100W',    'name' => '100W PD Ultra Fast',        'content' => '100 Watt ultra fast power delivery'],
            ['code' => '120W',    'name' => '120W Hyper Charge',         'content' => '120 Watt hyper fast charging (Xiaomi)'],
            ['code' => '10K',     'name' => '10000mAh Capacity',         'content' => 'Power bank 10000mAh capacity'],
            ['code' => '20K',     'name' => '20000mAh Capacity',         'content' => 'Power bank 20000mAh capacity'],
            ['code' => '30K',     'name' => '30000mAh Capacity',         'content' => 'Power bank 30000mAh capacity'],
            ['code' => 'ORG',     'name' => 'Original (မူရင်းအစစ်)',     'content' => 'Genuine manufacturer original quality'],
            ['code' => 'AAA',     'name' => 'AAA Quality (အဆင့်မြင့်)', 'content' => 'High quality grade AAA replacement'],
            ['code' => 'MA',      'name' => 'MA Quality (သာမန်)',        'content' => 'Standard replacement grade'],
            ['code' => 'OCA',     'name' => 'OCA Glass',                 'content' => 'Optically clear adhesive glass panel'],
            ['code' => 'SIL',     'name' => 'Silicone Material',         'content' => 'Soft silicone material case/cover'],
            ['code' => 'CLR',     'name' => 'Clear / Transparent',       'content' => 'Transparent clear material'],
            ['code' => 'HDMI',    'name' => 'HDMI Cable',                'content' => 'High-definition multimedia interface'],
            ['code' => 'DP',      'name' => 'DisplayPort',               'content' => 'DisplayPort video interface'],
            ['code' => 'VGA',     'name' => 'VGA Cable',                 'content' => 'VGA analog display connector'],
            ['code' => 'RJ45',    'name' => 'RJ45 / LAN',               'content' => 'Cat5e / Cat6 network cable RJ45'],
            ['code' => 'BNC',     'name' => 'BNC Coaxial (CCTV)',        'content' => 'BNC analog CCTV video connector'],
        ];
    }

    private function techColors(): array
    {
        return [
            ['code' => 'BLK', 'name' => 'Black (အနက်)',           'color_hex' => '#0A0A0A', 'content' => 'Jet Black'],
            ['code' => 'WHT', 'name' => 'White (အဖြူ)',            'color_hex' => '#FAFAFA', 'content' => 'Pearl White'],
            ['code' => 'BLU', 'name' => 'Blue (အပြာ)',             'color_hex' => '#2563EB', 'content' => 'Ocean Blue'],
            ['code' => 'RED', 'name' => 'Red (အနီ)',               'color_hex' => '#DC2626', 'content' => 'Ruby Red'],
            ['code' => 'GLD', 'name' => 'Gold (ရွှေရောင်)',         'color_hex' => '#D97706', 'content' => 'Metallic Gold'],
            ['code' => 'SLV', 'name' => 'Silver (ငွေရောင်)',        'color_hex' => '#94A3B8', 'content' => 'Metallic Silver'],
            ['code' => 'GRY', 'name' => 'Gray (မီးခိုးရောင်)',      'color_hex' => '#64748B', 'content' => 'Space Gray'],
            ['code' => 'PUR', 'name' => 'Purple (ခရမ်းရောင်)',      'color_hex' => '#9333EA', 'content' => 'Deep Purple'],
            ['code' => 'GRN', 'name' => 'Green (အစိမ်းရောင်)',      'color_hex' => '#16A34A', 'content' => 'Emerald Green'],
            ['code' => 'PNK', 'name' => 'Pink (ပန်းရောင်)',          'color_hex' => '#F472B6', 'content' => 'Rose Pink'],
            ['code' => 'YLW', 'name' => 'Yellow (အဝါရောင်)',        'color_hex' => '#EAB308', 'content' => 'Sunlight Yellow'],
            ['code' => 'ORG', 'name' => 'Orange (လိမ္မော်ရောင်)',   'color_hex' => '#EA580C', 'content' => 'Tangerine Orange'],
            ['code' => 'CYN', 'name' => 'Cyan / Teal (ပင်လယ်ပြာ)', 'color_hex' => '#0891B2', 'content' => 'Teal Blue'],
            ['code' => 'BRN', 'name' => 'Brown (ညိုရောင်)',          'color_hex' => '#92400E', 'content' => 'Coffee Brown'],
            ['code' => 'RGD', 'name' => 'Rose Gold (နှင်းဆီ-ရွှေ)', 'color_hex' => '#FBBF8A', 'content' => 'Rose Gold gradient'],
            ['code' => 'DBL', 'name' => 'Dark Blue (ရေပြာ-တိမ်)',   'color_hex' => '#1E3A5F', 'content' => 'Navy / Deep Blue'],
            ['code' => 'LBL', 'name' => 'Light Blue (မှုန်ပြာ)',    'color_hex' => '#BAE6FD', 'content' => 'Sky Blue'],
            ['code' => 'GRD', 'name' => 'Gradient / Rainbow',       'color_hex' => '#A855F7', 'content' => 'Rainbow gradient finish'],
            ['code' => 'TRN', 'name' => 'Transparent (ပွင့်)',       'color_hex' => '#CCCCCC', 'content' => 'Clear transparent'],
        ];
    }

    private function techShelves(): array
    {
        return [
            ['code' => 'CTR-A',       'name' => 'Counter Glass A (ကောင်တာ မှန်ပုံး A)',          'content' => 'Main counter glass showcase section A'],
            ['code' => 'CTR-B',       'name' => 'Counter Glass B (ကောင်တာ မှန်ပုံး B)',          'content' => 'Main counter glass showcase section B'],
            ['code' => 'CAB-A1',      'name' => 'Phone Cabinet A1 (ဖုန်းဗီရို A1)',             'content' => 'High-value smartphone cabinet level 1'],
            ['code' => 'CAB-A2',      'name' => 'Phone Cabinet A2 (ဖုန်းဗီရို A2)',             'content' => 'Smartphone display cabinet level 2'],
            ['code' => 'CAB-A3',      'name' => 'Phone Cabinet A3 (ဖုန်းဗီရို A3)',             'content' => 'Mid-range phone cabinet level 3'],
            ['code' => 'CAB-A4',      'name' => 'Phone Cabinet A4 (ဖုန်းဗီရို A4)',             'content' => 'Budget phone cabinet level 4'],
            ['code' => 'CAB-T1',      'name' => 'Tablet Showcase T1 (တက်ဘလက်ဗီရို)',             'content' => 'iPad & Tablet showcase cabinet'],
            ['code' => 'CAB-W1',      'name' => 'Watch Showcase W1 (နာရီဗီရို)',                 'content' => 'Smartwatch showcase cabinet'],
            ['code' => 'CAB-SD',      'name' => 'Storage Cabinet SD (မမိုရီဗီရို)',              'content' => 'Secure cabinet for SD & USB cards'],
            ['code' => 'A-01',        'name' => 'Wall Shelf A1 (နံရံစင် — အပေါ်ထပ်)',           'content' => 'Wall-mounted display shelf level 1'],
            ['code' => 'A-02',        'name' => 'Wall Shelf A2 (နံရံစင် — အလယ်ထပ်)',           'content' => 'Wall-mounted display shelf level 2'],
            ['code' => 'A-03',        'name' => 'Wall Shelf A3 (နံရံစင် — အောက်ထပ်)',          'content' => 'Wall-mounted display shelf level 3'],
            ['code' => 'B-01',        'name' => 'Side Shelf B1 (ဘေးစင် — အပေါ်ထပ်)',           'content' => 'Side display rack level 1'],
            ['code' => 'B-02',        'name' => 'Side Shelf B2 (ဘေးစင် — အောက်ထပ်)',          'content' => 'Side display rack level 2'],
            ['code' => 'HOOK-R',      'name' => 'Hook Rail Right (ညာဘက် တွဲချိတ်)',            'content' => 'Right-side hanging peg hook rail'],
            ['code' => 'HOOK-L',      'name' => 'Hook Rail Left (ဘယ်ဘက် တွဲချိတ်)',            'content' => 'Left-side hanging peg hook rail'],
            ['code' => 'RACK-CHG',    'name' => 'Charger Rack (အားသွင်းစင်)',                  'content' => 'Chargers and fast power accessories rack'],
            ['code' => 'RACK-CBL',    'name' => 'Cable Rack (ကြိုးစင်)',                       'content' => 'Data & charging cables peg rack'],
            ['code' => 'RACK-PB',     'name' => 'Power Bank Rack (ပါဝါဘဏ်စင်)',                'content' => 'Power bank display rack'],
            ['code' => 'RACK-AUD',    'name' => 'Audio Rack (နားကြပ်စင်)',                     'content' => 'Headphone, TWS, and speaker rack'],
            ['code' => 'RACK-GLS',    'name' => 'Glass & Screen Rack (မှန်ကပ်စင်)',             'content' => 'Tempered glass and film display rack'],
            ['code' => 'RACK-CASE',   'name' => 'Case & Cover Rack (ကာဗာစင်)',                 'content' => 'Phone cases display rack'],
            ['code' => 'RACK-STND',   'name' => 'Holder & Stand Rack (ဒေါက်တိုင်စင်)',          'content' => 'Car mounts and desk stands display rack'],
            ['code' => 'CAB-01',      'name' => 'Cabinet Store A (ပြင်သိမ်းဗီရို)',              'content' => 'Storage cabinet for overstock / offline items'],
            ['code' => 'RP-RACK',     'name' => 'Repair Bench Rack (ပြင်ဆင်ရေး စင်)',          'content' => 'Service bench spare parts rack'],
            ['code' => 'SPARE-LCD',   'name' => 'Spare LCD Cabinet (LCD အပိုပစ္စည်းဗီရို)',      'content' => 'Technician LCD screen storage cabinet'],
            ['code' => 'SPARE-BAT',   'name' => 'Spare Battery Bin (ဘက်ထရီသေတ္တာ)',            'content' => 'Technician phone battery storage bin'],
            ['code' => 'SPARE-PRT',   'name' => 'Small Parts Drawer (အပိုပစ္စည်းအံဆွဲ)',          'content' => 'Technician flex cables and charging port parts'],
            ['code' => 'SPARE-TOOL',  'name' => 'Repair Tools Shelf (စက်ပြင်ကိရိယာစင်)',        'content' => 'Technician repair kits and precision screwdrivers'],
            ['code' => 'SERVICE-DESK','name' => 'Service & Intake Desk (ပြုပြင်ရေးကောင်တာ)',   'content' => 'Service center customer receiving desk'],
            ['code' => 'DIGITAL-VAULT','name'=> 'Digital Code Vault (ဒစ်ဂျစ်တယ်ဆာဗာ)',         'content' => 'Instant electronic delivery vault'],
            ['code' => 'WH-MAIN',     'name' => 'Warehouse Rack (ဂိုဒေါင် — ပင်မ စင်ကြီး)',  'content' => 'Main warehouse bulk storage rack'],
            ['code' => 'WH-B',        'name' => 'Warehouse Bin B (ဂိုဒေါင် — bin B)',         'content' => 'Secondary bin/area in warehouse'],
            ['code' => 'ONLINE',      'name' => 'Online Stock Only (အွန်လိုင်း)',               'content' => 'Virtual stock for e-commerce listings only'],
        ];
    }

    private function techWarranties(): array
    {
        return [
            ['code' => null, 'name' => 'ရောင်းချပြီး ချက်ချင်း စစ်ဆေးပေးပါ (No Warranty)',
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
            ['code' => null, 'name' => '3 Days Testing Warranty (၃ ရက် အစမ်းသုံး)',
                'content' => 'ဝယ်ယူပြီး ၃ ရက်အတွင်း စက်ချို့ယွင်းချက် (Factory Defect) ဖြစ်ပါက စစ်ဆေးပြင်ဆင်ပေးပါသည်။'],
            ['code' => null, 'name' => '7 Days Testing Warranty (၇ ရက် အစမ်းသုံး)',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း Factory Defect ဖြစ်ပေါ်ပါက အသစ်လဲလှယ်ပေးပါသည်။ ရေဝင်ခြင်း၊ ပြုတ်ကျကွဲရှခြင်း အကျုံးမဝင်ပါ။'],
            ['code' => null, 'name' => '1 Month Free Service (၁ လ လက်ခမဲ့ ဝန်ဆောင်မှု)',
                'content' => 'ဝယ်ယူပြီး ၁ လအတွင်း ပစ္စည်းစစ်ဆေးပြင်ဆင်ခ — လက်ခ အခမဲ့ ဝန်ဆောင်မှုပေးပါသည်။'],
            ['code' => null, 'name' => '3 Months Official Warranty (၃ လ တရားဝင် အာမခံ)',
                'content' => '၃ လ အာမခံ ပါဝင်သည်။ စက်ပစ္စည်း ချို့ယွင်းမှုများအတွက် အခမဲ့ စစ်ဆေး ပြုပြင် လဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => '6 Months Service Warranty (၆ လ ဆိုင်အာမခံ)',
                'content' => '၆ လ အတွင်း ဆိုင်အာမခံ ဖြင့် စစ်ဆေးပြင်ဆင်ခွင့် ရပါသည်။'],
            ['code' => null, 'name' => '6 Months Official Warranty (၆ လ ကုမ္ပဏီအာမခံ)',
                'content' => 'ကုမ္ပဏီ တရားဝင် ၆ လ အာမခံ ပါဝင်သည်။ Warranty Card ပြသ၍ ဝင်ရောက် စစ်ဆေးနိုင်ပါသည်။'],
            ['code' => null, 'name' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)',
                'content' => 'ကုမ္ပဏီ တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။ Warranty Card သိမ်းဆည်းထားပါ။'],
            ['code' => null, 'name' => '2 Years Brand Warranty (၂ နှစ် Brand အာမခံ)',
                'content' => 'Manufacturer ၂ နှစ် အာမခံ ပါဝင်သည်။ Global/Local Warranty စစ်ဆေးပါ။'],
            ['code' => null, 'name' => 'Screen Protector No Warranty (မျက်နှာပြင်ကာ — အာမခံမပါ)',
                'content' => 'မျက်နှာပြင် ကာမှန်ချပ် Tempered Glass တပ်ဆင်ပြီးသည်နှင့် ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'CCTV 2 Years Warranty (CCTV ၂ နှစ် အာမခံ)',
                'content' => 'CCTV ကင်မရာနှင့် DVR/NVR တွင် ၂ နှစ် ကုမ္ပဏီ အာမခံ ပါဝင်သည်။'],
        ];
    }

    private function techReturnPolicies(): array
    {
        return [
            ['code' => null, 'name' => 'No Return / Exchange (ပြန်မလဲပေးပါ)',
                'content' => 'ဝယ်ယူပြီးသည့်နောက် ပြန်လဲ/ပြန်လည်ရောင်းချ မခွင့်ပြုပါ။ ဝယ်ချင်မှ ဝယ်ပါ။'],
            ['code' => null, 'name' => '7 Days Defect Exchange (ဘူးအကောင်းအတိုင်း)',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ဘူးခွံ၊ ဘားကုဒ်နှင့် ဆက်စပ်ပစ္စည်းများ အကောင်းပကတိ ရှိပါက တန်ဖိုးတူ ပစ္စည်းနှင့် လဲနိုင်ပါသည်။'],
            ['code' => null, 'name' => 'Defective Exchange within Warranty',
                'content' => 'အာမခံသက်တမ်းအတွင်း စက်ချို့ယွင်းပါက စစ်ဆေးလဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => 'Defective Exchange within 7 Days',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ချို့ယွင်းချက်ရှိပါက အသစ်လဲလှယ်ပေးပါသည်။'],
            ['code' => null, 'name' => 'Spare Parts — Test Before Take (စစ်ဆေးပြီးမှ ယူပါ)',
                'content' => 'ဖုန်းအပိုပစ္စည်းများကို ဝယ်ယူချိန်တွင် ချက်ချင်းစမ်းသပ်ပြီး ယူဆောင်ရမည်ဖြစ်ပြီး ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Service Satisfaction Guaranteed',
                'content' => 'ပြုပြင်ရေး ဝန်ဆောင်မှု စိတ်တိုင်းကျစေရန် တာဝန်ယူပေးပါသည်။'],
            ['code' => null, 'name' => 'Non-refundable once code is redeemed',
                'content' => 'ဒစ်ဂျစ်တယ်ကုတ်ကို ပေးပို့ပြီးပါက သို့မဟုတ် Redeem ပြုလုပ်ပြီးပါက ငွေပြန်မအမ်းပါ။'],
            ['code' => null, 'name' => 'Direct in-game credit - Final Sale',
                'content' => 'ဂိမ်းအကောင့်ထဲသို့ တိုက်ရိုက်ငွေဖြည့်ပြီးပါက ပြန်လည်ပြင်ဆင်ခွင့်မရှိပါ။'],
            ['code' => null, 'name' => '3 Days Factory Defect Exchange (၃ ရက် Factory Defect လဲ)',
                'content' => 'ဝယ်ယူပြီး ၃ ရက်အတွင်း စက်ချို့ယွင်းချက် (Factory Defect) ဖြစ်ပါက တန်ဖိုးတူ ပစ္စည်းနှင့် လဲလှယ်ပေးပါသည်။ ရေဝင်/ကွဲ/ပြုတ် မပါ။'],
            ['code' => null, 'name' => '7 Days Box-Condition Exchange (၇ ရက် ဘူးအကောင်းအတိုင်း လဲ)',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ဘူးခွံ၊ ဘားကုဒ်နှင့် ဆက်စပ်ပစ္စည်းများ အကောင်းပကတိ ရှိပါက တန်ဖိုးတူ ပစ္စည်းနှင့် လဲနိုင်ပါသည်။'],
            ['code' => null, 'name' => 'Touch LCD / Screen — Test Before Purchase',
                'content' => 'Touch LCD နှင့် မှန်ချပ်များကို ဖုန်းတွင် ကော်မကပ်မီ ပြင်ပမှ ကြိုးထိုး စမ်းသပ်ပေးရပါမည်။ ကော်ကပ်ပြီး/ဖလင်ခွာပြီး/ဘားကုဒ်ပျက်ပါက ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Accessories — Defective Exchange Only (Accessories ချို့ယွင်းမှသာ လဲ)',
                'content' => 'ကြိုး၊ အားသွင်းခေါင်း၊ Case/Cover စသည့် Accessories များ ချို့ယွင်းပါက ဘောက်ချာပြသ၍ စစ်ဆေးပြီး အသစ်လဲလှယ်ပေးပါသည်။ Defect မဟုတ်ပါက ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Spare Parts — No Return (Spare Parts ပြန်မလဲပေးပါ)',
                'content' => 'Spare Part / IC / Flex / Battery များကို ဝယ်ယူချိန်တွင် စစ်ဆေးပြီး ယူဆောင်ရမည်ဖြစ်ပြီး ပြည်တင်ပြီးနောက် ပြန်မခွင့်ပြုပါ။'],
            ['code' => null, 'name' => 'Service — Fee Charged Regardless of Outcome (ပြင်ဆင်ငွေ ကောက်ခံမည်)',
                'content' => 'စစ်ဆေး/ပြင်ဆင်ပြီးသည့်နောက် ပြင်မရ/ဆက်မပြင်ဆိုပါက စစ်ဆေးခ (Diagnostic Fee) ကောက်ခံပါသည်။'],
            ['code' => null, 'name' => 'CCTV — Installation No Refund (CCTV တပ်ဆင်ပြီး ငွေမပြန်ပေးပါ)',
                'content' => 'CCTV တပ်ဆင်မှု ပြီးစီးပြီးနောက် ငွေ ပြန်မပေးပါ။ ကင်မရာ/DVR ချို့ယွင်းပါက Warranty ဖြင့် ပြန်စစ်ဆေးပေးပါသည်။'],
            ['code' => null, 'name' => 'Digital Code — No Refund Once Delivered (Digital Code ပေးပြီး ငွေမပြန်)',
                'content' => 'Gift Card / Voucher Code / Top-up ပေးပို့ပြီးသည့်နောက် ငွေပြန်မပေးပါ။ ပြည်တင်မပြုမီ စစ်ဆေးပါ။'],
        ];
    }

    private function techVariantPresets(): array
    {
        return [
            [
                'name' => 'Connector Types (ကြိုးခေါင်း အမျိုးအစား)',
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
                'name' => 'Colors (အရောင်များ)',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => 'Black (အနက်)',          'sku_suffix' => 'BLK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'White (အဖြူ)',           'sku_suffix' => 'WHT', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Blue (အပြာ)',            'sku_suffix' => 'BLU', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Red (အနီ)',              'sku_suffix' => 'RED', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Gold (ရွှေ)',             'sku_suffix' => 'GLD', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Silver (ငွေ)',            'sku_suffix' => 'SLV', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Gray (မီးခိုး)',          'sku_suffix' => 'GRY', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Purple (ခရမ်း)',          'sku_suffix' => 'PUR', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Green (အစိမ်း)',          'sku_suffix' => 'GRN', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Pink (ပန်းရောင်)',        'sku_suffix' => 'PNK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Rose Gold (နှင်းဆီ-ရွှေ)', 'sku_suffix' => 'RGD', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Power Bank Capacity (ပါဝါဘဏ် ပမာဏ)',
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
                'name' => 'Charging Speed / Wattage (ဝပ်အား)',
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
                'name' => 'Quality Grade (အရည်အသွေး အဆင့်)',
                'category_family' => 'mobile',
                'options' => [
                    ['name' => 'Original (မူရင်းအစစ်)',  'sku_suffix' => 'ORG', 'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,     'stock_status' => 'in_stock'],
                    ['name' => 'AAA+ Quality',            'sku_suffix' => 'AAA', 'retail_price_adjustment' => -8000, 'wholesale_price_adjustment' => -5500, 'stock_status' => 'in_stock'],
                    ['name' => 'AA Quality',              'sku_suffix' => 'AA',  'retail_price_adjustment' => -15000,'wholesale_price_adjustment' => -10000,'stock_status' => 'in_stock'],
                    ['name' => 'MA Quality (သာမန်)',      'sku_suffix' => 'MA',  'retail_price_adjustment' => -22000,'wholesale_price_adjustment' => -15000,'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'CCTV Resolution (ရုပ်ကြည်အဆင့်)',
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
            ['name' => 'Shwe Zin (ရွှေဇင်)',   'code' => 'SZIN'],
            ['name' => 'YKK',                'code' => 'YKK'],
            ['name' => 'Brother / Juki',     'code' => 'BJUK'],
            ['name' => 'Flying Wheel (ရဟတ်)', 'code' => 'FLW'],
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
                'name' => 'Tailoring Service (စက်ချုပ်ဝန်ဆောင်မှု)', 'code' => 'SVC_TLR', 'icon' => '✂️',
                'subs' => [
                    ['name' => "Women's Dressmaking (အမျိုးသမီး ရင်ဖုံး/ရင်စေ့ ချုပ်ခ)", 'code' => 'TLR_WD', 'icon' => '👗'],
                    ['name' => "Men's Tailoring (အမျိုးသား တိုက်ပုံ/ရှပ် ချုပ်ခ)",         'code' => 'TLR_MT', 'icon' => '👔'],
                    ['name' => 'Bridal & Formal Design (မင်္ဂလာ/ပွဲတက် ချုပ်ခ)',          'code' => 'TLR_BR', 'icon' => '👰'],
                    ['name' => 'Alteration & Hemming (အချိုးပြင်/အနားခေါက် ချုပ်ခ)',      'code' => 'TLR_AL', 'icon' => '✂️'],
                    ['name' => 'Custom Embroidery (ပန်းထိုး/ဒီဇိုင်းဖော် ချုပ်ခ)',       'code' => 'TLR_EM', 'icon' => '🪡'],
                ],
            ],
            [
                'name' => 'Fabrics & Textiles (ပိတ်စနှင့် အထည်လိပ်)', 'code' => 'FAB', 'icon' => '🧵',
                'subs' => [
                    ['name' => 'Silk & Satin (ပိုးထည်နှင့် ပိုးဇာ)',             'code' => 'FAB_SLK', 'icon' => '👘'],
                    ['name' => 'Cotton & Linen (ချည်ပိတ်စနှင့် လင်နင်)',         'code' => 'FAB_CTN', 'icon' => '🧵'],
                    ['name' => 'Denim & Twill (ဂျင်းစနှင့် တွယ်စ)',             'code' => 'FAB_DNM', 'icon' => '👖'],
                    ['name' => 'Chiffon & Georgette (ရှီဖွန်နှင့် ဂျော့ဂျက်)',    'code' => 'FAB_CFN', 'icon' => '🥻'],
                    ['name' => 'Lace & Brocade (ဇာစနှင့် ပန်းထိုးစ)',            'code' => 'FAB_LAC', 'icon' => '✨'],
                    ['name' => 'Traditional Fabric (ရိုးရာ ချည်ထည်လိပ်)',         'code' => 'FAB_TRD', 'icon' => '🎎'],
                ],
            ],
            [
                'name' => 'Sewing Accessories (စက်ချုပ်အပိုပစ္စည်း)', 'code' => 'NOT', 'icon' => '🪡',
                'subs' => [
                    ['name' => 'Sewing Threads (အပ်ချည်ကြိုး)',                 'code' => 'NOT_THR', 'icon' => '🧵'],
                    ['name' => 'Zippers & Fasteners (ဇစ်နှင့် တွယ်ချိတ်)',        'code' => 'NOT_ZIP', 'icon' => '🤐'],
                    ['name' => 'Buttons & Buckles (ကြယ်သီးနှင့် ဘတ်ကယ်)',        'code' => 'NOT_BTN', 'icon' => '🔘'],
                    ['name' => 'Machine Needles & Parts (စက်အပ်နှင့် အပိုပစ္စည်း)', 'code' => 'NOT_NDL', 'icon' => '🪡'],
                    ['name' => 'Tailor Tools & Scissors (ဓားကပ်၊ ပေကြိုး)',       'code' => 'NOT_TOL', 'icon' => '✂️'],
                    ['name' => 'Interlining & Elastic (ကော်ပြားနှင့် သားရေကြိုး)', 'code' => 'NOT_INT', 'icon' => '📦'],
                    ['name' => 'Sewing Machine Oil (စက်ဆီ)',                    'code' => 'NOT_OIL', 'icon' => '🛢️'],
                ],
            ],
            [
                'name' => 'Clothing (အဝတ်အထည်)', 'code' => 'CLO', 'icon' => '👗',
                'subs' => [
                    ['name' => "Women's Top (အပေါ်ဝတ်-အမျိုးသမီး)",    'code' => 'WT',   'icon' => '👚'],
                    ['name' => "Women's Bottom (အောက်ဝတ်-အမျိုးသမီး)",  'code' => 'WB',   'icon' => '👖'],
                    ['name' => "Men's Top (အပေါ်ဝတ်-အမျိုးသား)",        'code' => 'MT',   'icon' => '👔'],
                    ['name' => "Men's Bottom (အောက်ဝတ်-အမျိုးသား)",     'code' => 'MB',   'icon' => '👖'],
                    ['name' => 'Dress (ဝတ်စုံ)',                         'code' => 'DRS',  'icon' => '👗'],
                    ['name' => 'Jacket / Coat (ဂျာကင်/ကုတ်)',           'code' => 'JKT',  'icon' => '🧥'],
                    ['name' => 'Traditional Wear (ဓမ္မတာဝတ်)',           'code' => 'TRAD', 'icon' => '🎎'],
                    ['name' => 'Sportswear (အားကစားဝတ်)',                'code' => 'SPT',  'icon' => '🏃'],
                    ['name' => "Kids Clothing (ကလေးဝတ်)",               'code' => 'KID',  'icon' => '👶'],
                ],
            ],
            [
                'name' => 'Footwear (ဖိနပ်)', 'code' => 'FTW', 'icon' => '👟',
                'subs' => [
                    ['name' => "Women's Shoes (အမျိုးသမီးဖိနပ်)",  'code' => 'WS',   'icon' => '👠'],
                    ['name' => "Men's Shoes (အမျိုးသားဖိနပ်)",      'code' => 'MS',   'icon' => '👞'],
                    ['name' => 'Sneakers (ကော်ဖိနပ်)',               'code' => 'SNK',  'icon' => '👟'],
                    ['name' => 'Sandals / Slippers (ဖိနပ်ချပ်)',     'code' => 'SAN',  'icon' => '🩴'],
                    ['name' => 'Heels (ဒေါင်ဖိနပ်)',                 'code' => 'HEL',  'icon' => '👠'],
                    ['name' => 'Boots (ဘောင်းဖိနပ်)',                'code' => 'BOT',  'icon' => '👢'],
                    ['name' => "Kids Shoes (ကလေးဖိနပ်)",            'code' => 'KS',   'icon' => '👟'],
                ],
            ],
            [
                'name' => 'Bags (အိတ်)', 'code' => 'BAG', 'icon' => '👜',
                'subs' => [
                    ['name' => 'Handbag / Shoulder Bag (လက်ကိုင်/လက်ပြန်)', 'code' => 'HB',  'icon' => '👜'],
                    ['name' => 'Backpack (မျောက်ကျောအိတ်)',                   'code' => 'BP',  'icon' => '🎒'],
                    ['name' => 'Clutch / Evening Bag',                         'code' => 'CLT', 'icon' => '👜'],
                    ['name' => 'Tote Bag (ကြိုးအိတ်)',                        'code' => 'TOT', 'icon' => '🛍️'],
                    ['name' => 'Wallet / Purse (ပိုက်ဆံအိတ်)',                'code' => 'WLT', 'icon' => '👛'],
                    ['name' => 'Travel / Luggage (ခရီးအိတ်)',                 'code' => 'TRV', 'icon' => '🧳'],
                ],
            ],
            [
                'name' => 'Jewelry & Accessories (ဆောင်ယောင်ပစ္စည်း)', 'code' => 'JWL', 'icon' => '💍',
                'subs' => [
                    ['name' => 'Necklace (လည်ဆွဲ)',           'code' => 'NCK',  'icon' => '📿'],
                    ['name' => 'Earring (နားကပ်)',             'code' => 'EAR',  'icon' => '💎'],
                    ['name' => 'Bracelet / Bangle (ကင်းကြာ)', 'code' => 'BRC',  'icon' => '💍'],
                    ['name' => 'Ring (လက်စွပ်)',               'code' => 'RNG',  'icon' => '💍'],
                    ['name' => 'Watch (နာရီ)',                 'code' => 'WTC',  'icon' => '⌚'],
                    ['name' => 'Sunglasses (နေကာမျက်မှန်)',   'code' => 'SGL',  'icon' => '🕶️'],
                    ['name' => 'Hat / Cap (ဦးထုပ်)',          'code' => 'HAT',  'icon' => '🧢'],
                    ['name' => 'Scarf / Stole',                'code' => 'SCF',  'icon' => '🧣'],
                    ['name' => 'Hair Accessories (ဆံပင်)',     'code' => 'HAIR', 'icon' => '💇'],
                    ['name' => 'Belt (ခါးပတ်)',                'code' => 'BLT',  'icon' => '🔗'],
                ],
            ],
            [
                'name' => 'Innerwear / Lingerie (အတွင်းဝတ်)', 'code' => 'INN', 'icon' => '🩱',
                'subs' => [
                    ['name' => "Women's Innerwear (အမျိုးသမီးအတွင်းဝတ်)", 'code' => 'WIW', 'icon' => '🩱'],
                    ['name' => "Men's Innerwear (အမျိုးသားအတွင်းဝတ်)",    'code' => 'MIW', 'icon' => '🩳'],
                    ['name' => 'Sleepwear / Pajamas (အိပ်ဝတ်)',            'code' => 'SLW', 'icon' => '😴'],
                    ['name' => "Kids Innerwear (ကလေးအတွင်းဝတ်)",          'code' => 'KIW', 'icon' => '👶'],
                ],
            ],
            [
                'name' => 'Seasonal / Collection (ရာသီကာလ)', 'code' => 'SEA', 'icon' => '🌸',
                'subs' => [
                    ['name' => 'New Year Collection (နှစ်သစ်)',       'code' => 'NY',   'icon' => '🎊'],
                    ['name' => 'Summer Collection (နွေရာသီ)',          'code' => 'SUM',  'icon' => '☀️'],
                    ['name' => 'Thingyan / Water Festival (သင်္ကြန်)',  'code' => 'THG',  'icon' => '💦'],
                    ['name' => 'Wedding / Formal (မင်္ဂလာ)',            'code' => 'WED',  'icon' => '💒'],
                    ['name' => 'Sale / Clearance (လျှော့ဈေး)',          'code' => 'SALE', 'icon' => '🏷️'],
                ],
            ],
        ];
    }

    private function fashionConnectors(): array
    {
        return [
            ['code' => 'REG',  'name' => 'Regular Fit (သာမန်ဆစ်)',      'content' => 'Standard regular fit clothing'],
            ['code' => 'SLM',  'name' => 'Slim Fit (ပါးပါးဆစ်)',         'content' => 'Slim/fitted cut clothing'],
            ['code' => 'OVR',  'name' => 'Oversized (ကြီးကြီးကျယ်)',     'content' => 'Loose oversized style clothing'],
            ['code' => 'RLX',  'name' => 'Relaxed Fit (ပြေပြေညာညာ)',    'content' => 'Comfort relaxed cut'],
            ['code' => 'CTN',  'name' => 'Cotton (ကော်တွန်)',             'content' => '100% cotton fabric'],
            ['code' => 'PLY',  'name' => 'Polyester (ပိုလီ)',             'content' => 'Polyester fabric'],
            ['code' => 'SLK',  'name' => 'Silk (ပိုးထည်)',               'content' => 'Silk fabric'],
            ['code' => 'LNN',  'name' => 'Linen (နုနွောင်းစ)',            'content' => 'Linen fabric'],
            ['code' => 'DNM',  'name' => 'Denim (ဂျင်းပစ္စည်း)',          'content' => 'Denim/jeans material'],
            ['code' => 'CFN',  'name' => 'Chiffon (ရွဲ့ပတ်)',             'content' => 'Light chiffon fabric'],
            ['code' => 'ZPR',  'name' => 'Zipper Closure (ဇစ်)',          'content' => 'Zipper fastening'],
            ['code' => 'BTN',  'name' => 'Button Closure (ခလုပ်)',        'content' => 'Button fastening'],
            ['code' => 'PULL', 'name' => 'Pull-On / Elastic',             'content' => 'Elastic pull-on waistband'],
            ['code' => 'ORG',  'name' => 'Original Brand Label (မူရင်း)', 'content' => 'Genuine brand label product'],
        ];
    }

    private function fashionColors(): array
    {
        return [
            ['code' => 'BLK',  'name' => 'Black (အနက်)',              'color_hex' => '#0A0A0A', 'content' => 'Jet Black'],
            ['code' => 'WHT',  'name' => 'White (အဖြူ)',               'color_hex' => '#FAFAFA', 'content' => 'Pure White'],
            ['code' => 'OWHT', 'name' => 'Off-White / Ivory (နွေး)',   'color_hex' => '#F8F4E8', 'content' => 'Cream / Ivory'],
            ['code' => 'BGE',  'name' => 'Beige (ကြေ)',                'color_hex' => '#E8D5B7', 'content' => 'Warm Beige'],
            ['code' => 'CML',  'name' => 'Camel (ကြကျော်)',            'color_hex' => '#C19A6B', 'content' => 'Camel Tan'],
            ['code' => 'KHK',  'name' => 'Khaki (ကာကီ)',               'color_hex' => '#BDB76B', 'content' => 'Khaki Green-Tan'],
            ['code' => 'GRY',  'name' => 'Grey (မီးခိုး)',              'color_hex' => '#9E9E9E', 'content' => 'Mid Grey'],
            ['code' => 'LGRY', 'name' => 'Light Grey (မှုန်မီးခိုး)',   'color_hex' => '#D3D3D3', 'content' => 'Light Grey'],
            ['code' => 'DGRY', 'name' => 'Charcoal (မဲ့မီးခိုး)',       'color_hex' => '#36454F', 'content' => 'Charcoal Grey'],
            ['code' => 'BRN',  'name' => 'Brown (ညို)',                 'color_hex' => '#795548', 'content' => 'Warm Brown'],
            ['code' => 'CHOC', 'name' => 'Chocolate (ချောကလက်)',        'color_hex' => '#3D1C0B', 'content' => 'Deep Chocolate'],
            ['code' => 'RST',  'name' => 'Rust (သံချေးရောင်)',          'color_hex' => '#B7410E', 'content' => 'Rust Orange'],
            ['code' => 'NVY',  'name' => 'Navy (ရေပြာ)',                'color_hex' => '#1B2A4A', 'content' => 'Deep Navy Blue'],
            ['code' => 'RBL',  'name' => 'Royal Blue (ကြက်ကျ)',         'color_hex' => '#2563EB', 'content' => 'Royal Blue'],
            ['code' => 'SKY',  'name' => 'Sky Blue (မိုးကောင်းကင်)',    'color_hex' => '#87CEEB', 'content' => 'Sky Blue'],
            ['code' => 'DNM',  'name' => 'Denim Blue (ဂျင်းပြာ)',       'color_hex' => '#5B73A3', 'content' => 'Denim Wash Blue'],
            ['code' => 'RED',  'name' => 'Red (အနီ)',                   'color_hex' => '#E53935', 'content' => 'Bright Red'],
            ['code' => 'BRG',  'name' => 'Burgundy (စပျစ်ရင့်)',        'color_hex' => '#800020', 'content' => 'Deep Burgundy'],
            ['code' => 'PNK',  'name' => 'Pink (ပန်း)',                 'color_hex' => '#F48FB1', 'content' => 'Soft Pink'],
            ['code' => 'HPK',  'name' => 'Hot Pink (တောက်ပန်း)',        'color_hex' => '#FF69B4', 'content' => 'Hot Pink'],
            ['code' => 'BSH',  'name' => 'Blush / Rose (ပန်းညို)',      'color_hex' => '#E8B4B8', 'content' => 'Blush Rose'],
            ['code' => 'MVE',  'name' => 'Mauve (ခရမ်းပန်း)',           'color_hex' => '#C8A2C8', 'content' => 'Dusty Mauve'],
            ['code' => 'GRN',  'name' => 'Green (အစိမ်း)',              'color_hex' => '#4CAF50', 'content' => 'Bright Green'],
            ['code' => 'OLV',  'name' => 'Olive (သံပြာဝါ)',             'color_hex' => '#808000', 'content' => 'Olive Green'],
            ['code' => 'SGE',  'name' => 'Sage (မြက်ခင်း)',             'color_hex' => '#BCB88A', 'content' => 'Sage Green'],
            ['code' => 'MIN',  'name' => 'Mint (မန်ကျည်းစိမ်း)',         'color_hex' => '#98FF98', 'content' => 'Mint Green'],
            ['code' => 'PUR',  'name' => 'Purple / Violet (ခရမ်း)',     'color_hex' => '#7B1FA2', 'content' => 'Deep Purple'],
            ['code' => 'LVD',  'name' => 'Lavender (ဆိပ်ညို)',          'color_hex' => '#E6E6FA', 'content' => 'Soft Lavender'],
            ['code' => 'YLW',  'name' => 'Yellow (ဝါ)',                 'color_hex' => '#FFC107', 'content' => 'Golden Yellow'],
            ['code' => 'MST',  'name' => 'Mustard (မုတ်ဆိတ်ဝါ)',        'color_hex' => '#FFDB58', 'content' => 'Mustard Yellow'],
            ['code' => 'ORG',  'name' => 'Orange (လိမ္မော်)',            'color_hex' => '#FF6F00', 'content' => 'Burnt Orange'],
            ['code' => 'CRL',  'name' => 'Coral (ကျောက်ပန်း)',          'color_hex' => '#FF7F7F', 'content' => 'Coral Pink'],
            ['code' => 'EML',  'name' => 'Emerald (နက်ဖြိုးစိမ်း)',     'color_hex' => '#50C878', 'content' => 'Emerald Green'],
            ['code' => 'MLT',  'name' => 'Multicolor / Print',           'color_hex' => '#A78BFA', 'content' => 'Printed or Multicolor pattern'],
        ];
    }

    private function fashionShelves(): array
    {
        return [
            ['code' => 'FA',     'name' => "Zone A — Women's Clothing (A — အမျိုးသမီးဝတ်)",     'content' => 'Main floor zone A — womens clothing display'],
            ['code' => 'FB',     'name' => "Zone B — Men's Clothing (B — အမျိုးသားဝတ်)",        'content' => 'Main floor zone B — mens clothing display'],
            ['code' => 'FC',     'name' => 'Zone C — Kids Wear (C — ကလေးဝတ်)',                    'content' => 'Zone C — kids and baby clothing'],
            ['code' => 'FD',     'name' => 'Zone D — Footwear (D — ဖိနပ်)',                       'content' => 'Zone D — footwear display racks'],
            ['code' => 'FE',     'name' => 'Zone E — Bags & Accessories (E — အိတ်/ဆောင်)',        'content' => 'Zone E — handbags, accessories, jewelry'],
            ['code' => 'FHK',    'name' => 'Clothing Rail (ကြိုးချိတ်)',                           'content' => 'Hanging garment rail / clothes hanger rack'],
            ['code' => 'FWND',   'name' => 'Window Display (ဝင်းဒိုး)',                           'content' => 'Front window showcase / mannequin display'],
            ['code' => 'FMNN',   'name' => 'Mannequin Stand (ဖောင်)',                              'content' => 'In-store mannequin display stand'],
            ['code' => 'FSALE',  'name' => 'Sale Rack (လျှော့ဈေးစင်)',                            'content' => 'Clearance and sale items hanging rack'],
            ['code' => 'FBOX',   'name' => 'Footwear Display Box (ဖိနပ်ပြသောင်)',                 'content' => 'Footwear display shelf / shoe box rack'],
            ['code' => 'STK1',   'name' => 'Stockroom 1 — Regular Sizes (ဂိုဒေါင် — သာမန်ဆိုဒ်)', 'content' => 'Stockroom shelf for regular sizes S-XL'],
            ['code' => 'STK2',   'name' => 'Stockroom 2 — XL/Plus Sizes (ဂိုဒေါင် — ကြီးဆိုဒ်)',  'content' => 'Stockroom for XL-XXXL plus size stock'],
            ['code' => 'STK3',   'name' => 'Stockroom 3 — New Arrivals (ဂိုဒေါင် — သစ်)',          'content' => 'Stockroom for new arrivals awaiting display'],
            ['code' => 'ONLINE', 'name' => 'Online Stock Only (အွန်လိုင်း)',                      'content' => 'Virtual stock — online listings only'],
        ];
    }

    private function fashionWarranties(): array
    {
        return [
            ['code' => null, 'name' => 'No Warranty — Check Before Purchase (ဝယ်မှာ စစ်ဆေးပြီးမှ ဝယ်ပါ)',
                'content' => 'ဝတ်စောင်းများ ဝယ်ယူချိန်တွင် စစ်ဆေးပြီး ယူဆောင်ရမည်ဖြစ်ပြီး အာမခံ မပါရှိပါ။'],
            ['code' => null, 'name' => '7 Days Exchange — Factory Defect Only (၇ ရက် ချို့ယွင်းချက်လဲ)',
                'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း ချုပ်ချကွဲ/ဆိုးတောင် (Factory Defect) ဖြစ်ပါက တူပစ္စည်းနှင့် လဲပေးပါသည်။'],
            ['code' => null, 'name' => '14 Days Exchange — Defective Only (၁၄ ရက် ချို့ယွင်းမှ လဲ)',
                'content' => 'ဝယ်ပြီး ၁၄ ရက်အတွင်း ပစ္စည်းချို့ယွင်းမှု ဖြစ်ပါက စစ်ဆေးပြီး လဲပေးပါသည်။'],
            ['code' => null, 'name' => 'Alteration Service Included (ချုပ်ပြင် ဝန်ဆောင်မှုပါ)',
                'content' => 'ဝတ်စောင်းဝယ်ယူပါက သင့်ဆိုဒ်နှင့် ကိုက်ညီစေရန် ချုပ်ပြင်ဝန်ဆောင်မှု ပေးပါသည်။'],
            ['code' => null, 'name' => 'Footwear — 7 Days Defect Exchange (ဖိနပ် ၇ ရက် ချို့ယွင်းလဲ)',
                'content' => 'ဖိနပ်ဝယ်ပြီး ၇ ရက်အတွင်း ပစ္စည်းထုတ်လုပ်မှု ချို့ယွင်းပါက အသစ်လဲပေးပါသည်။'],
            ['code' => null, 'name' => 'Bags — 7 Days Quality Guarantee (အိတ် ၇ ရက် အရည်အသွေး)',
                'content' => 'အိတ်ဝယ်ပြီး ၇ ရက်အတွင်း ဇစ်ကျိုး/ကြိုးပြတ် ဖြစ်ပါက စစ်ဆေးပြီး ပြင်/လဲပေးပါသည်။'],
        ];
    }

    private function fashionReturnPolicies(): array
    {
        return [
            ['code' => null, 'name' => 'No Return / Exchange (ပြန်မလဲပေးပါ)',
                'content' => 'ဝယ်ယူပြီးသောနောက် ပြန်လဲ၊ ပြန်ရောင်းချ မခွင့်ပြုပါ။ ဝတ်ကြည့်ပြီးမှ ဆုံးဖြတ်ပါ။'],
            ['code' => null, 'name' => 'Exchange Same Item — Defect Only (ချို့ယွင်းမှသာ တူပစ္စည်းနှင့်လဲ)',
                'content' => 'ချို့ယွင်းချက် (ချုပ်ချကွဲ) ဖြစ်ပါကသာ တူညီသောဒီဇိုင်း/ဆိုဒ်ဖြင့် လဲပေးပါသည်။'],
            ['code' => null, 'name' => '7 Days Color/Size Exchange (၇ ရက် ဆိုဒ်/အရောင် လဲနိုင်)',
                'content' => 'ဝယ်ပြီး ၇ ရက်အတွင်း ဆိုဒ် သို့မဟုတ် အရောင် မကိုက်ပါက ဝတ်မကြည့်ဘဲ ပကတိ ဖြင့် လဲနိုင်ပါသည်။'],
            ['code' => null, 'name' => 'Sale Items — No Exchange (လျှော့ဈေး ပစ္စည်း ပြန်မလဲပေးပါ)',
                'content' => 'Sale / Clearance ဈေးဖြင့် ဝယ်ယူသောပစ္စည်းများ Factory Defect မဟုတ်ပါက ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Underwear / Lingerie — No Return (အတွင်းဝတ် ပြန်မလဲပေးပါ)',
                'content' => 'အတွင်းဝတ်ပစ္စည်းများ ကျန်းမာရေးကြောင့် မည်သည့်အကြောင်းကြောင့်မဆို ပြန်မလဲပေးပါ။'],
            ['code' => null, 'name' => 'Footwear — Exchange in Box Condition (ဖိနပ် ဘူးပကတိ နှင့်သာ လဲ)',
                'content' => 'ဖိနပ်ဘူး၊ ကတ် ပကတိ ဖြင့် ၇ ရက်အတွင်း လာပါက ဆိုဒ်/ဒီဇိုင်း လဲပေးပါသည်။'],
            ['code' => null, 'name' => 'Tailored/Altered Items — No Return (ချုပ်ပြင်ပြီး ပြန်မလဲပေးပါ)',
                'content' => 'ချုပ်ပြင်/ဆိုဒ်ချင်ပြင်ပြီးသောပစ္စည်းများ ပြန်မလဲပေးပါ။'],
        ];
    }

    private function fashionVariantPresets(): array
    {
        return [
            [
                'name' => 'Clothing Size — International (International ဆိုဒ်)',
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
                'name' => 'Clothing Size — Myanmar / Asian (မြန်မာဆိုဒ်)',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'Free Size (ဘယ်ဆိုဒ်မဆို)', 'sku_suffix' => 'FS',  'retail_price_adjustment' => 0,    'wholesale_price_adjustment' => 0,    'stock_status' => 'in_stock'],
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
                'name' => 'Shoe Size — EU (ဖိနပ်ဆိုဒ် EU)',
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
                'name' => 'Kids Size (ကလေးဆိုဒ်)',
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
                'name' => 'Jeans Waist Size — Inches (ဂျင်းခါးပတ်)',
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
                'name' => 'Fabric / Material (ပစ္စည်းအမျိုးအစား)',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'Cotton (ကော်တွန်)',            'sku_suffix' => 'CTN',  'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,     'stock_status' => 'in_stock'],
                    ['name' => 'Polyester (ပိုလီ)',             'sku_suffix' => 'PLY',  'retail_price_adjustment' => -2000, 'wholesale_price_adjustment' => -1500, 'stock_status' => 'in_stock'],
                    ['name' => 'Cotton-Poly Blend (ရောနှော)',  'sku_suffix' => 'CTPL', 'retail_price_adjustment' => 0,     'wholesale_price_adjustment' => 0,     'stock_status' => 'in_stock'],
                    ['name' => 'Denim (ဂျင်း)',                'sku_suffix' => 'DNM',  'retail_price_adjustment' => 3000,  'wholesale_price_adjustment' => 2000,  'stock_status' => 'in_stock'],
                    ['name' => 'Silk (ပိုးထည်)',               'sku_suffix' => 'SLK',  'retail_price_adjustment' => 10000, 'wholesale_price_adjustment' => 7000,  'stock_status' => 'in_stock'],
                    ['name' => 'Linen (နုနွောင်း)',             'sku_suffix' => 'LNN',  'retail_price_adjustment' => 5000,  'wholesale_price_adjustment' => 3500,  'stock_status' => 'in_stock'],
                    ['name' => 'Chiffon (ရွဲ့ပတ်)',             'sku_suffix' => 'CFN',  'retail_price_adjustment' => 4000,  'wholesale_price_adjustment' => 3000,  'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Fashion Colors (အဝတ်-အရောင်)',
                'category_family' => 'fashion',
                'options' => [
                    ['name' => 'Black (အနက်)',            'sku_suffix' => 'BLK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'White (အဖြူ)',             'sku_suffix' => 'WHT', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Navy (ရေပြာ)',             'sku_suffix' => 'NVY', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Beige (ကြေ)',              'sku_suffix' => 'BGE', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Grey (မီးခိုး)',            'sku_suffix' => 'GRY', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Pink (ပန်း)',              'sku_suffix' => 'PNK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Red (အနီ)',                'sku_suffix' => 'RED', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Green (အစိမ်း)',            'sku_suffix' => 'GRN', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Blue (အပြာ)',              'sku_suffix' => 'BLU', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Brown / Camel (ညို)',      'sku_suffix' => 'BRN', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Olive (သံပြာဝါ)',          'sku_suffix' => 'OLV', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Multicolor / Print (ဖျပ်)', 'sku_suffix' => 'MLT', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
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
            ['name' => 'Local Brand (ပြည်တွင်း Brand)', 'code' => 'LOCAL'],
            ['name' => 'No Brand / Generic (Brand မပါ)', 'code' => 'GEN'],
            ['name' => 'Import (တင်သွင်း)',              'code' => 'IMP'],
            ['name' => 'OEM',                            'code' => 'OEM'],
        ];
    }

    private function generalCategoryTree(): array
    {
        return [
            ['name' => 'General Product (သာမန်ကုန်ပစ္စည်း)', 'code' => 'GEN',  'icon' => '📦', 'subs' => []],
            ['name' => 'Food & Beverage (အစားအသောက်)',         'code' => 'FNB',  'icon' => '🍱', 'subs' => []],
            ['name' => 'Household (အိမ်သုံးပစ္စည်း)',          'code' => 'HH',   'icon' => '🏠', 'subs' => []],
            ['name' => 'Electronics (လျှပ်စစ်)',               'code' => 'ELEC', 'icon' => '🔌', 'subs' => []],
            ['name' => 'Service (ဝန်ဆောင်မှု)',                'code' => 'SVC',  'icon' => '🛠️', 'subs' => []],
        ];
    }

    private function generalConnectors(): array
    {
        return [
            ['code' => 'STD', 'name' => 'Standard',          'content' => 'Standard item'],
            ['code' => 'PRE', 'name' => 'Premium',            'content' => 'Premium quality'],
            ['code' => 'IMP', 'name' => 'Imported (တင်)',     'content' => 'Imported product'],
        ];
    }
}

