<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Store;

$user = User::where('phone', '09400000033')->first();
echo "User 09400000033: " . ($user ? "{$user->id} - {$user->name}" : "Not found") . "\n";

// Check customer membership tiers / groups
$tiers = \DB::table('membership_tiers')->where('store_id', 1)->get();
echo "Tiers count: " . $tiers->count() . "\n";
foreach ($tiers as $t) {
    echo "Tier: {$t->id} - {$t->name}\n";
}
