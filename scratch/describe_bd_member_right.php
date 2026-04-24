<?php
$pdo = new PDO('mysql:host=localhost;dbname=CTMS', 'root', '');
$stmt = $pdo->query('DESCRIBE bd_member_right');
while($row = $stmt->fetch()) { echo $row[0] . ' (' . $row[1] . ')' . PHP_EOL; }
