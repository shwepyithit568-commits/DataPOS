<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/', 'GET', ['store_slug' => 'datapos-mobile']);
$response = $app->handle($request);
$content = $response->getContent();

echo "Response status: " . $response->getStatusCode() . "\n";

// Check for forbidden hardcoded 'Ks'
preg_match_all('/(?:\d+\s*(?:Ks|\(Ks\)))|(?:Ks\s*\d+)/i', $content, $ksMatches);
echo "Hardcoded 'Ks' matches found: " . count($ksMatches[0]) . "\n";
if (count($ksMatches[0]) > 0) {
    print_r(array_slice($ksMatches[0], 0, 5));
}

// Check 3D buttons count
preg_match_all('/sf-btn-3d/i', $content, $btnMatches);
echo "sf-btn-3d occurrences: " . count($btnMatches[0]) . "\n";

// Check Category Flyout
preg_match_all('/activeHover === (\d+)/', $content, $flyoutMatches);
echo "Category Flyout Triggers on Home: " . count($flyoutMatches[0]) . "\n";

// Check Glass Finder 3D button
if (strpos($content, 'sf-btn-3d') !== false && strpos($content, 'glass-finder') !== false) {
    echo "Glass Finder 3D button: PRESENT\n";
}

// Check Section Header View All 3D buttons
if (strpos($content, 'view_all_products') !== false) {
    echo "View All Products 3D buttons: PRESENT\n";
}
