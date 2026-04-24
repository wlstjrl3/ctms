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
    
    foreach (['css_info_es', 'css_info_mhs'] as $table) {
        $stmt = $pdo->query("DESCRIBE $table");
        echo "--- Structure of $table ---\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
