<?php
$db = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'service%' ORDER BY name");
foreach ($tables as $t) {
    echo $t['name'] . PHP_EOL;
}
