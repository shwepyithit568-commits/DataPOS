<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Store;
use App\POS\Models\PosRegister;
use App\POS\Models\PosShift;
use Illuminate\Support\Facades\Schema;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->first();
echo "Store: " . ($store ? $store->id . " - " . $store->name : "None") . "\n";

$users = User::all();
foreach ($users as $u) {
    echo "User: {$u->id} | {$u->name} | {$u->phone} | {$u->role} | store_id: {$u->store_id}\n";
}

$registers = PosRegister::where('store_id', $store->id)->get();
echo "Registers count: " . $registers->count() . "\n";
foreach ($registers as $r) {
    echo "Register: {$r->id} | {$r->name} | {$r->code}\n";
}

$shifts = PosShift::where('store_id', $store->id)->get();
echo "Shifts count: " . $shifts->count() . "\n";
foreach ($shifts as $s) {
    echo "Shift: {$s->id} | User: {$s->user_id} | Status: {$s->status} | Float: {$s->opening_float}\n";
}
