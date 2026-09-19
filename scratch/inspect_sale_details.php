<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\PosSale;
use App\POS\Models\PosPayment;
use App\POS\Models\CashierShift;
use App\POS\Models\InventoryBalance;

$sale = PosSale::first();
echo "Sale attributes: " . json_encode($sale?->getAttributes()) . "\n";

$payment = PosPayment::first();
echo "Payment attributes: " . json_encode($payment?->getAttributes()) . "\n";

$balances = InventoryBalance::where('store_id', 1)->get();
foreach ($balances as $b) {
    echo "Balance attributes: " . json_encode($b->getAttributes()) . "\n";
}
