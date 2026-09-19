<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\CashierShift;

$shifts = CashierShift::all();
echo "Total shifts: " . $shifts->count() . "\n";
foreach ($shifts as $s) {
    echo "ID: {$s->id}, Store: {$s->store_id}, Cashier: {$s->cashier_id}, Status: {$s->status}, Opening: {$s->opening_cash}\n";
}
