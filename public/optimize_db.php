<?php
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/App.php';
use App\Core\App;
header('Content-Type: text/plain');
$db = App::getInstance()->db()->getPdo();

$statements = [
    "CREATE INDEX idx_login_id ON bd_member_right(login_id)",
    "CREATE INDEX idx_bcode ON bd_member_right(bcode)",
    "CREATE INDEX idx_name ON bd_member_right(name)",
    "CREATE INDEX idx_login_id_cs ON bd_member_csdate(login_id)",
    "CREATE INDEX idx_login_id_tml ON tch_tml(login_id)"
];

foreach ($statements as $sql) {
    echo "Executing: $sql ... ";
    try {
        $db->exec($sql);
        echo "OK\n";
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}
