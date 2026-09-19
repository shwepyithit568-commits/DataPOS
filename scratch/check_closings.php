<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DAILY CLOSINGS ===\n";
dump(\DB::table('daily_closings')->get());

echo "=== CASHIER SHIFTS ===\n";
dump(\DB::table('cashier_shifts')->get());
