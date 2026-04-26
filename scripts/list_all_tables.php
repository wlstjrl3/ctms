<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=CTMS', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- All Tables --- \n";
    $stmt = $db->query("SHOW TABLES");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
