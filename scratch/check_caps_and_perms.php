<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Store;
use App\Capabilities\Capability;
use App\Services\StorePermissionService;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->first();
$cashier = User::where('phone', '09200000022')->first();
$manager = User::where('phone', '09200000011')->first();

$permService = app(StorePermissionService::class);

echo "Cashier ID: {$cashier->id}\n";
$cashierStoreUser = \DB::table('store_user')->where('store_id', $store->id)->where('user_id', $cashier->id)->first();
echo "Cashier store_user: " . json_encode($cashierStoreUser) . "\n";
echo "Cashier can pos_sales.view: " . ($permService->can($cashier, $store, 'pos_sales.view') ? 'YES' : 'NO') . "\n";
echo "Cashier can pos_closing.create: " . ($permService->can($cashier, $store, 'pos_closing.create') ? 'YES' : 'NO') . "\n";

echo "Manager ID: {$manager->id}\n";
$managerStoreUser = \DB::table('store_user')->where('store_id', $store->id)->where('user_id', $manager->id)->first();
echo "Manager store_user: " . json_encode($managerStoreUser) . "\n";
echo "Manager can pos_sales.view: " . ($permService->can($manager, $store, 'pos_sales.view') ? 'YES' : 'NO') . "\n";
