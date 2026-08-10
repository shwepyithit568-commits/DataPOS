<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\VariantPreset;
use Illuminate\Database\Seeder;

class VariantPresetSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'datapos-mobile')->first() ?? Store::first();

        if (! $store) {
            $this->command?->error('No store found. Create a store first.');

            return;
        }

        VariantPreset::where('store_id', $store->id)->delete();

        foreach ($this->presets() as $sortOrder => [$name, $family, $options]) {
            VariantPreset::create([
                'store_id' => $store->id,
                'name' => $name,
                'category_family' => $family,
                'options' => $options,
                'sort_order' => $sortOrder,
            ]);
        }

        $this->command?->info('Variant presets refreshed for store: ' . $store->name);
    }

    private function presets(): array
    {
        return [
            ['Mobile Storage', 'mobile', [
                $this->option('128GB', '128', 0, 0),
                $this->option('256GB', '256', 150000, 120000),
                $this->option('512GB', '512', 450000, 380000),
                $this->option('1TB', '1TB', 900000, 760000),
            ]],
            ['Phone Color', 'mobile', [
                $this->option('Black', 'BK', 0, 0),
                $this->option('White', 'WH', 0, 0),
                $this->option('Blue', 'BL', 0, 0),
                $this->option('Natural Titanium', 'NT', 0, 0),
            ]],
            ['Accessories Color', 'accessories', [
                $this->option('Black', 'BK', 0, 0),
                $this->option('White', 'WH', 0, 0),
                $this->option('Clear', 'CL', 0, 0),
                $this->option('Purple', 'PP', 0, 0),
            ]],
            ['CCTV Kit Size', 'cctv', [
                $this->option('2 Camera Kit', '2C', 0, 0),
                $this->option('4 Camera Kit', '4C', 350000, 300000),
                $this->option('8 Camera Kit', '8C', 800000, 700000),
            ]],
            ['Network Router Speed', 'network', [
                $this->option('AX1500', 'AX15', 0, 0),
                $this->option('AX3000', 'AX30', 85000, 72000),
                $this->option('AX5400', 'AX54', 145000, 125000),
            ]],
            ['Computer RAM / Storage', 'computer', [
                $this->option('8GB / 256GB', '8-256', 0, 0),
                $this->option('8GB / 512GB', '8-512', 250000, 210000),
                $this->option('16GB / 512GB', '16-512', 450000, 380000),
                $this->option('16GB / 1TB', '16-1TB', 750000, 640000),
            ]],
            ['Fashion Size', 'fashion', [
                $this->option('S', 'S', 0, 0),
                $this->option('M', 'M', 0, 0),
                $this->option('L', 'L', 0, 0),
                $this->option('XL', 'XL', 2000, 1500),
            ]],
            ['Fashion Color', 'fashion', [
                $this->option('Black', 'BK', 0, 0),
                $this->option('Navy', 'NV', 0, 0),
                $this->option('Gray', 'GY', 0, 0),
                $this->option('Brown', 'BR', 0, 0),
            ]],
        ];
    }

    private function option(string $name, string $skuSuffix, float $retailAdjustment, float $wholesaleAdjustment): array
    {
        return [
            'name' => $name,
            'sku_suffix' => $skuSuffix,
            'retail_price_adjustment' => $retailAdjustment,
            'wholesale_price_adjustment' => $wholesaleAdjustment,
            'stock_status' => 'in_stock',
        ];
    }
}
