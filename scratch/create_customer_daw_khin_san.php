<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Store;
use Illuminate\Support\Facades\Hash;

$store = Store::where('slug', 'shwe-pyi-thit-mobile')->first();

$user = User::where('phone', '09400000044')->first();
if (!$user) {
    $user = User::create([
        'name' => 'ဒေါ်ခင်စန်း',
        'phone' => '09400000044',
        'password' => Hash::make('Password@2026!'),
        'role' => 'customer',
    ]);
}

if (!$user->stores()->where('stores.id', $store->id)->exists()) {
    $user->stores()->attach($store->id, [
        'role' => 'retail_customer',
        'status' => 'active',
    ]);
}

echo "Customer Daw Khin San: ID {$user->id} | Name: {$user->name} | Phone: {$user->phone}\n";
