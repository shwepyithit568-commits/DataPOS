<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::where('slug', 'datapos-mobile')->first();
$paths = App\Models\Product::where('store_id', $store->id)->pluck('image_path')->unique();
echo "Total distinct image_paths: " . $paths->count() . "\n";
foreach ($paths->take(20) as $p) {
    echo " - " . ($p ?? 'NULL') . "\n";
}

$variantsWithImages = App\Models\ProductVariant::whereHas('product', fn($q) => $q->where('store_id', $store->id))
    ->whereNotNull('image_path')
    ->pluck('image_path')->unique();
echo "Variant image_paths: " . $variantsWithImages->count() . "\n";
foreach ($variantsWithImages->take(5) as $vp) {
    echo "  * Var: " . $vp . "\n";
}
