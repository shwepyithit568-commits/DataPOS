<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\DeviceWarranty;

$warranties = DeviceWarranty::where('store_id', 1)->get();
echo "Device warranties count: " . $warranties->count() . "\n";
foreach ($warranties as $w) {
    echo "ID: {$w->id} | Serial: {$w->serial_number} | Product: {$w->product_id} | Status: {$w->status} | Invoice: {$w->pos_sale_id}\n";
}
