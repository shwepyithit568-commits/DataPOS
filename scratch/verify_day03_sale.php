<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\PosSale;
use App\POS\Models\PosPayment;
use App\POS\Models\CashierShift;
use App\POS\Models\InventoryBalance;

$sale = PosSale::where('store_id', 1)->latest('id')->first();
if ($sale) {
    echo "Sale ID: {$sale->id}\n";
    echo "Invoice No: {$sale->invoice_number}\n";
    echo "Total: {$sale->total_amount}\n";
    echo "Payment Status: {$sale->payment_status}\n";
    echo "Cashier Shift ID: {$sale->cashier_shift_id}\n";
    
    foreach ($sale->items as $item) {
        echo " - Item: Product {$item->product_id} | Qty: {$item->quantity} | Price: {$item->unit_price} | Total: {$item->total_amount}\n";
    }
}

$payments = PosPayment::where('pos_sale_id', $sale?->id)->get();
foreach ($payments as $p) {
    echo "Payment ID: {$p->id} | Method: {$p->payment_method} | Amount: {$p->amount} | Tendered: {$p->tendered_amount} | Change: {$p->change_amount}\n";
}

$shift = CashierShift::find(1);
echo "Shift 1: Cash Sales = {$shift->cash_sales}, Opening = {$shift->opening_cash}\n";
$expectedDrawer = bcadd($shift->opening_cash, $shift->cash_sales, 2);
echo "Current Calculated Drawer Cash: {$expectedDrawer} MMK\n";

$balances = InventoryBalance::where('store_id', 1)->get();
foreach ($balances as $b) {
    echo "Balance: Warehouse {$b->warehouse_id} | Product {$b->product_id} | Qty: {$b->quantity}\n";
}
