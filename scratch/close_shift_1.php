<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\CashierShift;
use App\POS\Services\CashierShiftService;
use App\Models\User;

$shift = CashierShift::find(1);
$cashier = User::where('phone', '09200000022')->first();
$shiftService = app(CashierShiftService::class);

$closed = $shiftService->closeShift($shift, [
    'actual_closing_amount' => '203000.00',
    'notes' => 'Shift closed with zero variance verified.',
], $cashier);

echo "CashierShift 1 closed successfully!\n";
echo "Status: {$closed->status}\n";
echo "Expected: {$closed->expected_closing_amount}\n";
echo "Actual: {$closed->actual_closing_amount}\n";
echo "Difference: {$closed->difference}\n";
