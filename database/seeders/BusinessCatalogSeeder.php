<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\GlassFinderItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('BusinessCatalogSeeder is destructive and cannot run in production.');
        }

        $store = Store::where('slug', 'datapos-mobile')->first() ?? Store::first();

        if (! $store) {
            $this->command?->error('No store found. Create a store first.');

            return;
        }

        DB::transaction(function () use ($store) {
            $this->clearCatalog($store);

            $categories = $this->seedCategories($store);
            $brands = $this->seedBrands($store);
            $this->seedProducts($store, $categories, $brands);
            $this->seedGlassFinder($store);
        });

        $this->command?->info('Business catalog refreshed for store: ' . $store->name);
    }

    private function clearCatalog(Store $store): void
    {
        Product::where('store_id', $store->id)->delete();
        GlassFinderItem::where('store_id', $store->id)->delete();
        Category::where('store_id', $store->id)->delete();
        Brand::where('store_id', $store->id)->delete();
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(Store $store): array
    {
        $rows = [
            'mobile' => ['Mobile Phones', 'mobile', 'Smartphones, button phones, and official warranty handsets.', '📱'],
            'accessories' => ['Accessories', 'accessories', 'Cases, chargers, cables, earbuds, power banks, and protection items.', '🎧'],
            'cctv' => ['CCTV & Security', 'cctv-security', 'CCTV cameras, NVR kits, Wi-Fi cameras, storage, and security accessories.', '📹'],
            'computer' => ['Computer & Laptop', 'computer-laptop', 'Laptops, desktops, monitors, networking, storage, and computer accessories.', '💻'],
            'network' => ['Network & WiFi', 'network', 'Routers, WiFi extenders, switches, modems, and network accessories for home and office.', '🌐'],
            'fashion' => ['Fashion', 'fashion', 'Everyday clothing, bags, footwear, and lifestyle items.', '👕'],
        ];

        $categories = [];

        foreach ($rows as $key => [$name, $slug, $description, $icon]) {
            $categories[$key] = Category::create([
                'store_id' => $store->id,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'icon' => $icon,
            ]);
        }

        return $categories;
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrands(Store $store): array
    {
        $names = [
            'apple' => 'Apple',
            'samsung' => 'Samsung',
            'xiaomi' => 'Xiaomi',
            'oppo' => 'OPPO',
            'vivo' => 'Vivo',
            'anker' => 'Anker',
            'baseus' => 'Baseus',
            'ugreen' => 'UGREEN',
            'hikvision' => 'Hikvision',
            'dahua' => 'Dahua',
            'tplink' => 'TP-Link',
            'tenda' => 'Tenda',
            'mercusys' => 'Mercusys',
            'mikrotik' => 'MikroTik',
            'netis' => 'Netis',
            'dell' => 'Dell',
            'hp' => 'HP',
            'asus' => 'ASUS',
            'lenovo' => 'Lenovo',
            'logitech' => 'Logitech',
            'uniqlo' => 'Uniqlo',
            'adidas' => 'Adidas',
            'nike' => 'Nike',
            'local' => 'Local Select',
        ];

        $brands = [];

        foreach ($names as $key => $name) {
            $brands[$key] = Brand::create([
                'store_id' => $store->id,
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }

        return $brands;
    }

    /**
     * @param array<string, Category> $categories
     * @param array<string, Brand> $brands
     */
    private function seedProducts(Store $store, array $categories, array $brands): void
    {
        $products = [
            ['iPhone 15 Pro Max', 'mobile', 'apple', 5350000, 5120000, 5650000, true, [
                ['256GB Natural Titanium', 'APL-IP15PM-256-NT', 5350000, 5120000, 'in_stock', true],
                ['512GB Blue Titanium', 'APL-IP15PM-512-BL', 6150000, 5900000, 'in_stock', false],
                ['1TB Black Titanium', 'APL-IP15PM-1TB-BK', 7050000, 6750000, 'out_of_stock', false],
            ]],
            ['Samsung Galaxy S24 Ultra', 'mobile', 'samsung', 4850000, 4620000, 5150000, true, [
                ['12/256GB Titanium Gray', 'SAM-S24U-256-GY', 4850000, 4620000, 'in_stock', true],
                ['12/512GB Titanium Black', 'SAM-S24U-512-BK', 5450000, 5200000, 'in_stock', false],
            ]],
            ['Xiaomi 14T Pro 5G', 'mobile', 'xiaomi', 2180000, 2040000, 2350000, true, [
                ['12/256GB Titan Gray', 'XIA-14TP-256-GY', 2180000, 2040000, 'in_stock', true],
                ['12/512GB Titan Blue', 'XIA-14TP-512-BL', 2480000, 2320000, 'in_stock', false],
            ]],
            ['OPPO Reno 12 5G', 'mobile', 'oppo', 1380000, 1280000, 1490000, false, [
                ['12/256GB Astro Silver', 'OPP-R12-256-SV', 1380000, 1280000, 'in_stock', true],
                ['12/512GB Matte Brown', 'OPP-R12-512-BR', 1580000, 1460000, 'in_stock', false],
            ]],
            ['Vivo V40 5G', 'mobile', 'vivo', 1450000, 1340000, 1590000, false, [
                ['12/256GB Stellar Silver', 'VIV-V40-256-SV', 1450000, 1340000, 'in_stock', true],
                ['12/512GB Nebula Purple', 'VIV-V40-512-PP', 1680000, 1550000, 'out_of_stock', false],
            ]],
            ['Samsung Galaxy A55 5G', 'mobile', 'samsung', 980000, 910000, 1060000, true, [
                ['8/128GB Awesome Navy', 'SAM-A55-128-NV', 980000, 910000, 'in_stock', true],
                ['8/256GB Awesome Iceblue', 'SAM-A55-256-IB', 1120000, 1030000, 'in_stock', false],
            ]],
            ['Redmi Note 13 Pro', 'mobile', 'xiaomi', 820000, 760000, 890000, false, [
                ['8/256GB Midnight Black', 'XIA-RN13P-256-BK', 820000, 760000, 'in_stock', true],
                ['12/512GB Ocean Teal', 'XIA-RN13P-512-TL', 980000, 910000, 'in_stock', false],
            ]],
            ['iPhone 13', 'mobile', 'apple', 2980000, 2790000, 3180000, false, [
                ['128GB Starlight', 'APL-IP13-128-ST', 2980000, 2790000, 'in_stock', true],
                ['256GB Midnight', 'APL-IP13-256-MD', 3450000, 3220000, 'out_of_stock', false],
            ]],

            ['Anker 20W USB-C Charger', 'accessories', 'anker', 45000, 36000, 52000, true, [
                ['White', 'ANK-20W-WH', 45000, 36000, 'in_stock', true],
                ['Black', 'ANK-20W-BK', 45000, 36000, 'in_stock', false],
            ]],
            ['Baseus 10000mAh Power Bank', 'accessories', 'baseus', 98000, 82000, 115000, true, [
                ['20W Black', 'BAS-PB10-20-BK', 98000, 82000, 'in_stock', true],
                ['22.5W Purple', 'BAS-PB10-225-PP', 108000, 90000, 'in_stock', false],
            ]],
            ['UGREEN Type-C Cable', 'accessories', 'ugreen', 18000, 12000, null, false, [
                ['1M 60W', 'UGR-TC-1M-60', 18000, 12000, 'in_stock', true],
                ['2M 100W', 'UGR-TC-2M-100', 28000, 19000, 'in_stock', false],
            ]],
            ['iPhone 15 Pro Max Clear Case', 'accessories', 'local', 28000, 18000, 35000, true, [
                ['Clear', 'LOC-IP15PM-CASE-CL', 28000, 18000, 'in_stock', true],
                ['MagSafe Clear', 'LOC-IP15PM-CASE-MS', 45000, 30000, 'in_stock', false],
            ]],
            ['Samsung S24 Ultra Tempered Glass', 'accessories', 'local', 22000, 14000, null, false, [
                ['Clear 9H', 'LOC-S24U-TG-CL', 22000, 14000, 'in_stock', true],
                ['Privacy', 'LOC-S24U-TG-PR', 32000, 21000, 'in_stock', false],
            ]],
            ['Bluetooth TWS Earbuds', 'accessories', 'baseus', 125000, 102000, 145000, false, [
                ['White', 'BAS-TWS-WH', 125000, 102000, 'in_stock', true],
                ['Black', 'BAS-TWS-BK', 125000, 102000, 'out_of_stock', false],
            ]],
            ['Logitech Wireless Mouse M331', 'accessories', 'logitech', 65000, 54000, null, false, [
                ['Black', 'LOG-M331-BK', 65000, 54000, 'in_stock', true],
                ['Blue', 'LOG-M331-BL', 65000, 54000, 'in_stock', false],
            ]],

            ['Hikvision 4CH CCTV Kit', 'cctv', 'hikvision', 650000, 590000, 710000, true, [
                ['2MP 4 Camera Kit', 'HIK-KIT4-2MP', 650000, 590000, 'in_stock', true],
                ['5MP 4 Camera Kit', 'HIK-KIT4-5MP', 890000, 805000, 'in_stock', false],
            ]],
            ['Dahua 8CH DVR Package', 'cctv', 'dahua', 980000, 900000, 1080000, true, [
                ['2MP 6 Camera Set', 'DAH-DVR8-6C-2MP', 980000, 900000, 'in_stock', true],
                ['5MP 8 Camera Set', 'DAH-DVR8-8C-5MP', 1450000, 1330000, 'in_stock', false],
            ]],
            ['TP-Link Tapo C210 Wi-Fi Camera', 'cctv', 'tplink', 125000, 105000, null, false, [
                ['Single Camera', 'TPL-C210-1P', 125000, 105000, 'in_stock', true],
                ['Twin Pack', 'TPL-C210-2P', 238000, 198000, 'in_stock', false],
            ]],
            ['Hikvision 1TB Surveillance HDD', 'cctv', 'hikvision', 145000, 125000, 165000, false, [
                ['1TB', 'HIK-HDD-1TB', 145000, 125000, 'in_stock', true],
                ['2TB', 'HIK-HDD-2TB', 235000, 205000, 'in_stock', false],
            ]],
            ['Dahua Outdoor Bullet Camera', 'cctv', 'dahua', 92000, 76000, null, false, [
                ['2MP', 'DAH-BUL-2MP', 92000, 76000, 'in_stock', true],
                ['5MP', 'DAH-BUL-5MP', 145000, 123000, 'out_of_stock', false],
            ]],
            ['TP-Link Archer Router', 'network', 'tplink', 85000, 70000, null, false, [
                ['C54 AC1200', 'TPL-ARC-C54', 85000, 70000, 'in_stock', true],
                ['AX23 AX1800', 'TPL-ARC-AX23', 185000, 158000, 'in_stock', false],
            ]],
            ['TP-Link Archer AX55 AX3000', 'network', 'tplink', 265000, 228000, 299000, true, [
                ['AX3000 Standard', 'TPL-AX55-1P', 265000, 228000, 'in_stock', true],
                ['AX3000 + 4 Gigabit Ports', 'TPL-AX55-4P', 285000, 245000, 'in_stock', false],
            ]],
            ['Tenda AC10 AC1200 Router', 'network', 'tenda', 78000, 65000, 95000, true, [
                ['AC1200', 'TEN-AC10-1P', 78000, 65000, 'in_stock', true],
                ['AC1200 + 2M Cable', 'TEN-AC10-KIT', 88000, 72000, 'in_stock', false],
            ]],
            ['Mercusys MR70X AX3000', 'network', 'mercusys', 135000, 112000, 155000, false, [
                ['AX3000', 'MER-MR70X-1P', 135000, 112000, 'in_stock', true],
                ['AX3000 + Extender Bundle', 'MER-MR70X-2P', 178000, 148000, 'in_stock', false],
            ]],
            ['MikroTik hAP ac3', 'network', 'mikrotik', 145000, 125000, null, false, [
                ['hAP ac3 (Dual Band)', 'MIK-HAPAC3-1P', 145000, 125000, 'in_stock', true],
            ]],
            ['TP-Link WiFi Extender RE305', 'network', 'tplink', 52000, 43000, 65000, true, [
                ['AC1200 Extender', 'TPL-RE305-1P', 52000, 43000, 'in_stock', true],
                ['Twin Pack', 'TPL-RE305-2P', 98000, 80000, 'in_stock', false],
            ]],
            ['Netis Gigabit Switch', 'network', 'netis', 48000, 38000, null, false, [
                ['8-Port Gigabit', 'NET-SW8-1P', 48000, 38000, 'in_stock', true],
                ['16-Port Gigabit', 'NET-SW16-1P', 88000, 72000, 'out_of_stock', false],
            ]],
            ['Cat6 Ethernet Cable 20m', 'network', 'netis', 18000, 13000, null, false, [
                ['20m Blue', 'NET-CAT6-20-BL', 18000, 13000, 'in_stock', true],
                ['30m Blue', 'NET-CAT6-30-BL', 24000, 18000, 'in_stock', false],
            ]],

            ['MacBook Air 13 M3', 'computer', 'apple', 3980000, 3720000, 4250000, true, [
                ['8/256GB Midnight', 'APL-MBA13-M3-8-256', 3980000, 3720000, 'in_stock', true],
                ['16/512GB Silver', 'APL-MBA13-M3-16-512', 4980000, 4650000, 'in_stock', false],
            ]],
            ['Dell Inspiron 15 3530', 'computer', 'dell', 1680000, 1530000, 1850000, true, [
                ['i5/8/512GB', 'DEL-IN15-I5-8-512', 1680000, 1530000, 'in_stock', true],
                ['i7/16/512GB', 'DEL-IN15-I7-16-512', 2250000, 2050000, 'in_stock', false],
            ]],
            ['ASUS Vivobook 15 OLED', 'computer', 'asus', 2350000, 2150000, 2590000, false, [
                ['i5/16/512GB', 'ASU-VIVO-OLED-I5', 2350000, 2150000, 'in_stock', true],
                ['i7/16/1TB', 'ASU-VIVO-OLED-I7', 2980000, 2700000, 'out_of_stock', false],
            ]],
            ['Lenovo IdeaPad Slim 3', 'computer', 'lenovo', 1450000, 1320000, null, false, [
                ['Ryzen 5/8/512GB', 'LEN-IPS3-R5', 1450000, 1320000, 'in_stock', true],
                ['Ryzen 7/16/512GB', 'LEN-IPS3-R7', 1980000, 1800000, 'in_stock', false],
            ]],
            ['HP LaserJet Printer', 'computer', 'hp', 385000, 342000, 430000, false, [
                ['M111w', 'HP-LJ-M111W', 385000, 342000, 'in_stock', true],
                ['MFP M236dw', 'HP-LJ-M236DW', 685000, 620000, 'in_stock', false],
            ]],
            ['Logitech Keyboard Combo', 'computer', 'logitech', 105000, 88000, null, false, [
                ['MK220 Compact', 'LOG-MK220', 105000, 88000, 'in_stock', true],
                ['MK345 Full Size', 'LOG-MK345', 155000, 132000, 'in_stock', false],
            ]],
            ['Samsung 24 Inch Monitor', 'computer', 'samsung', 385000, 340000, null, true, [
                ['FHD 75Hz', 'SAM-MON24-FHD75', 385000, 340000, 'in_stock', true],
                ['FHD IPS 100Hz', 'SAM-MON24-IPS100', 465000, 410000, 'in_stock', false],
            ]],

            ['Uniqlo Cotton T-Shirt', 'fashion', 'uniqlo', 35000, 26000, null, true, [
                ['Black M', 'UNI-TEE-BK-M', 35000, 26000, 'in_stock', true],
                ['Black L', 'UNI-TEE-BK-L', 35000, 26000, 'in_stock', false],
                ['White M', 'UNI-TEE-WH-M', 35000, 26000, 'in_stock', false],
            ]],
            ['Adidas Daily Backpack', 'fashion', 'adidas', 125000, 98000, 145000, true, [
                ['Black 20L', 'ADI-BAG-20-BK', 125000, 98000, 'in_stock', true],
                ['Navy 25L', 'ADI-BAG-25-NV', 148000, 116000, 'in_stock', false],
            ]],
            ['Nike Running Shoes', 'fashion', 'nike', 245000, 210000, 275000, false, [
                ['EU 40 Black', 'NIK-RUN-BK-40', 245000, 210000, 'in_stock', true],
                ['EU 41 Black', 'NIK-RUN-BK-41', 245000, 210000, 'in_stock', false],
                ['EU 42 White', 'NIK-RUN-WH-42', 255000, 218000, 'out_of_stock', false],
            ]],
            ['Local Longyi Premium Cotton', 'fashion', 'local', 42000, 31000, null, false, [
                ['Blue Pattern', 'LOC-LGY-BL', 42000, 31000, 'in_stock', true],
                ['Green Pattern', 'LOC-LGY-GR', 42000, 31000, 'in_stock', false],
            ]],
            ['Women Crossbody Bag', 'fashion', 'local', 58000, 43000, 69000, false, [
                ['Brown', 'LOC-CBAG-BR', 58000, 43000, 'in_stock', true],
                ['Black', 'LOC-CBAG-BK', 58000, 43000, 'in_stock', false],
            ]],
            ['Men Casual Polo Shirt', 'fashion', 'local', 38000, 28000, null, false, [
                ['Navy M', 'LOC-POLO-NV-M', 38000, 28000, 'in_stock', true],
                ['Navy L', 'LOC-POLO-NV-L', 38000, 28000, 'in_stock', false],
                ['Gray XL', 'LOC-POLO-GY-XL', 40000, 30000, 'in_stock', false],
            ]],
        ];

        foreach ($products as $index => [$name, $categoryKey, $brandKey, $retail, $wholesale, $oldPrice, $featured, $variants]) {
            $product = Product::create([
                'store_id' => $store->id,
                'category_id' => $categories[$categoryKey]->id,
                'brand_id' => $brands[$brandKey]->id,
                'sku' => strtoupper(substr($categoryKey, 0, 3)) . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $this->descriptionFor($categoryKey, $name),
                'meta_description' => $name . ' available at ' . $store->name . ' with retail and wholesale pricing.',
                'retail_price' => $retail,
                'old_price' => $oldPrice,
                'wholesale_price' => $wholesale,
                'stock_status' => collect($variants)->contains(fn ($variant) => $variant[4] === 'in_stock') ? 'in_stock' : 'out_of_stock',
                'image_path' => null,
                'warranty' => $this->warrantyFor($categoryKey),
                'return_policy' => $this->returnPolicyFor($categoryKey),
                'is_featured' => $featured,
            ]);

            foreach ($variants as $sortOrder => [$variantName, $sku, $variantRetail, $variantWholesale, $stockStatus, $isDefault]) {
                $product->variants()->create([
                    'name' => $variantName,
                    'sku' => $sku,
                    'retail_price' => $variantRetail,
                    'wholesale_price' => $variantWholesale,
                    'stock_status' => $stockStatus,
                    'is_default' => $isDefault,
                    'sort_order' => $sortOrder,
                ]);
            }
        }
    }

    private function descriptionFor(string $categoryKey, string $name): string
    {
        return match ($categoryKey) {
            'mobile' => "{$name} with variant-based storage/color choices, retail and wholesale pricing, official warranty support, and Myanmar delivery.",
            'accessories' => "{$name} selected for daily mobile and computer use. Variants cover colors, size, length, or capacity where relevant.",
            'cctv' => "{$name} suitable for homes, shops, warehouses, and offices. Installation planning and bundle pricing can be confirmed by chat.",
            'computer' => "{$name} for school, office, design, and business use. Config variants help customers compare memory, storage, and performance.",
            'network' => "{$name} for home and office WiFi, gaming, and business networks. Speed, coverage, and port options can be confirmed by chat.",
            'fashion' => "{$name} with practical size/color variants for everyday Myanmar customers.",
            default => "{$name} available with selectable variants.",
        };
    }

    private function warrantyFor(string $categoryKey): string
    {
        return match ($categoryKey) {
            'mobile', 'computer', 'cctv' => 'Official/service warranty available. Terms depend on brand and supplier.',
            'network' => '12 months manufacturer warranty (excludes damage from misuse or power surges).',
            'accessories' => '7 to 30 days replacement for factory defects.',
            'fashion' => 'Exchange within 3 days if unused and tag attached.',
            default => 'Warranty depends on product condition.',
        };
    }

    private function returnPolicyFor(string $categoryKey): string
    {
        return match ($categoryKey) {
            'fashion' => 'Size exchange only if unused, clean, and original tag attached.',
            'cctv' => 'Return accepted only before installation and if packaging is complete.',
            'network' => 'Return within 7 days if unopened and seal intact.',
            default => 'Return within 7 days if unopened and unused.',
        };
    }

    private function seedGlassFinder(Store $store): void
    {
        $rows = [
            ['Apple', 'iPhone 15 Pro Max', 'IP15PM-F', 'in_stock'],
            ['Apple', 'iPhone 15 Pro', 'IP15P-F', 'in_stock'],
            ['Apple', 'iPhone 14 Pro Max', 'IP14PM-F', 'in_stock'],
            ['Apple', 'iPhone 13', 'IP13-F', 'in_stock'],
            ['Samsung', 'Galaxy S24 Ultra', 'S24U-F', 'in_stock'],
            ['Samsung', 'Galaxy S24', 'S24-F', 'in_stock'],
            ['Samsung', 'Galaxy A55', 'A55-F', 'in_stock'],
            ['Samsung', 'Galaxy A15', 'A15-F', 'out_of_stock'],
            ['Xiaomi', 'Redmi Note 13 Pro', 'RN13P-F', 'in_stock'],
            ['Xiaomi', 'Xiaomi 14T Pro', 'X14TP-F', 'in_stock'],
            ['OPPO', 'Reno 12 5G', 'OR12-F', 'in_stock'],
            ['OPPO', 'Reno 11', 'OR11-F', 'in_stock'],
            ['Vivo', 'V40 5G', 'VV40-F', 'in_stock'],
            ['Vivo', 'V30', 'VV30-F', 'in_stock'],
        ];

        foreach ($rows as [$brand, $model, $code, $stockStatus]) {
            GlassFinderItem::create([
                'store_id' => $store->id,
                'brand' => $brand,
                'phone_model' => $model,
                'glass_code' => $code,
                'stock_status' => $stockStatus,
            ]);
        }
    }
}
