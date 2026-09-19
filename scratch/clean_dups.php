<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\DeviceWarranty;

$dups = DeviceWarranty::where('store_id', 1)->where('serial_number', 'RMX-2026-001')->get();
if ($dups->count() > 1) {
    foreach ($dups->slice(1) as $d) {
        $d->delete();
    }
}
echo "Device Warranties Count: " . DeviceWarranty::where('store_id', 1)->count() . "\n";
