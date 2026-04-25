<?php
/**
 * Migration: Setup Organization Tables
 * Creates vicariates and districts tables and links them to parishes.
 */

// Simple Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\App;

$db = App::getInstance()->db();

echo "Starting Organization Schema Migration...\n";

try {
    // 1. Create vicariates table
    echo "- Creating vicariates table...\n";
    $db->query("CREATE TABLE IF NOT EXISTS vicariates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(20) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. Create districts table
    echo "- Creating districts table...\n";
    $db->query("CREATE TABLE IF NOT EXISTS districts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vicariate_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        code VARCHAR(20) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vicariate_id) REFERENCES vicariates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Migrate unique Vicariates from parishes
    echo "- Migrating vicariates data...\n";
    $vicariates = $db->fetchAll("SELECT DISTINCT diocese_name, diocese_code FROM parishes WHERE diocese_code IS NOT NULL AND diocese_code != ''");
    foreach ($vicariates as $v) {
        $db->query("INSERT IGNORE INTO vicariates (name, code) VALUES (?, ?)", [$v['diocese_name'], $v['diocese_code']]);
    }

    // 4. Migrate unique Districts from parishes
    echo "- Migrating districts data...\n";
    $districts = $db->fetchAll("SELECT DISTINCT district_name, district_code, diocese_code FROM parishes WHERE district_code IS NOT NULL AND district_code != ''");
    foreach ($districts as $d) {
        $vic = $db->fetch("SELECT id FROM vicariates WHERE code = ?", [$d['diocese_code']]);
        if ($vic) {
            $db->query("INSERT IGNORE INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)", [$vic['id'], $d['district_name'], $d['district_code']]);
        }
    }

    // 5. Add district_id to parishes
    echo "- Updating parishes table structure...\n";
    // Check if column exists first to avoid error on reruns
    $columns = $db->fetchAll("SHOW COLUMNS FROM parishes LIKE 'district_id'");
    if (empty($columns)) {
        $db->query("ALTER TABLE parishes ADD COLUMN district_id INT AFTER id");
    }

    // 6. Link parishes to districts
    echo "- Linking parishes to districts...\n";
    $parishes = $db->fetchAll("SELECT id, district_code FROM parishes WHERE district_code IS NOT NULL AND district_code != ''");
    foreach ($parishes as $p) {
        $dist = $db->fetch("SELECT id FROM districts WHERE code = ?", [$p['district_code']]);
        if ($dist) {
            $db->query("UPDATE parishes SET district_id = ? WHERE id = ?", [$dist['id'], $p['id']]);
        }
    }

    echo "Migration Completed Successfully.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
