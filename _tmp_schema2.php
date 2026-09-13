<?php
// READ-ONLY schema inspection. Delete after use.
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$want = ['stores','storefront_settings','products','categories','brands','suppliers',
         'service_jobs','service_job_items','expenses','expense_categories','cashier_shifts',
         'inventory_balances','warehouses','branches','pos_sales','pos_payments','customer_ledger_entries'];

foreach ($want as $t) {
    $cols = DB::select("PRAGMA table_info($t)");
    if (!$cols) { echo "== $t: MISSING\n\n"; continue; }
    echo "== $t ==\n";
    $parts = [];
    foreach ($cols as $c) {
        $nn = $c->notnull ? ' NOT NULL' : '';
        $parts[] = $c->name . ':' . $c->type . $nn;
    }
    echo implode(', ', $parts) . "\n\n";
}

echo "===== store #1 row =====\n";
echo json_encode(DB::table('stores')->where('id',1)->first(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n\n";

echo "===== store #1 capability/profile hints =====\n";
echo json_encode(DB::table('storefront_settings')->where('store_id',1)->first(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n\n";

echo "===== service_jobs store#1 sample =====\n";
echo json_encode(DB::table('service_jobs')->where('store_id',1)->get(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n\n";

echo "===== store_user rows for store #1 =====\n";
echo json_encode(DB::table('store_user')->where('store_id',1)->get(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n\n";

echo "===== staff_roles for store #1 =====\n";
echo json_encode(DB::table('staff_roles')->where('store_id',1)->get(['id','slug','name']), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n\n";

echo "===== cashier_shifts store#1 =====\n";
echo json_encode(DB::table('cashier_shifts')->where('store_id',1)->get(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n\n";

echo "===== expense_categories (global?) =====\n";
echo json_encode(DB::table('expense_categories')->get(['id','name','slug'])->take(25), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";
