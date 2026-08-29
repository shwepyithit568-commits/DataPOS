<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class StoreDataExportService
{
    /**
     * Export complete store data archive as structured array.
     *
     * @param Store $store
     * @return array<string, mixed>
     */
    public function exportStoreArchive(Store $store): array
    {
        $setting = $store->setting;

        return [
            'store_metadata' => [
                'id'                => $store->id,
                'name'              => $store->name,
                'slug'              => $store->slug,
                'business_profile'  => $store->business_profile,
                'subscription_tier' => $store->subscription_tier,
                'exported_at'       => now()->toIso8601String(),
                'version'           => 'DataPOS 2.0 GDPR Portability Package',
            ],
            'storefront_settings' => $setting ? [
                'phone'             => $setting->phone,
                'viber_number'      => $setting->viber_number,
                'telegram_username' => $setting->telegram_username,
                'address'           => $setting->address,
                'theme_preset'      => $setting->theme_preset,
                'font_preset'       => $setting->font_preset,
                'grid_density'      => $setting->grid_density,
            ] : null,
            'categories' => Category::where('store_id', $store->id)
                ->select(['id', 'name', 'slug', 'description', 'icon'])
                ->get()
                ->toArray(),
            'brands' => Brand::where('store_id', $store->id)
                ->select(['id', 'name', 'slug'])
                ->get()
                ->toArray(),
            'products' => Product::where('store_id', $store->id)
                ->with(['variants', 'category', 'brand'])
                ->get()
                ->map(fn (Product $p) => [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'sku'             => $p->sku,
                    'retail_price'    => (float) $p->retail_price,
                    'wholesale_price' => (float) $p->wholesale_price,
                    'buy_price'       => (float) $p->buy_price,
                    'category'        => $p->category?->name,
                    'brand'           => $p->brand?->name,
                    'is_active'       => (bool) $p->is_active,
                    'variants'        => $p->variants->map(fn ($v) => [
                        'name'            => $v->name,
                        'sku'             => $v->sku,
                        'retail_price'    => (float) ($v->retail_price ?? 0),
                        'wholesale_price' => (float) ($v->wholesale_price ?? 0),
                        'buy_price'       => (float) ($v->buy_price ?? 0),
                    ])->toArray(),
                ])
                ->toArray(),
            'customers_summary' => [
                'total_count' => $store->users()->wherePivotIn('role', ['customer', 'retail_customer', 'wholesale_customer'])->count(),
            ],
            'sales_summary' => [
                'total_sales_count' => \App\POS\Models\PosSale::where('store_id', $store->id)->count(),
                'total_revenue'     => (float) \App\POS\Models\PosSale::where('store_id', $store->id)->sum('final_total'),
            ],
        ];
    }
}
