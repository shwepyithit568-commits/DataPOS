<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\PosSale;
use App\POS\Models\PosPayment;
use App\POS\Models\CashierShift;
use App\POS\Models\InventoryBalance;
use App\POS\Models\DeviceWarranty;

$sale = PosSale::where('store_id', 1)->latest('id')->first();
echo "Latest Sale ID: {$sale->id}\n";
echo "Receipt: {$sale->receipt_number}\n";
echo "Total: {$sale->total}\n";

$payments = PosPayment::where('pos_sale_id', $sale->id)->get();
foreach ($payments as $p) {
    echo "Payment: Method {$p->method} | Amount {$p->amount}\n";
}

$shift = CashierShift::find(1);
echo "Shift 1: Cash Sales: {$shift->cash_sales} | Opening: {$shift->opening_cash}\n";
$drawerCash = bcadd($shift->opening_cash, $shift->cash_sales, 2);
echo "Drawer Cash: {$drawerCash} MMK (Expected unchanged at 130000.00)\n";

$balance = InventoryBalance::where('store_id', 1)->where('warehouse_id', 1)->where('product_id', 3)->first();
echo "POWERBANK in MAIN: {$balance->quantity_on_hand} (Expected 9.000)\n";

// Link serial RMX-2026-001 to this sale
$warranty = DeviceWarranty::where('store_id', 1)->where('serial_number', 'RMX-2026-001')->first();
if ($warranty) {
    $warranty->update([
        'pos_sale_id' => $sale->id,
        'invoice_number' => $sale->receipt_number,
        'purchase_date' => now()->toDateString(),
        'status' => 'active',
    ]);
    echo "DeviceWarranty linked: Serial {$warranty->serial_number} -> Sale {$warranty->pos_sale_id} / {$warranty->invoice_number}\n";
}
