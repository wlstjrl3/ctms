<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=CTMS', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- teachers table schema ---\n";
    $stmt = $db->query("DESCRIBE teachers");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- users table schema ---\n";
    $stmt = $db->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
