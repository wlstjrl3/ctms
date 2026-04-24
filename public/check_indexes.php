<?php
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/App.php';
use App\Core\App;
header('Content-Type: text/plain');
$db = App::getInstance()->db();

$tables = ['bd_member_right', 'bd_member_csdate', 'tch_tml'];

foreach ($tables as $table) {
    echo "\nIndexes for $table:\n";
    try {
        $indexes = $db->fetchAll("SHOW INDEX FROM $table");
        foreach ($indexes as $idx) {
            echo "{$idx['Key_name']}: {$idx['Column_name']} (Unique: " . ($idx['Non_unique'] ? 'No' : 'Yes') . ")\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
