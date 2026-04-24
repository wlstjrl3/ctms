<?php
$config = [
    'host' => 'localhost',
    'name' => 'CTMS',
    'user' => 'root',
    'pass' => '',
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $stmt = $pdo->query("SELECT login_id FROM bd_member_right ORDER BY login_id DESC LIMIT 20");
    echo "--- Sample login_id values ---\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['login_id'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
