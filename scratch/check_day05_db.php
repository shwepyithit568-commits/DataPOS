<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\PosSale;
use App\POS\Models\PosPayment;
use App\POS\Models\CashierShift;
use App\POS\Models\InventoryBalance;
use App\POS\Models\CustomerLedgerEntry;

$sale = PosSale::where('store_id', 1)->latest('id')->first();
echo "Sale ID: {$sale->id}\n";
echo "Receipt: {$sale->receipt_number}\n";
echo "Customer ID: {$sale->customer_id}\n";
echo "Subtotal: {$sale->subtotal}\n";
echo "Discount: {$sale->discount}\n";
echo "Total: {$sale->total}\n";

$payments = PosPayment::where('pos_sale_id', $sale->id)->get();
foreach ($payments as $p) {
    echo "Payment: Method {$p->method} | Amount {$p->amount}\n";
}

$shift = CashierShift::find(1);
echo "Shift 1: Cash Sales: {$shift->cash_sales} | Opening: {$shift->opening_cash}\n";

$debts = CustomerLedgerEntry::where('store_id', 1)->get();
echo "Customer Ledger Entries count: " . $debts->count() . "\n";
foreach ($debts as $d) {
    echo "Debt: ID {$d->id} | Cust {$d->customer_id} | Type {$d->type} | Amount {$d->amount} | Balance {$d->balance}\n";
}

$balances = InventoryBalance::where('store_id', 1)->where('warehouse_id', 1)->get();
foreach ($balances as $b) {
    echo "MAIN Balance: Product {$b->product_id} | Qty: {$b->quantity_on_hand}\n";
}
