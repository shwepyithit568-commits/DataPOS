<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\POS\Models\Branch;
$branches = Branch::where('store_id', 1)->get();
echo "Branches count: " . $branches->count() . "\n";
foreach ($branches as $b) {
    echo "Branch: {$b->id} - {$b->name}\n";
}
