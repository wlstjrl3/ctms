<?php
/**
 * Database Indexing Script
 * Adds necessary indexes to improve performance of teacher and user management.
 */

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['db'];

try {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
    $db = new PDO($dsn, $dbConfig['user'], $dbConfig['pass']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Starting Indexing Process...\n";

    function addIndex($db, $table, $indexName, $columns) {
        $stmt = $db->prepare("SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $stmt->execute([$table, $indexName]);
        if ($stmt->fetchColumn() == 0) {
            echo "- Adding index $indexName to $table...\n";
            $db->exec("CREATE INDEX $indexName ON $table ($columns)");
        } else {
            echo "- Index $indexName already exists on $table.\n";
        }
    }

    // 1. Teachers Table
    addIndex($db, 'teachers', 'idx_teachers_name', 'name');
    addIndex($db, 'teachers', 'idx_teachers_birth_date', 'birth_date');
    addIndex($db, 'teachers', 'idx_teachers_status_dept', 'status, department');
    addIndex($db, 'teachers', 'idx_teachers_mobile', 'mobile_phone');
    addIndex($db, 'teachers', 'idx_teachers_bname', 'baptismal_name');
    addIndex($db, 'teachers', 'idx_teachers_parish_name', 'parish_id, name');

    // 2. Teacher Tenure Table
    addIndex($db, 'teacher_tenure', 'idx_tenure_start_year', 'start_year');

    // 3. Users Table
    addIndex($db, 'users', 'idx_users_role', 'role');
    addIndex($db, 'users', 'idx_users_name', 'name');

    // 4. Parishes Table
    addIndex($db, 'parishes', 'idx_parishes_name', 'parish_name');

    echo "Indexing completed successfully!\n";

} catch (Exception $e) {
    // MySQL CREATE INDEX IF NOT EXISTS might not be supported in older versions, 
    // handle "Duplicate key name" errors if they occur.
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "Some indexes already exist. Continuing...\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
