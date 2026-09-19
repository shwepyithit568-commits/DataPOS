<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== purchase_orders columns ===\n";
dump(\Illuminate\Support\Facades\Schema::getColumnListing('purchase_orders'));

echo "\n=== product_master_presets ===\n";
dump(\DB::table('product_master_presets')->get());

echo "\n=== PO 1 ===\n";
dump(\DB::table('purchase_orders')->find(1));
