<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo Catalog Seeder — Linn IT Mart style sample data
 * =====================================================
 *
 * Adds two demo categories (ဖုန်း / Phone and ကွန်ပြူတာ / Computer) with a
 * handful of sample products so the Linn-style catalog layout can be seen
 * with realistic phone/computer items. This is DEMO data only.
 *
 * Safe to run multiple times (idempotent — uses firstOrCreate / updateOrCreate):
 *
 *   php artisan db:seed --class=DemoCatalogSeeder
 *
 * NOTE: This seeder is NOT wired into DatabaseSeeder (which stays empty by
 * design). Run it explicitly whenever you want the demo catalog back.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'datapos-mobile')->first()
            ?? Store::first();

        if (! $store) {
            $this->command?->error('No store found. Create a store first.');

            return;
        }

        $this->command?->info("🌱 Seeding demo catalog for store: {$store->name}...");

        $phone = $this->category($store, 'ဖုန်း', 'phone', '📱');
        $computer = $this->category($store, 'ကွန်ပြူတာ', 'computer', '💻');

        $brands = [
            'apple'   => $this->brand($store, 'Apple'),
            'samsung' => $this->brand($store, 'Samsung'),
            'xiaomi'  => $this->brand($store, 'Xiaomi'),
            'asus'    => $this->brand($store, 'ASUS'),
            'dell'    => $this->brand($store, 'Dell'),
            'lenovo'  => $this->brand($store, 'Lenovo'),
        ];

        // [name, slug, category, brand_key, retail_ks, wholesale_ks, stock, featured, warranty]
        $products = [
            // -- Phones (ဖုန်း) --
            ['iPhone 15 Pro Max 256GB',                       'iphone-15-pro-max-256gb',        $phone,    'apple',   6990000, 6500000, 'in_stock',    true,  'Official warranty 1 year'],
            ['Samsung Galaxy S24 Ultra 12/256GB',             'samsung-galaxy-s24-ultra',       $phone,    'samsung', 5290000, 4950000, 'in_stock',    true,  'Official warranty 1 year'],
            ['Xiaomi 14T 12/256GB 5G',                        'xiaomi-14t-12-256gb',            $phone,    'xiaomi',  1890000, 1720000, 'in_stock',    true,  'Official warranty 1 year'],
            ['POCO X6 Pro 12/512GB 5G',                       'poco-x6-pro-12-512gb',           $phone,    'xiaomi',  1490000, 1360000, 'in_stock',    false, 'Official warranty 1 year'],
            ['iPhone 13 128GB',                               'iphone-13-128gb',                $phone,    'apple',   3990000, 3700000, 'out_of_stock', false, 'Official warranty 1 year'],
            ['Samsung Galaxy A55 8/128GB',                    'samsung-galaxy-a55',             $phone,    'samsung', 1090000,  980000, 'in_stock',    false, 'Official warranty 1 year'],

            // -- Computers (ကွန်ပြူတာ) --
            ['MacBook Air 13" M3 8/256GB',                    'macbook-air-13-m3',              $computer, 'apple',   4290000, 3950000, 'in_stock',    true,  'Official warranty 1 year'],
            ['Dell Inspiron 15 3520 (i5-1235U, 8/512GB)',     'dell-inspiron-15-3520',          $computer, 'dell',    1890000, 1720000, 'in_stock',    true,  'Official warranty 1 year'],
            ['ASUS Vivobook 15 (i5-1240P, 16/512GB)',         'asus-vivobook-15',               $computer, 'asus',    2150000, 1980000, 'in_stock',    false, 'Official warranty 1 year'],
            ['Lenovo IdeaPad Slim 3 (R5-7530U, 8/512GB)',     'lenovo-ideapad-slim-3',          $computer, 'lenovo',  1650000, 1510000, 'in_stock',    false, 'Official warranty 1 year'],
            ['MacBook Pro 14" M4 Pro 24GB/1TB',               'macbook-pro-14-m4-pro',          $computer, 'apple',   7490000, 6900000, 'out_of_stock', false, 'Official warranty 1 year'],
            ['ASUS TUF Gaming F15 (i7-13620H, 16/512GB, RTX4060)', 'asus-tuf-gaming-f15',        $computer, 'asus',    4890000, 4500000, 'in_stock',    false, 'Official warranty 1 year'],
        ];

        foreach ($products as $i => $p) {
            $slug = $p[1];
            $existing = Product::where('store_id', $store->id)->where('slug', $slug)->first();

            $data = [
                'category_id'     => $p[2]->id,
                'brand_id'        => $brands[$p[3]]->id,
                'sku'             => strtoupper(substr($p[3], 0, 3)) . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'name'            => $p[0],
                'slug'            => $slug,
                'description'     => $p[0] . ' — ' . ($p[2]->name === 'ဖုန်း' ? 'စမတ်ဖုန်း' : 'ကွန်ပြူတာ') . ' (Demo Catalog). Original condition, nationwide delivery available.',
                'retail_price'    => $p[4],
                'wholesale_price' => $p[5],
                'stock_status'    => $p[6],
                'image_path'      => null,
                'warranty'        => $p[8],
                'return_policy'   => 'Return within 7 days if unopened.',
                'is_featured'     => $p[7],
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                Product::create(array_merge($data, [
                    'store_id' => $store->id,
                ]));
            }
        }

        $this->command?->info("✅ Demo catalog seeded: {$phone->name} + {$computer->name} categories, " . count($products) . ' products.');
    }

    private function category(Store $store, string $name, string $slug, string $icon): Category
    {
        return Category::firstOrCreate(
            ['store_id' => $store->id, 'slug' => $slug],
            ['name' => $name, 'icon' => $icon]
        );
    }

    private function brand(Store $store, string $name): Brand
    {
        return Brand::firstOrCreate(
            ['store_id' => $store->id, 'slug' => Str::slug($name)],
            ['name' => $name]
        );
    }
}
