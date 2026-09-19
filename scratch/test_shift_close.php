<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shift = \App\POS\Models\CashierShift::find(1);
echo "Shift 1 status: {$shift->status}\n";
echo "Shift 1 isOpen: " . ($shift->isOpen() ? 'true' : 'false') . "\n";

try {
    app(\App\POS\Services\CashierShiftService::class)->addCashEvent($shift, [
        'type' => 'cash_in',
        'amount' => '1000.00',
        'reason' => 'Test event on closed shift',
    ]);
    echo "No exception thrown!\n";
} catch (\Throwable $e) {
    echo "Caught: " . get_class($e) . " => " . $e->getMessage() . "\n";
}
