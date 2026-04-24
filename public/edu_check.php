<?php
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/App.php';
use App\Core\App;
header('Content-Type: text/plain');
$db = App::getInstance()->db();
try {
    echo "\nctms_person_login_list Structure:\n";
    $columns = $db->fetchAll("DESCRIBE ctms_person_login_list");
    foreach ($columns as $col) {
        echo "{$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
