<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use App\POS\Models\Warehouse;
use App\POS\Services\OpeningStockService;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->firstOrFail();
$manager = User::where('phone', '09200000011')->firstOrFail();
$pCharger = Product::where('store_id', $store->id)->where('barcode', 'UAT-CHG-001')->firstOrFail();
$pGlass = Product::where('store_id', $store->id)->where('barcode', 'UAT-GLS-001')->firstOrFail();

$service = app(OpeningStockService::class);

echo "Submitting Opening Stock for {$store->name} by Manager {$manager->name}...\n";

$items = [
    [
        'product_id' => $pCharger->id,
        'product_variant_id' => null,
        'quantity' => '20.000',
        'unit_cost' => '15000.00',
    ],
    [
        'product_id' => $pGlass->id,
        'product_variant_id' => null,
        'quantity' => '50.000',
        'unit_cost' => '1500.00',
    ],
];

$req = $service->create($store, $items, 'UAT Baseline Opening Stock', $manager);
echo "Opening Stock Request Created: ID: {$req->id}, Number: {$req->request_number}, Total Cost: {$req->total_cost}\n";

$approved = $service->approve($store, $req, $manager, 'Approved by Store Manager');
echo "Opening Stock Request Approved: Status: {$approved->status}, Approved By: {$approved->approved_by}\n";

// Verify inventory balances
$balCharger = InventoryBalance::where('store_id', $store->id)
    ->where('product_id', $pCharger->id)
    ->first();
$balGlass = InventoryBalance::where('store_id', $store->id)
    ->where('product_id', $pGlass->id)
    ->first();

echo "Stock Balance in Warehouse {$balCharger->warehouse_id} (MAIN):\n";
echo "- CHARGER On-Hand: {$balCharger->quantity_on_hand}\n";
echo "- GLASS On-Hand: {$balGlass->quantity_on_hand}\n";

$movements = InventoryMovement::where('store_id', $store->id)->get();
echo "Ledger movements count: {$movements->count()}\n";
foreach ($movements as $m) {
    echo "  Movement ID: {$m->id}, Product: {$m->product_id}, Type: {$m->movement_type->value}, Qty: {$m->quantity}, Unit Cost: {$m->unit_cost}\n";
}
