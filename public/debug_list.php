<?php
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/App.php';
use App\Core\App;
header('Content-Type: text/plain');

$app = App::getInstance();
$db = $app->db();
$session = $app->session();

echo "Current Session Data:\n";
print_r($_SESSION);

$bcode = $session->get('bcode');
echo "\nCurrent bcode: " . ($bcode ?: 'NOT SET') . "\n";

echo "\nChecking bd_member_right table count...\n";
$count = $db->fetch("SELECT COUNT(*) as total FROM bd_member_right");
echo "Total records in bd_member_right: " . $count['total'] . "\n";

if ($bcode) {
    echo "Records for bcode '$bcode': ";
    $countB = $db->fetch("SELECT COUNT(*) as total FROM bd_member_right WHERE bcode = ?", [$bcode]);
    echo $countB['total'] . "\n";
    
    if ($countB['total'] > 0) {
        echo "Sample data for bcode '$bcode':\n";
        print_r($db->fetchAll("SELECT * FROM bd_member_right WHERE bcode = ? LIMIT 3", [$bcode]));
    }
} else {
    echo "\nSample data from bd_member_right (first 3):\n";
    print_r($db->fetchAll("SELECT * FROM bd_member_right LIMIT 3"));
}
