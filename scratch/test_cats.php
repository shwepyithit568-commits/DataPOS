<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::where('slug', 'datapos-mobile')->first();
$allCategories = App\Models\Category::where('store_id', $store->id)
    ->withCount('products')
    ->get();

foreach ($allCategories->whereNull('parent_id') as $p) {
    $subs = $allCategories->where('parent_id', $p->id);
    echo "Parent [{$p->id}]: {$p->name} (products: {$p->products_count}, subcategories: " . $subs->count() . ")\n";
    foreach ($subs as $s) {
        echo "   -> Child [{$s->id}]: {$s->name} (products: {$s->products_count})\n";
    }
}
