<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\CashierShift;
use App\POS\Models\CashEvent;

$shift = CashierShift::where('store_id', 1)->latest('id')->first();
if ($shift) {
    echo "Shift ID: {$shift->id}\n";
    echo "Register: {$shift->register_name}\n";
    echo "Cashier ID: {$shift->cashier_id}\n";
    echo "Status: {$shift->status}\n";
    echo "Opening cash: {$shift->opening_cash}\n";
    echo "Opened at: {$shift->opened_at}\n";
} else {
    echo "No shift found!\n";
}

$events = CashEvent::where('cashier_shift_id', $shift?->id)->get();
echo "Cash events count: " . $events->count() . "\n";
foreach ($events as $e) {
    echo "Event: {$e->id} | Type: {$e->type} | Amount: {$e->amount} | Reason: {$e->reason}\n";
}
