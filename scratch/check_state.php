<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Database: " . \DB::getDatabaseName() . "\n";

$tables = [
    'cashier_shifts',
    'pos_sales',
    'pos_payments',
    'pos_returns',
    'expenses',
    'cash_events',
    'service_jobs',
    'customer_ledger_entries',
    'daily_closings',
    'inventory_balances'
];

foreach ($tables as $table) {
    echo "\n=== $table ===\n";
    $rows = \DB::table($table)->get();
    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
