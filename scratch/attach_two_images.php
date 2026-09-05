<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

$stores = Store::all();
echo "Found {$stores->count()} stores...\n";

$totalUpdated = 0;
$totalImagesCreated = 0;

DB::transaction(function () use ($stores, &$totalUpdated, &$totalImagesCreated) {
    foreach ($stores as $store) {
        $products = Product::where('store_id', $store->id)->with(['category', 'images'])->get();
        echo "Processing Store: [{$store->slug}] ({$products->count()} products)...\n";

        foreach ($products as $p) {
            $catName = strtolower($p->category?->name ?? '');

            if (str_contains($catName, 'phone') || str_contains($catName, 'tablet') || str_contains($catName, 'mobile')) {
                $imgNum = 1;
            } elseif (str_contains($catName, 'charger') || str_contains($catName, 'cable') || str_contains($catName, 'power') || str_contains($catName, 'battery')) {
                $imgNum = 2;
            } elseif (str_contains($catName, 'audio') || str_contains($catName, 'sound') || str_contains($catName, 'earphone') || str_contains($catName, 'digital') || str_contains($catName, 'gift')) {
                $imgNum = 3;
            } elseif (str_contains($catName, 'case') || str_contains($catName, 'cover') || str_contains($catName, 'screen') || str_contains($catName, 'glass') || str_contains($catName, 'repair') || str_contains($catName, 'cctv') || str_contains($catName, 'service')) {
                $imgNum = 4;
            } else {
                $imgNum = ($p->id % 4) + 1;
            }

            $mainImagePath = "placeholders/data-product-{$imgNum}.webp";
            $adImagePath = "placeholders/datapos-software-ad-{$imgNum}.webp";

            // Update main product image_path
            $p->image_path = $mainImagePath;
            $p->save();

            // Clear old placeholder images from product_images table to prevent duplicates
            ProductImage::where('product_id', $p->id)->delete();

            // Insert 2 images: [Main Data Product] + [Software Ad]
            ProductImage::create([
                'product_id' => $p->id,
                'image_path' => $mainImagePath,
                'is_primary' => 1,
                'sort_order' => 1,
            ]);

            ProductImage::create([
                'product_id' => $p->id,
                'image_path' => $adImagePath,
                'is_primary' => 0,
                'sort_order' => 2,
            ]);

            $totalUpdated++;
            $totalImagesCreated += 2;
        }
    }
});

echo "\n=== DONE ===\n";
echo "Total Products Updated: {$totalUpdated}\n";
echo "Total Product Images Created: {$totalImagesCreated} (2 per product)\n";
