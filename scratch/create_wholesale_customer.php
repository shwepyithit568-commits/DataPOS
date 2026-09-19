<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\POS\Services\PosSaleService;
use Illuminate\Support\Facades\Hash;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->first();

// Check if customer exists
$user = User::where('phone', '09400000033')->first();
if (!$user) {
    $user = User::create([
        'name' => 'ဦးသန်းလွင်',
        'phone' => '09400000033',
        'password' => Hash::make('Password@2026!'),
        'role' => 'customer',
    ]);
}

// Attach to store 1 as wholesale_customer
$membership = $user->stores()->where('stores.id', $store->id)->first();
if (!$membership) {
    $user->stores()->attach($store->id, [
        'role' => 'wholesale_customer',
        'status' => 'active',
    ]);
} else {
    $user->stores()->updateExistingPivot($store->id, [
        'role' => 'wholesale_customer',
        'status' => 'active',
    ]);
}

echo "Wholesale customer created: ID {$user->id} | Name: {$user->name} | Phone: {$user->phone}\n";

$salesService = app(PosSaleService::class);
$charger = Product::find(1);
$glass = Product::find(2);

echo "Charger price for U Than Lwin: " . $salesService->priceFor($user, $charger) . " (Expected: 18000.00)\n";
echo "Glass price for U Than Lwin: " . $salesService->priceFor($user, $glass) . " (Expected: 2500.00)\n";
