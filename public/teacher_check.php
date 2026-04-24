<?php
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/App.php';
use App\Core\App;
header('Content-Type: text/plain');
$db = App::getInstance()->db();

$tables = ['bd_member_right', 'bd_member_csdate', 'tch_tml'];

foreach ($tables as $table) {
    echo "\n$table Structure:\n";
    try {
        $columns = $db->fetchAll("DESCRIBE $table");
        foreach ($columns as $col) {
            echo "{$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}
