<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=CTMS', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- bd_member_csdate sample --- \n";
    $stmt = $db->query("SELECT * FROM bd_member_csdate LIMIT 3");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- tch_tml sample --- \n";
    $stmt = $db->query("SELECT * FROM tch_tml LIMIT 3");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
