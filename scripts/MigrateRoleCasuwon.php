<?php
/**
 * Migration: Rename role 'office' to 'casuwon'
 */

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
    $db = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting Role Migration...\n";

    // 1. Update ENUM definition
    echo "- Updating 'users.role' ENUM definition...\n";
    $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('casuwon', 'diocese', 'bondang') DEFAULT 'bondang'");

    // 2. Update existing records
    echo "- Converting 'office' records to 'casuwon'...\n";
    // If we just modified the ENUM, previous 'office' values might have been lost or set to empty/invalid 
    // depending on MySQL version if 'office' wasn't in the new ENUM list.
    // Better to ADD 'casuwon' first, update, then REMOVE 'office'.
    
    // Re-do for safety:
    $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('office', 'casuwon', 'diocese', 'bondang') DEFAULT 'bondang'");
    $db->exec("UPDATE users SET role = 'casuwon' WHERE role = 'office'");
    $db->exec("ALTER TABLE users MODIFY COLUMN role ENUM('casuwon', 'diocese', 'bondang') DEFAULT 'bondang'");

    echo "Role migration completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
