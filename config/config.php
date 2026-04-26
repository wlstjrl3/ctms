<?php
/**
 * Application Configuration
 */

// Basic .env loader (for development without full composer dependency)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

return [
    'db' => [
        'host'    => $_ENV['DB_HOST'] ?? 'localhost',
        'name'    => $_ENV['DB_NAME'] ?? 'ctms_db',
        'user'    => $_ENV['DB_USER'] ?? 'root',
        'pass'    => $_ENV['DB_PASS'] ?? '',
        'port'    => $_ENV['DB_PORT'] ?? '3306',
        'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ],
    'app' => [
        'name' => 'CTMS',
        'env'  => 'development',
    ]
];
