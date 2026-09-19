<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Store;
use App\Services\StorePermissionService;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->first();
$cashier = User::where('phone', '09200000022')->first();
$permService = app(StorePermissionService::class);

echo "can pos_sales.create: " . ($permService->can($cashier, $store, 'pos_sales.create') ? 'YES' : 'NO') . "\n";
echo "can pos_sales.update: " . ($permService->can($cashier, $store, 'pos_sales.update') ? 'YES' : 'NO') . "\n";
echo "can pos_sales.view: " . ($permService->can($cashier, $store, 'pos_sales.view') ? 'YES' : 'NO') . "\n";
