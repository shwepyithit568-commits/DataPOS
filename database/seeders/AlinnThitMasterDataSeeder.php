<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Store;
use App\Models\VariantPreset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AlinnThitMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::whereIn('slug', ['datapos-mobile'])->get();

        if ($stores->isEmpty()) {
            $stores = Store::all();
        }

        foreach ($stores as $store) {
            $this->seedMasterDataForStore($store);
        }
    }

    public function seedMasterDataForStore(Store $store): void
    {
        DB::transaction(function () use ($store) {
            $this->seedBrands($store);
            $this->seedCategoryHierarchy($store);
            $this->seedVariantPresets($store);
            $this->seedProductMasterPresets($store);
        });
    }

    private function seedProductMasterPresets(Store $store): void
    {
        $presets = [
            // 1. Connectors & Specs
            ['type' => 'connector_spec', 'code' => 'TC', 'name' => 'Type-C', 'content' => 'USB Type-C interface'],
            ['type' => 'connector_spec', 'code' => 'MC', 'name' => 'Micro USB', 'content' => 'Micro USB standard interface'],
            ['type' => 'connector_spec', 'code' => 'IP', 'name' => 'Lightning / iPhone', 'content' => 'Apple 8-pin lightning connector'],
            ['type' => 'connector_spec', 'code' => '3.5MM', 'name' => '3.5mm Aux', 'content' => 'Standard audio jack connector'],
            ['type' => 'connector_spec', 'code' => '3IN1', 'name' => '3-in-1 Combo', 'content' => 'Multi-head Type-C + Micro + IP'],
            ['type' => 'connector_spec', 'code' => 'OTG', 'name' => 'OTG Adapter', 'content' => 'On-The-Go converter'],
            ['type' => 'connector_spec', 'code' => '10000MAH', 'name' => '10000mAh Capacity', 'content' => 'Power bank 10000mAh'],
            ['type' => 'connector_spec', 'code' => '20000MAH', 'name' => '20000mAh Capacity', 'content' => 'Power bank 20000mAh'],
            ['type' => 'connector_spec', 'code' => '30000MAH', 'name' => '30000mAh Capacity', 'content' => 'Power bank 30000mAh'],
            ['type' => 'connector_spec', 'code' => '20W', 'name' => '20W Fast Charge', 'content' => '20 Watt power delivery'],
            ['type' => 'connector_spec', 'code' => '33W', 'name' => '33W Super Charge', 'content' => '33 Watt super flash charge'],
            ['type' => 'connector_spec', 'code' => '65W', 'name' => '65W GaN Fast', 'content' => '65 Watt gallium nitride charger'],
            ['type' => 'connector_spec', 'code' => '100W', 'name' => '100W PD Ultra', 'content' => '100 Watt ultra fast power delivery'],
            ['type' => 'connector_spec', 'code' => 'ORG', 'name' => 'Original (မူရင်းအစစ်)', 'content' => 'Genuine manufacturer original'],
            ['type' => 'connector_spec', 'code' => 'AAA', 'name' => 'AAA Quality (အဆင့်မြင့်)', 'content' => 'High quality grade AAA replacement'],
            ['type' => 'connector_spec', 'code' => 'MA', 'name' => 'MA Quality', 'content' => 'Standard replacement grade'],
            ['type' => 'connector_spec', 'code' => 'OCA', 'name' => 'OCA Glass', 'content' => 'Optically clear adhesive glass'],
            ['type' => 'connector_spec', 'code' => 'SIL', 'name' => 'Silicone Case', 'content' => 'Soft silicone material'],
            ['type' => 'connector_spec', 'code' => 'CLR', 'name' => 'Clear Case', 'content' => 'Transparent protective cover'],

            // 2. Colors
            ['type' => 'color', 'code' => 'BLK', 'name' => 'Black (အနက်)', 'color_hex' => '#000000', 'content' => 'Jet Black'],
            ['type' => 'color', 'code' => 'WHT', 'name' => 'White (အဖြူ)', 'color_hex' => '#FFFFFF', 'content' => 'Pure White'],
            ['type' => 'color', 'code' => 'BLU', 'name' => 'Blue (အပြာ)', 'color_hex' => '#2563EB', 'content' => 'Ocean Blue'],
            ['type' => 'color', 'code' => 'RED', 'name' => 'Red (အနီ)', 'color_hex' => '#DC2626', 'content' => 'Ruby Red'],
            ['type' => 'color', 'code' => 'GLD', 'name' => 'Gold (ရွှေရောင်)', 'color_hex' => '#F59E0B', 'content' => 'Metallic Gold'],
            ['type' => 'color', 'code' => 'SLV', 'name' => 'Silver (ငွေရောင်)', 'color_hex' => '#94A3B8', 'content' => 'Metallic Silver'],
            ['type' => 'color', 'code' => 'GRY', 'name' => 'Gray (မီးခိုး)', 'color_hex' => '#64748B', 'content' => 'Space Gray'],
            ['type' => 'color', 'code' => 'PUR', 'name' => 'Purple (ခရမ်း)', 'color_hex' => '#9333EA', 'content' => 'Deep Purple'],
            ['type' => 'color', 'code' => 'GRN', 'name' => 'Green (အစိမ်း)', 'color_hex' => '#16A34A', 'content' => 'Emerald Green'],
            ['type' => 'color', 'code' => 'PNK', 'name' => 'Pink (ပန်းရောင်)', 'color_hex' => '#F472B6', 'content' => 'Rose Pink'],

            // 3. Shelf / Bin Locations
            ['type' => 'shelf_location', 'code' => 'A-01', 'name' => 'Shelf A1 (ရှေ့စင် အပေါ်ထပ်)', 'content' => 'Front Main Display Shelf Level 1'],
            ['type' => 'shelf_location', 'code' => 'A-02', 'name' => 'Shelf A2 (ရှေ့စင် အလယ်ထပ်)', 'content' => 'Front Main Display Shelf Level 2'],
            ['type' => 'shelf_location', 'code' => 'B-01', 'name' => 'Shelf B1 (ဘေးစင် အပေါ်ထပ်)', 'content' => 'Side Display Shelf Level 1'],
            ['type' => 'shelf_location', 'code' => 'B-02', 'name' => 'Shelf B2 (ဘေးစင် အောက်ထပ်)', 'content' => 'Side Display Shelf Level 2'],
            ['type' => 'shelf_location', 'code' => 'CTR-01', 'name' => 'Counter Glass (ကောင်တာ မှန်ပုံး)', 'content' => 'Main checkout glass showcase'],
            ['type' => 'shelf_location', 'code' => 'CAB-01', 'name' => 'Back Cabinet (နောက်ဘက် ဗီရို)', 'content' => 'Back storage wooden cabinet'],
            ['type' => 'shelf_location', 'code' => 'RP-01', 'name' => 'Repair Bench Rack (ပြင်ဆင်ရေး စင်)', 'content' => 'Service & Spare parts rack'],
            ['type' => 'shelf_location', 'code' => 'WH-RACK', 'name' => 'Warehouse Main Rack (ဂိုဒေါင် စင်ကြီး)', 'content' => 'Warehouse bulk storage area'],

            // 4. Warranty Presets
            ['type' => 'warranty', 'code' => null, 'name' => '7 Days Testing Warranty (၇ ရက် အစမ်းသုံး အာမခံ)', 'content' => 'ဝယ်ယူပြီး ၇ ရက်အတွင်း စက်ချို့ယွင်းချက် (Factory Defect) ဖြစ်ပေါ်ပါက အသစ်လဲလှယ်ပေးပါသည်။ ရေဝင်ခြင်း၊ ပြုတ်ကျကွဲရှခြင်းများ အကျုံးမဝင်ပါ။'],
            ['type' => 'warranty', 'code' => null, 'name' => '1 Month Service Warranty (၁ လ လက်ခမဲ့ ဝန်ဆောင်မှု)', 'content' => 'ဝယ်ယူပြီး ၁ လအတွင်း ပစ္စည်းစစ်ဆေးပြင်ဆင်ခ လက်ခအခမဲ့ ဝန်ဆောင်မှုပေးပါသည်။'],
            ['type' => 'warranty', 'code' => null, 'name' => '3 Months Official Warranty (၃ လ အာမခံ)', 'content' => '၃ လ အာမခံပါဝင်သည်။ စက်ပစ္စည်းချို့ယွင်းမှုအတွက် အခမဲ့စစ်ဆေး ပြုပြင်လဲလှယ်ပေးပါသည်။'],
            ['type' => 'warranty', 'code' => null, 'name' => '6 Months Service Warranty (၆ လ အာမခံ)', 'content' => '၆ လအတွင်း အာမခံကတ်ပြားဖြင့် တရားဝင်စစ်ဆေး ပြုပြင်ခွင့်ရရှိမည်ဖြစ်ပါသည်။'],
            ['type' => 'warranty', 'code' => null, 'name' => '1 Year Official Warranty (၁ နှစ် တရားဝင် အာမခံ)', 'content' => 'ကုမ္ပဏီ တရားဝင် ၁ နှစ် အာမခံ ပါဝင်သည်။'],
            ['type' => 'warranty', 'code' => null, 'name' => 'No Warranty (အာမခံမပါပါ)', 'content' => 'ပစ္စည်းကို ဝယ်ယူစဉ် ချက်ချင်း စစ်ဆေးပြီး ယူဆောင်ရမည်ဖြစ်ပြီး အာမခံ မပါရှိပါ။'],

            // 5. Return Policy Presets
            ['type' => 'return_policy', 'code' => null, 'name' => '7 Days Exchange & Return (၇ ရက်အတွင်း ဘူးအကောင်းအတိုင်း ပြန်လဲနိုင်)', 'content' => 'ပစ္စည်းဘူးခွံ၊ ဘားကုဒ်နှင့် ဆက်စပ်ပစ္စည်းများ အကောင်းပကတိအတိုင်း ရှိပါက ဝယ်ယူပြီး ၇ ရက်အတွင်း တန်ဖိုးတူ အခြားပစ္စည်းနှင့် လဲလှယ်နိုင်ပါသည်။'],
            ['type' => 'return_policy', 'code' => null, 'name' => 'Screen & Touch LCD Policy (မှန်ချပ်/Touch တပ်ဆင်ပြီးပါက ပြန်မလဲပေးပါ)', 'content' => 'Touch LCD နှင့် မှန်ချပ်များကို ဖုန်းတွင် ကော်မကပ်မီ အပြင်မှ ကြိုးထိုး စမ်းသပ်ပေးရပါမည်။ တံဆိပ်တုံးပျက်စီးခြင်း၊ ကော်ကပ်ပြီးခြင်း၊ ဖလင်ခွာပြီးပါက ပြန်လဲမပေးပါ။'],
            ['type' => 'return_policy', 'code' => null, 'name' => 'Standard Accessories Policy (ချို့ယွင်းချက်ရှိပါက အသစ်လဲပေးသည်)', 'content' => 'ကြိုး၊ အားသွင်းခေါင်း စသည့် Accessories များ ပျက်စီးချို့ယွင်းပါက ဝယ်ယူသည့် ဘောက်ချာပြသ၍ အသစ်လဲလှယ်နိုင်ပါသည်။'],
        ];

        foreach ($presets as $idx => $p) {
            \App\Models\ProductMasterPreset::updateOrCreate(
                ['store_id' => $store->id, 'type' => $p['type'], 'name' => $p['name']],
                [
                    'code' => $p['code'] ?? null,
                    'color_hex' => $p['color_hex'] ?? null,
                    'content' => $p['content'] ?? null,
                    'sort_order' => $idx,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedBrands(Store $store): void
    {
        $brands = [
            ['name' => '168 Quality', 'code' => '168'],
            ['name' => 'Bavinto', 'code' => 'BVT'],
            ['name' => 'Denmen', 'code' => 'DENMEN'],
            ['name' => 'AB Quality', 'code' => 'AB'],
            ['name' => 'Huawei', 'code' => 'HW'],
            ['name' => 'Redmi / Xiaomi', 'code' => 'RM'],
            ['name' => 'Samsung', 'code' => 'SAM'],
            ['name' => 'Apple / iPhone', 'code' => 'APL'],
            ['name' => 'OPPO', 'code' => 'OP'],
            ['name' => 'Vivo', 'code' => 'VV'],
            ['name' => 'Realme', 'code' => 'RL'],
            ['name' => 'YK Audio', 'code' => 'YK'],
            ['name' => 'UW Universal', 'code' => 'UW'],
            ['name' => 'Car Universal', 'code' => 'CAR'],
            ['name' => 'Remax', 'code' => 'REMAX'],
            ['name' => 'Hoco', 'code' => 'HOCO'],
            ['name' => 'Joyroom', 'code' => 'JOYROOM'],
            ['name' => 'Baseus', 'code' => 'BASEUS'],
            ['name' => 'Ugreen', 'code' => 'UGREEN'],
            ['name' => 'Anker', 'code' => 'ANKER'],
            ['name' => 'Kingston', 'code' => 'KST'],
            ['name' => 'Orico', 'code' => 'ORICO'],
            ['name' => 'Hikvision', 'code' => 'HIK'],
            ['name' => 'Dahua', 'code' => 'DAHUA'],
        ];

        foreach ($brands as $b) {
            $slug = Str::slug($b['name']) . '-' . $store->id;
            Brand::updateOrCreate(
                ['store_id' => $store->id, 'name' => $b['name']],
                ['code' => $b['code'], 'slug' => $slug]
            );
        }
    }

    private function seedCategoryHierarchy(Store $store): void
    {
        $tree = [
            [
                'name' => 'Cable & Charger',
                'code' => 'CBCH',
                'icon' => '🔌',
                'subs' => [
                    ['name' => 'Cable', 'code' => 'CB', 'icon' => '🔌'],
                    ['name' => 'Charger', 'code' => 'CH', 'icon' => '⚡'],
                    ['name' => 'Charger Set', 'code' => 'CHS', 'icon' => '🔌'],
                    ['name' => 'Car Charger', 'code' => 'CCH', 'icon' => '🚗'],
                ],
            ],
            [
                'name' => 'Audio & Sound',
                'code' => 'AUD',
                'icon' => '🎧',
                'subs' => [
                    ['name' => 'Earphone', 'code' => 'EP', 'icon' => '🎧'],
                    ['name' => 'Bluetooth Earphone', 'code' => 'BEP', 'icon' => '📶'],
                    ['name' => 'Bluetooth Speaker', 'code' => 'SPK', 'icon' => '🔊'],
                    ['name' => 'Microphone', 'code' => 'MIC', 'icon' => '🎙️'],
                ],
            ],
            [
                'name' => 'Power & Storage',
                'code' => 'PWR',
                'icon' => '🔋',
                'subs' => [
                    ['name' => 'Power Bank', 'code' => 'PB', 'icon' => '🔋'],
                    ['name' => 'Memory Card', 'code' => 'SD', 'icon' => '💾'],
                    ['name' => 'USB Flash Drive', 'code' => 'USB', 'icon' => '💾'],
                ],
            ],
            [
                'name' => 'Screen & LCD',
                'code' => 'SCR',
                'icon' => '📱',
                'subs' => [
                    ['name' => 'Touch LCD', 'code' => 'TL', 'icon' => '📱'],
                    ['name' => 'Original Touch LCD', 'code' => 'TL_ORG', 'icon' => '📱'],
                    ['name' => 'Touch Screen', 'code' => 'TS', 'icon' => '📱'],
                    ['name' => 'Front Glass', 'code' => 'GLS', 'icon' => '🛡️'],
                    ['name' => 'OCA Glass', 'code' => 'OCA', 'icon' => '🛡️'],
                    ['name' => 'Screen Protector', 'code' => 'SG', 'icon' => '🛡️'],
                ],
            ],
            [
                'name' => 'Body & Back Cover',
                'code' => 'BC_GRP',
                'icon' => '📱',
                'subs' => [
                    ['name' => 'Back Cover', 'code' => 'BC', 'icon' => '📱'],
                    ['name' => 'Body Frame', 'code' => 'BD', 'icon' => '🗜️'],
                ],
            ],
            [
                'name' => 'Battery',
                'code' => 'BT_GRP',
                'icon' => '🔋',
                'subs' => [
                    ['name' => 'Original Battery', 'code' => 'BT_ORG', 'icon' => '🔋'],
                    ['name' => 'Standard Battery', 'code' => 'BT_STD', 'icon' => '🔋'],
                ],
            ],
            [
                'name' => 'Phone Accessories',
                'code' => 'ACC',
                'icon' => '🛡️',
                'subs' => [
                    ['name' => 'Phone Case', 'code' => 'CV', 'icon' => '🛡️'],
                    ['name' => 'Phone Holder', 'code' => 'HLD', 'icon' => '🧲'],
                    ['name' => 'Waterproof Pouch', 'code' => 'WTP', 'icon' => '💧'],
                    ['name' => 'Sticker', 'code' => 'STK', 'icon' => '✨'],
                ],
            ],
            [
                'name' => 'Phone Spare Parts',
                'code' => 'PRT',
                'icon' => '🔧',
                'subs' => [
                    ['name' => 'Charging Port', 'code' => 'USBFIX', 'icon' => '🔧'],
                    ['name' => 'Power Switch', 'code' => 'PWRSW', 'icon' => '🔘'],
                    ['name' => 'Camera Lens', 'code' => 'LENS', 'icon' => '📷'],
                    ['name' => 'Flex Ribbon Cable', 'code' => 'FLEX', 'icon' => '🧰'],
                    ['name' => 'Other Spare Parts', 'code' => 'SPARE', 'icon' => '🔧'],
                ],
            ],
            [
                'name' => 'CCTV & Network',
                'code' => 'CCTV',
                'icon' => '📹',
                'subs' => [
                    ['name' => 'CCTV Camera', 'code' => 'CAM', 'icon' => '📹'],
                    ['name' => 'CCTV Accessory', 'code' => 'CCTVACC', 'icon' => '🔌'],
                    ['name' => 'WiFi Router & Switch', 'code' => 'NET', 'icon' => '🌐'],
                ],
            ],
            [
                'name' => 'Computer & Electronics',
                'code' => 'ELEC',
                'icon' => '💻',
                'subs' => [
                    ['name' => 'Mouse', 'code' => 'MOU', 'icon' => '🖱️'],
                    ['name' => 'Keyboard', 'code' => 'KBD', 'icon' => '⌨️'],
                    ['name' => 'LED Light & Fan', 'code' => 'LED', 'icon' => '💡'],
                ],
            ],
        ];

        foreach ($tree as $parentItem) {
            $parentSlug = Str::slug($parentItem['name']) . '-' . $store->id;
            $parent = Category::updateOrCreate(
                ['store_id' => $store->id, 'name' => $parentItem['name']],
                [
                    'code' => $parentItem['code'],
                    'icon' => $parentItem['icon'],
                    'slug' => $parentSlug,
                    'parent_id' => null,
                ]
            );

            foreach ($parentItem['subs'] as $subItem) {
                $subSlug = Str::slug($subItem['name']) . '-' . $store->id . '-' . $parent->id;
                Category::updateOrCreate(
                    ['store_id' => $store->id, 'name' => $subItem['name'], 'parent_id' => $parent->id],
                    [
                        'code' => $subItem['code'],
                        'icon' => $subItem['icon'],
                        'slug' => $subSlug,
                    ]
                );
            }
        }
    }

    private function seedVariantPresets(Store $store): void
    {
        $presets = [
            [
                'name' => 'Connector Types (ကြိုးခေါင်း အမျိုးအစား)',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => 'Type-C', 'sku_suffix' => 'TC', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Micro USB', 'sku_suffix' => 'MC', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Lightning / iPhone', 'sku_suffix' => 'IP', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '3.5mm Aux', 'sku_suffix' => '3.5MM', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '3-in-1 Multi', 'sku_suffix' => '3IN1', 'retail_price_adjustment' => 1500, 'wholesale_price_adjustment' => 1000, 'stock_status' => 'in_stock'],
                    ['name' => 'OTG Adapter', 'sku_suffix' => 'OTG', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Colors (အရောင်များ)',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => 'Black (အနက်)', 'sku_suffix' => 'BLK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'White (အဖြူ)', 'sku_suffix' => 'WHT', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Blue (အပြာ)', 'sku_suffix' => 'BLU', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Red (အနီ)', 'sku_suffix' => 'RED', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Gold (ရွှေ)', 'sku_suffix' => 'GLD', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Silver (ငွေ)', 'sku_suffix' => 'SLV', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Gray (မီးခိုး)', 'sku_suffix' => 'GRY', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Purple (ခရမ်း)', 'sku_suffix' => 'PUR', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Green (အစိမ်း)', 'sku_suffix' => 'GRN', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'Pink (ပန်းရောင်)', 'sku_suffix' => 'PNK', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Power Bank Capacity (ပါဝါဘဏ် ပမာဏ)',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => '10000mAh', 'sku_suffix' => '10000mAh', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '20000mAh', 'sku_suffix' => '20000mAh', 'retail_price_adjustment' => 12000, 'wholesale_price_adjustment' => 9000, 'stock_status' => 'in_stock'],
                    ['name' => '30000mAh', 'sku_suffix' => '30000mAh', 'retail_price_adjustment' => 25000, 'wholesale_price_adjustment' => 20000, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Memory Storage (SD/USB/Storage)',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => '32GB', 'sku_suffix' => '32GB', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '64GB', 'sku_suffix' => '64GB', 'retail_price_adjustment' => 6000, 'wholesale_price_adjustment' => 4500, 'stock_status' => 'in_stock'],
                    ['name' => '128GB', 'sku_suffix' => '128GB', 'retail_price_adjustment' => 15000, 'wholesale_price_adjustment' => 12000, 'stock_status' => 'in_stock'],
                    ['name' => '256GB', 'sku_suffix' => '256GB', 'retail_price_adjustment' => 35000, 'wholesale_price_adjustment' => 28000, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Charging Power (ဝပ်အား)',
                'category_family' => 'accessories',
                'options' => [
                    ['name' => '20W Fast Charge', 'sku_suffix' => '20W', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => '33W Super Charge', 'sku_suffix' => '33W', 'retail_price_adjustment' => 4000, 'wholesale_price_adjustment' => 3000, 'stock_status' => 'in_stock'],
                    ['name' => '65W GaN Fast Charge', 'sku_suffix' => '65W', 'retail_price_adjustment' => 15000, 'wholesale_price_adjustment' => 11000, 'stock_status' => 'in_stock'],
                    ['name' => '100W PD Ultra Fast', 'sku_suffix' => '100W', 'retail_price_adjustment' => 25000, 'wholesale_price_adjustment' => 18000, 'stock_status' => 'in_stock'],
                ],
            ],
            [
                'name' => 'Quality Level (အရည်အသွေး အဆင့်)',
                'category_family' => 'mobile',
                'options' => [
                    ['name' => 'Original (မူရင်းအစစ်)', 'sku_suffix' => 'ORG', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                    ['name' => 'AAA Quality (အဆင့်မြင့်)', 'sku_suffix' => 'AAA', 'retail_price_adjustment' => -10000, 'wholesale_price_adjustment' => -7000, 'stock_status' => 'in_stock'],
                    ['name' => 'MA Quality (သာမန်အဆင့်)', 'sku_suffix' => 'MA', 'retail_price_adjustment' => -18000, 'wholesale_price_adjustment' => -14000, 'stock_status' => 'in_stock'],
                    ['name' => 'OCA Glass', 'sku_suffix' => 'OCA', 'retail_price_adjustment' => 0, 'wholesale_price_adjustment' => 0, 'stock_status' => 'in_stock'],
                ],
            ],
        ];

        foreach ($presets as $sortOrder => $preset) {
            VariantPreset::updateOrCreate(
                ['store_id' => $store->id, 'name' => $preset['name']],
                [
                    'category_family' => $preset['category_family'],
                    'options' => $preset['options'],
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }
}
