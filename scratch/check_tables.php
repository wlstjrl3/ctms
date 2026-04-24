<?php
$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $stmt = $pdo->query("DESCRIBE academy_state");
    echo "--- Structure of academy_state ---\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
