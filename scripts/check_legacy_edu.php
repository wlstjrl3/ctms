<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=CTMS', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- bd_member_education schema --- \n";
    $stmt = $db->query("DESCRIBE bd_member_education");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- Sample data from bd_member_education --- \n";
    $stmt = $db->query("SELECT * FROM bd_member_education LIMIT 3");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
