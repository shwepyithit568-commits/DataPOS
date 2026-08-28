<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\POS\Services\InventoryService;

$iv = $app->make(InventoryService::class);

echo '--- balance rows for store 5 (product 45 Anker) ---' . PHP_EOL;
foreach (DB::table('inventory_balances')->where('store_id', 5)->where('product_id', 45)->get() as $r) {
    echo "  id={$r->id} wh=" . json_encode($r->warehouse_id) . " variant=" . json_encode($r->product_variant_id) . " onhand={$r->quantity_on_hand}" . PHP_EOL;
}

echo 'distinct warehouse_id in inventory_balances store5: ';
foreach (DB::table('inventory_balances')->where('store_id', 5)->distinct()->pluck('warehouse_id') as $w) {
    echo json_encode($w) . ' ';
}
echo PHP_EOL;

echo 'warehouses table rows: ' . DB::table('warehouses')->count() . PHP_EOL;
foreach (DB::table('warehouses')->get(['id','store_id','name','is_default']) as $w) {
    echo "  wh id={$w->id} store={$w->store_id} name=" . json_encode($w->name) . " default=" . ($w->is_default??'?') . PHP_EOL;
}

$wh = $iv->defaultWarehouseId(5);
echo 'defaultWarehouseId(5) = ' . $wh . PHP_EOL;

$bal = $iv->balanceFor(5, 45, null, $wh);
echo 'balanceFor(5,45,null,'.$wh.') = ' . ($bal ? 'qty=' . $bal->quantity_on_hand : 'null') . PHP_EOL;

$bal2 = $iv->balanceFor(5, 45);
echo 'balanceFor(5,45) [no wh] = ' . ($bal2 ? 'qty=' . $bal2->quantity_on_hand . ' wh=' . json_encode($bal2->warehouse_id) : 'null') . PHP_EOL;

echo 'totalOnHand(5,45) = ' . $iv->totalOnHand(5, 45) . PHP_EOL;

echo 'SENTINEL_WAREHOUSE = ' . InventoryService::SENTINEL_WAREHOUSE . PHP_EOL;