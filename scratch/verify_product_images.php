<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/products', 'GET', ['store_slug' => 'datapos-mobile']);
$response = $app->handle($request);
$content = $response->getContent();

echo "Products page status: " . $response->getStatusCode() . "\n";

// Check if data-product-*.webp are linked
preg_match_all('/placeholders\/data-product-(\d+)\.webp/', $content, $matches);
echo "DATA PRODUCTS placeholder image references found: " . count($matches[0]) . "\n";

$countsByImage = array_count_values($matches[1]);
foreach ($countsByImage as $imgNum => $cnt) {
    echo " - data-product-{$imgNum}.webp: {$cnt} products\n";
}
