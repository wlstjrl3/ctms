<?php
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$s = new App\Service\ParishService();
$p = $s->getParishList([], 1, 2);
print_r($p);
