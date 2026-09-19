<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
$tableKey = 'Tables_in_' . env('DB_DATABASE');
$matching = [];
foreach ($tables as $t) {
    $tName = $t->$tableKey;
    if (preg_match('/shift|register|cash/i', $tName)) {
        $matching[] = $tName;
    }
}
echo "Matching tables:\n" . implode("\n", $matching) . "\n";
