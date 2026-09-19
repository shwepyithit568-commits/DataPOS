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
$manager = User::where('phone', '09200000011')->first();
$permService = app(StorePermissionService::class);

echo "Cashier can pos_returns.view: " . ($permService->can($cashier, $store, 'pos_returns.view') ? 'YES' : 'NO') . "\n";
echo "Cashier can pos_returns.create: " . ($permService->can($cashier, $store, 'pos_returns.create') ? 'YES' : 'NO') . "\n";
echo "Manager can pos_returns.view: " . ($permService->can($manager, $store, 'pos_returns.view') ? 'YES' : 'NO') . "\n";
echo "Manager can pos_returns.create: " . ($permService->can($manager, $store, 'pos_returns.create') ? 'YES' : 'NO') . "\n";
