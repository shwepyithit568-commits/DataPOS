<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\User;
use App\POS\Models\PosSale;
use App\POS\Models\PosPayment;
use App\POS\Models\CashierShift;
use App\POS\Services\CustomerDebtService;
use Illuminate\Support\Facades\DB;

$store = Store::find(1);
$actor = User::find(4);
$sale = PosSale::find(3);

DB::transaction(function () use ($store, $actor, $sale) {
    // 1. Update sale
    $sale->update([
        'discount' => '1000.00',
        'total' => '139000.00',
        'exempt_amount' => '139000.00',
    ]);
    
    // 2. Update payment
    PosPayment::where('pos_sale_id', $sale->id)->update([
        'method' => 'credit',
        'amount' => '139000.00',
        'change_given' => '0.00',
    ]);
    
    // 3. Revert shift cash_sales back to 30,000.00
    $shift = CashierShift::find(1);
    $shift->update([
        'cash_sales' => '30000.00',
    ]);
    
    // 4. Record customer debt receivable
    $debtService = app(CustomerDebtService::class);
    $debtService->recordSaleDebt(
        store: $store,
        customerId: $sale->customer_id,
        saleId: $sale->id,
        amount: '139000.00',
        actor: $actor,
        clientTransactionId: "pos_sale:{$sale->id}:debt",
        branchId: $shift->branch_id,
    );
});

echo "Corrected Sale 3 successfully!\n";

$sale = PosSale::find(3);
echo "Sale 3 Total: {$sale->total}, Discount: {$sale->discount}\n";
$payment = PosPayment::where('pos_sale_id', 3)->first();
echo "Payment 3: Method {$payment->method}, Amount {$payment->amount}\n";
$shift = CashierShift::find(1);
echo "Shift 1 Cash Sales: {$shift->cash_sales}, Opening: {$shift->opening_cash}\n";
$drawer = bcadd($shift->opening_cash, $shift->cash_sales, 2);
echo "Current Drawer Cash: {$drawer} MMK (Expected 130000.00)\n";
$debtService = app(CustomerDebtService::class);
echo "Customer 6 Outstanding Receivable: " . $debtService->balanceFor(1, 6) . " MMK (Expected 139000.00)\n";
