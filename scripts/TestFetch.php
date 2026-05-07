<?php
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$s = new App\Service\ParishService();
$d = $s->getDioceses();
print_r($d);

$dists = $s->getDistricts();
print_r(array_slice($dists, 0, 2)); // Print just the first two districts

