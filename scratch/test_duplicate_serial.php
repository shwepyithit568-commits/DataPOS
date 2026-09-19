<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = \App\Models\Store::find(1);
$manager = \App\Models\User::where('phone', '09200000011')->first();

try {
    app(\App\POS\Services\WarrantyTrackerService::class)->register($store, [
        'product_id' => 3,
        'product_name' => 'Remax RPP-292',
        'serial_number' => 'RMX-2026-001',
        'warranty_duration_months' => 6,
        'purchase_date' => now()->toDateString(),
    ], $manager);
    echo "DUPLICATE_ACCEPTED\n";
} catch (\Illuminate\Database\QueryException $e) {
    echo "DUPLICATE_REJECTED_SQL: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "DUPLICATE_REJECTED: " . $e->getMessage() . "\n";
}
