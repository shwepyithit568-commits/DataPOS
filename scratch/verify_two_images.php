<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Store;

$store = Store::where('slug', 'datapos-mobile')->first();

$sampleQueries = ['%iPhone%', '%Anker%', '%AirPods%', '%Remax%'];

foreach ($sampleQueries as $query) {
    $p = Product::where('store_id', $store->id)->where('name', 'like', $query)->first();
    if ($p) {
        echo "Product: {$p->name} (Category: " . ($p->category?->name ?? 'None') . ")\n";
        echo "  Primary: {$p->image_path}\n";
        echo "  All Images (" . count($p->all_image_paths) . "):\n";
        foreach ($p->all_image_paths as $idx => $img) {
            echo "    [{$idx}] {$img}\n";
        }
        echo "----------------------------------------\n";
    }
}
