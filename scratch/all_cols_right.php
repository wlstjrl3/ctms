<?php
$pdo = new PDO('mysql:host=localhost;dbname=CTMS', 'root', '');
$stmt = $pdo->query('SHOW COLUMNS FROM bd_member_right');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total columns: " . count($cols) . "\n";
foreach ($cols as $col) {
    echo $col['Field'] . "\n";
}
