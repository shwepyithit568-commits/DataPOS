<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\InventoryBalance;
use App\POS\Models\StockTransfer;
use App\POS\Models\StockTransferItem;
use App\POS\Models\Warehouse;
use App\POS\Services\InventoryService;
use Illuminate\Support\Facades\DB;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->firstOrFail();
$manager = User::where('phone', '09200000011')->firstOrFail();
$mainWh = Warehouse::where('store_id', $store->id)->where('code', 'MAIN')->firstOrFail();
$auxWh = Warehouse::where('store_id', $store->id)->where('code', 'AUX')->firstOrFail();
$pCharger = Product::where('store_id', $store->id)->where('barcode', 'UAT-CHG-001')->firstOrFail();

$inventory = app(InventoryService::class);

echo "Executing INV-02: Transfer 5 CHARGER from MAIN ({$mainWh->id}) to AUX ({$auxWh->id})...\n";

// 1. Create StockTransfer
$transfer = DB::transaction(function () use ($store, $mainWh, $auxWh, $pCharger, $manager) {
    $transfer = StockTransfer::create([
        'store_id' => $store->id,
        'transfer_number' => StockTransfer::generateNumber($store->id),
        'from_warehouse_id' => $mainWh->id,
        'to_warehouse_id' => $auxWh->id,
        'status' => 'pending',
        'notes' => 'UAT Baseline Transfer 5 Chargers MAIN to AUX',
        'created_by' => $manager->id,
    ]);

    $bal = InventoryBalance::where('store_id', $store->id)
        ->where('warehouse_id', $mainWh->id)
        ->where('product_id', $pCharger->id)
        ->first();

    StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $pCharger->id,
        'quantity' => '5.000',
        'unit_cost' => $bal?->unit_cost_avg ?? '15000.00',
    ]);

    return $transfer;
});

echo "Transfer Created: ID {$transfer->id}, Number {$transfer->transfer_number}, Status: {$transfer->status}\n";

// 2. Ship Transfer (pending -> in_transit)
$transfer->update(['status' => 'in_transit', 'shipped_at' => now()]);
echo "Transfer Shipped: Status {$transfer->status}\n";

// 3. Receive Transfer (in_transit -> completed)
DB::transaction(function () use ($transfer, $inventory, $manager) {
    foreach ($transfer->items as $item) {
        $inventory->postMovement([
            'store_id' => $transfer->store_id,
            'product_id' => $item->product_id,
            'warehouse_id' => $transfer->from_warehouse_id,
            'movement_type' => 'transfer_out',
            'quantity_delta' => -$item->quantity,
            'unit_cost' => $item->unit_cost,
            'client_transaction_id' => "trf_out:{$transfer->id}:item:{$item->id}",
            'posted_by' => $manager->id,
        ]);

        $inventory->postMovement([
            'store_id' => $transfer->store_id,
            'product_id' => $item->product_id,
            'warehouse_id' => $transfer->to_warehouse_id,
            'movement_type' => 'transfer_in',
            'quantity_delta' => $item->quantity,
            'unit_cost' => $item->unit_cost,
            'client_transaction_id' => "trf_in:{$transfer->id}:item:{$item->id}",
            'posted_by' => $manager->id,
        ]);
    }

    $transfer->update(['status' => 'completed', 'received_at' => now()]);
});

echo "Transfer Completed: Status {$transfer->status}\n";

// 4. Verify balances
$mainBal = InventoryBalance::where('store_id', $store->id)
    ->where('warehouse_id', $mainWh->id)
    ->where('product_id', $pCharger->id)
    ->first();
$auxBal = InventoryBalance::where('store_id', $store->id)
    ->where('warehouse_id', $auxWh->id)
    ->where('product_id', $pCharger->id)
    ->first();

echo "=== INV-02 Verification ===\n";
echo "- MAIN Warehouse CHARGER On-Hand: {$mainBal->quantity_on_hand} (Expected: 15.000)\n";
echo "- AUX Warehouse CHARGER On-Hand: {$auxBal->quantity_on_hand} (Expected: 5.000)\n";
echo "- Store Total CHARGER: " . ($mainBal->quantity_on_hand + $auxBal->quantity_on_hand) . " (Expected: 20.000)\n";
