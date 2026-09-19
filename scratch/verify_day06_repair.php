<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceJobPayment;
use App\POS\Models\CashEvent;
use App\POS\Models\CashierShift;

$job = ServiceJob::where('store_id', 1)->latest('id')->first();
echo "Job ID: {$job->id}\n";
echo "Job Number: {$job->job_number}\n";
echo "Customer ID: {$job->customer_id}\n";
echo "Contact: {$job->contact_name} ({$job->contact_phone})\n";
echo "Device: {$job->brand} {$job->model}\n";
echo "Estimated Charge: {$job->estimated_charge}\n";

$payments = ServiceJobPayment::where('service_job_id', $job->id)->get();
echo "Payments count: " . $payments->count() . "\n";
foreach ($payments as $p) {
    echo "Payment: ID {$p->id} | Method {$p->method} | Amount {$p->amount} | Ref {$p->reference}\n";
}

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
echo "Current Calculated Drawer Cash: {$drawer} MMK (Expected: 160000.00 MMK)\n";
