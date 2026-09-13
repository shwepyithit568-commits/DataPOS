<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "== expense_categories by store ==\n";
foreach (DB::table('expense_categories')->selectRaw('store_id, count(*) c')->groupBy('store_id')->get() as $r) {
    echo "  store_id={$r->store_id}: {$r->c}\n";
}

echo "\n== categories/brands of store #1 ==\n";
echo json_encode(DB::table('categories')->where('store_id',1)->get(['id','name','slug','code','parent_id']), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";
echo json_encode(DB::table('brands')->where('store_id',1)->get(['id','name','slug','code']), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";

echo "\n== warehouses/branches store #1 ==\n";
echo json_encode(DB::table('warehouses')->where('store_id',1)->get(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";
echo json_encode(DB::table('branches')->where('store_id',1)->get(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";

echo "\n== users attached to store #1 ==\n";
echo json_encode(DB::table('users')->join('store_user','store_user.user_id','=','users.id')
    ->where('store_user.store_id',1)
    ->get(['users.id','users.name','users.phone','users.role','store_user.role as store_role']), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), "\n";

echo "\n== full list of tables that actually have store_id column ==\n";
$all = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
$withStore = [];
foreach ($all as $t) {
    $cols = DB::select("PRAGMA table_info({$t->name})");
    foreach ($cols as $c) {
        if ($c->name === 'store_id') { $withStore[] = $t->name; break; }
    }
}
echo implode(', ', $withStore), "\n";

echo "\n== counts for store_id=1 in every store-scoped table ==\n";
foreach ($withStore as $t) {
    $n = DB::table($t)->where('store_id',1)->count();
    if ($n > 0) echo "  $t = $n\n";
}
