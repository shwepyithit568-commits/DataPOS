<?php

$store = \App\Models\Store::where('slug', 'acdc-mobile')->first();
if (!$store) {
    echo "Store 'acdc-mobile' not found!\n";
    exit(0);
}

$user = \App\Models\User::find(11);
echo "User: {$user->name} (id={$user->id})\n";

$pivot = $user->stores()->where('store_id', $store->id)->first();
echo "Pivot role: " . ($pivot?->pivot?->role ?? 'NONE') . "\n";

$perms = \App\Models\StoreUserPermission::where('store_id', $store->id)
    ->where('user_id', $user->id)->get();
echo "Permissions count: " . $perms->count() . "\n";
if ($perms->isNotEmpty()) {
    foreach ($perms as $p) {
        echo "  - {$p->permission}\n";
    }
} else {
    echo "  (no explicit permissions found)\n";
}

$staffRoles = \App\Models\StaffRole::where('store_id', $store->id)->get();
echo "\nStaffRoles for store:\n";
if ($staffRoles->isEmpty()) {
    echo "  (none)\n";
}
foreach ($staffRoles as $sr) {
    echo "  - {$sr->name} (slug: {$sr->slug})\n";
}

$warehouses = \App\POS\Models\Warehouse::where('store_id', $store->id)->get();
echo "\nWarehouses: " . $warehouses->count() . "\n";
foreach ($warehouses as $wh) {
    echo "  - {$wh->name} (code: {$wh->code}, default: " . ($wh->is_default ? 'yes' : 'no') . ")\n";
}

$branches = \App\POS\Models\Branch::where('store_id', $store->id)->get();
echo "\nBranches: " . $branches->count() . "\n";
foreach ($branches as $br) {
    echo "  - {$br->name}\n";
}

exit(0);