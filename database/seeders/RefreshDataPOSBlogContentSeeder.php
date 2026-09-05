<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * RefreshDataPOSBlogContentSeeder
 *
 * Wrapper seeder that seeds rich blog content for the DataPOS store (datapos-mobile)
 * using the unified BlogSeeder engine.
 */
class RefreshDataPOSBlogContentSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('slug', 'datapos-mobile')->first();

        if (! $store) {
            $this->command?->warn('RefreshDataPOSBlogContentSeeder: store "datapos-mobile" not found — skipping.');

            return;
        }

        $count = BlogSeeder::seedForStore($store);
        $this->command?->info("RefreshDataPOSBlogContentSeeder: {$count} blog articles seeded for store [{$store->slug}].");
    }
}
