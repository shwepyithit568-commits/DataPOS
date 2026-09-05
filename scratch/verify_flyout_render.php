<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/products', 'GET', ['store_slug' => 'datapos-mobile']);
$response = $app->handle($request);
$content = $response->getContent();

echo "Response status: " . $response->getStatusCode() . "\n";

// Check if activeCatHover flyouts exist in content
preg_match_all('/activeCatHover === (\d+)/', $content, $matches);
echo "Flyout triggers found: " . count($matches[0]) . "\n";
echo "Distinct Category IDs with flyouts: " . implode(', ', array_unique($matches[1])) . "\n";

// Check if flyout is outside overflow-y-auto
$catSidebarStart = strpos($content, 'messages.categories');
if ($catSidebarStart === false) {
    $catSidebarStart = strpos($content, 'CATEGORIES') ?: strpos($content, 'Categories') ?: strpos($content, '🗂️');
}
echo "Category sidebar position found: " . ($catSidebarStart !== false ? 'YES' : 'NO') . "\n";

// Check a specific category flyout content e.g. Smartphones & Tablets (id 236)
if (strpos($content, 'activeCatHover === 236') !== false) {
    echo "Found Smartphones & Tablets flyout panel (id 236)!\n";
    // Check if subcategories exist inside it
    if (strpos($content, 'Android Phone') !== false) echo " - Contains 'Android Phone'\n";
    if (strpos($content, 'iOS / iPhone') !== false) echo " - Contains 'iOS / iPhone'\n";
    if (strpos($content, 'Tablet / iPad') !== false) echo " - Contains 'Tablet / iPad'\n";
} else {
    echo "Smartphones & Tablets flyout NOT found in rendered HTML!\n";
}

// Check Cable & Charger (id 242)
if (strpos($content, 'activeCatHover === 242') !== false) {
    echo "Found Cable & Charger flyout panel (id 242)!\n";
    if (strpos($content, 'Charging Cable') !== false) echo " - Contains 'Charging Cable'\n";
    if (strpos($content, 'Car Charger') !== false) echo " - Contains 'Car Charger'\n";
}
