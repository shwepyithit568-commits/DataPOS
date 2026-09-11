<?php

$store = \App\Models\Store::where('slug', 'acdc-mobile')->first();
$user = \App\Models\User::find(11); // U Myo Aung

echo "Attempting to seed 'mobile-sale-service' scenario into store: {$store->name} (id={$store->id})\n";
echo "Actor: {$user->name}\n\n";

try {
    $service = app(\App\Services\DemoBusinessScenarioService::class);
    $result = $service->seedIntoStore($store, 'mobile-sale-service', $user, false, false);
    
    echo "SUCCESS!\n";
    echo "Products seeded: {$result['products']}\n";
    echo "Featured: {$result['featured_products']}\n";
    echo "Promotions: {$result['timed_promotions']}\n";
    echo "Assets: " . json_encode($result['assets']) . "\n";
    if (!empty($result['asset_warning'])) {
        echo "Asset warning: {$result['asset_warning']}\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace (first 5):\n";
    $traces = $e->getTrace();
    for ($i = 0; $i < min(5, count($traces)); $i++) {
        $t = $traces[$i];
        echo "  #{$i} " . ($t['file'] ?? '?') . ":" . ($t['line'] ?? '?') . " " . ($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? '') . "()\n";
    }
}

exit(0);
