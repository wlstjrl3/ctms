<?php
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$db = App\Core\App::getInstance()->db();

echo "--- Current Database State ---\n";

$vicCount = $db->fetch("SELECT COUNT(*) as cnt FROM ORG_INFO WHERE ORG_CD LIKE '1306%'")['cnt'];
$distCount = $db->fetch("SELECT COUNT(*) as cnt FROM ORG_INFO WHERE ORG_CD LIKE '1309%'")['cnt'];
$parCount = $db->fetch("SELECT COUNT(*) as cnt FROM ORG_INFO WHERE ORG_CD LIKE '1311%'")['cnt'];

echo "Vicariates (1306): $vicCount\n";
echo "Districts (1309): $distCount\n";
echo "Parishes (1311): $parCount\n";

if ($distCount > 0) {
    echo "Sample District: ";
    print_r($db->fetch("SELECT * FROM ORG_INFO WHERE ORG_CD LIKE '1309%' LIMIT 1"));
}

if ($parCount > 0) {
    echo "Sample Parish: ";
    print_r($db->fetch("SELECT * FROM ORG_INFO WHERE ORG_CD LIKE '1311%' LIMIT 1"));
}

echo "\n--- Parishes Table ---\n";
echo "Total in parishes table: " . $db->fetch("SELECT COUNT(*) as cnt FROM parishes")['cnt'] . "\n";

echo "---------------------------\n";
