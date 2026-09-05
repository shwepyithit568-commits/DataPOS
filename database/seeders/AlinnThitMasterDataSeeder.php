<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * AlinnThitMasterDataSeeder
 *
 * Delegates to the unified MasterDataSeedImporter engine to seed Tech Master Data Presets
 * (Brands, Categories, Presets, Variant Matrix) for the datapos-mobile store.
 */
class AlinnThitMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::whereIn('slug', ['datapos-mobile'])->get();

        if ($stores->isEmpty()) {
            $stores = Store::all();
        }

        $importer = app(MasterDataSeedImporter::class);

        foreach ($stores as $store) {
            $importer->importForStore(
                $store,
                ['brands', 'categories', 'connectors', 'colors', 'shelves', 'warranties', 'return_policies', 'variant_presets'],
                'tech'
            );
        }

        $this->command?->info('AlinnThitMasterDataSeeder: Master data imported via MasterDataSeedImporter.');
    }
}
