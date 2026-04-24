<?php
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/App.php';
use App\Core\App;
header('Content-Type: text/plain');
$db = App::getInstance()->db();
try {
    $count = $db->fetch("SELECT COUNT(*) as total FROM bd_member_right");
    echo "Total records in bd_member_right: " . ($count['total'] ?? 0) . "\n";
    
    $bcodes = $db->fetchAll("SELECT DISTINCT bcode FROM bd_member_right");
    echo "Found bcodes in table: ";
    foreach ($bcodes as $b) echo $b['bcode'] . " ";
    echo "\n";
    
    echo "\nCurrent Session bcode: " . (App::getInstance()->session()->get('bcode') ?: 'NOT SET') . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
