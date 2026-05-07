<?php
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$db = App\Core\App::getInstance()->db();
$res = $db->fetchAll("SELECT ORG_CD, ORG_NM, USE_YN FROM ORG_INFO WHERE ORG_CD LIKE '1306%'");
print_r($res);
