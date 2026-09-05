<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\File;

$store = Store::where('slug', 'datapos-mobile')->first();
if (!$store) {
    echo "Store datapos-mobile not found!\n";
    exit(1);
}

$products = Product::where('store_id', $store->id)->with('category')->get();
echo "Found {$products->count()} products for store {$store->name}...\n";

$countUpdated = 0;
foreach ($products as $p) {
    $catName = strtolower($p->category?->name ?? '');
    
    // Choose which of the 4 DATA PRODUCTS images fits best, or cycle by ID
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

    $newPath = "placeholders/data-product-{$imgNum}.webp";
    
    // Also overwrite the old target file if it existed, so cache doesn't serve old product name image
    if ($p->image_path && File::exists(public_path('storage/' . $p->image_path))) {
        File::copy(public_path("storage/{$newPath}"), public_path('storage/' . $p->image_path));
    }
    
    $p->update(['image_path' => $newPath]);
    $countUpdated++;
}

echo "Successfully updated {$countUpdated} products to DATA PRODUCTS clean 4-image suite!\n";

// Also update all other demo-stores products directory if present
$demoDir = public_path('storage/demo-stores/1/mobile-sale-service/products');
if (File::isDirectory($demoDir)) {
    $files = File::files($demoDir);
    echo "Syncing " . count($files) . " legacy demo files in {$demoDir}...\n";
    foreach ($files as $file) {
        $id = (int) pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $num = ($id % 4) + 1;
        File::copy(public_path("storage/placeholders/data-product-{$num}.webp"), $file->getRealPath());
    }
    echo "✓ Legacy demo files synced with DATA PRODUCTS images.\n";
}
