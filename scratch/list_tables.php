<?php
require_once __DIR__ . '/../src/Core/App.php';
use App\Core\App;

// Manually initialize App if needed or just use PDO from config
$config = [
    'host' => 'localhost',
    'name' => 'CTMS',
    'user' => 'root',
    'pass' => '',
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $stmt = $pdo->query("SHOW TABLES");
    echo "--- Tables in CTMS ---\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
