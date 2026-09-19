<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$expense = \App\POS\Models\Expense::latest()->first();
echo "EXPENSE: id={$expense->id} num={$expense->expense_number} title={$expense->title} amount={$expense->amount} method={$expense->payment_method} source={$expense->payment_source} shift_id={$expense->cashier_shift_id}" . PHP_EOL;

$shift = \App\POS\Models\CashierShift::find(1);
$expensesPaid = \App\POS\Models\Expense::where('cashier_shift_id', 1)
    ->where('payment_method', 'cash')
    ->where('payment_source', 'drawer')
    ->where('status', 'paid')
    ->sum('amount');

echo "SHIFT: id={$shift->id} cash_sales={$shift->cash_sales} cash_in={$shift->cash_in} cash_out={$shift->cash_out} cash_refunds={$shift->cash_refunds}" . PHP_EOL;
echo "DRAWER EXPENSES: {$expensesPaid}" . PHP_EOL;

$expectedDrawer = $shift->opening_cash + $shift->cash_sales + $shift->cash_in - $shift->cash_out - $shift->cash_refunds - $expensesPaid;
echo "CALCULATED EXPECTED DRAWER: {$expectedDrawer}" . PHP_EOL;
