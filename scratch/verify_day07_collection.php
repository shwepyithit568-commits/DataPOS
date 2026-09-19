<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\CashEvent;
use App\POS\Models\CashierShift;
use App\POS\Services\CustomerDebtService;

$entries = CustomerLedgerEntry::where('store_id', 1)->where('customer_id', 6)->get();
echo "Customer 6 entries count: " . $entries->count() . "\n";
foreach ($entries as $e) {
    echo "Entry: ID {$e->id} | Type: {$e->type} | Amount: {$e->amount} | Notes: {$e->notes}\n";
}

$debtService = app(CustomerDebtService::class);
$balance = $debtService->balanceFor(1, 6);
echo "Customer 6 Balance: {$balance} MMK (Expected: 89000.00 MMK)\n";

$events = CashEvent::where('store_id', 1)->get();
echo "Cash Events count: " . $events->count() . "\n";
foreach ($events as $e) {
    echo "Cash Event: ID {$e->id} | Shift {$e->cashier_shift_id} | Type {$e->type} | Amount {$e->amount} | Reason {$e->reason}\n";
}

$shift = CashierShift::find(1);
echo "Shift 1:\n";
echo " - Opening Cash: {$shift->opening_cash}\n";
echo " - Cash Sales: {$shift->cash_sales}\n";
echo " - Cash In: {$shift->cash_in}\n";
echo " - Cash Out: {$shift->cash_out}\n";

$drawer = bcadd(bcadd($shift->opening_cash, $shift->cash_sales, 2), $shift->cash_in, 2);
$drawer = bcsub($drawer, $shift->cash_out, 2);
echo "Current Calculated Drawer Cash: {$drawer} MMK (Expected: 210000.00 MMK)\n";
