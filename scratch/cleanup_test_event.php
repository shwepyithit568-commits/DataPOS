<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\DB::table('cash_events')->where('reason', 'like', '%Test%')->delete();
\DB::table('cashier_shifts')->where('id', 1)->update(['cash_in' => '80000.00']);
echo "Shift 1 cash_in restored to 80000.00 and test events removed.\n";
