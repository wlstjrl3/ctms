<?php
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$scraper = new App\Service\ScraperService();
echo "Starting sync...\n";
$stats = $scraper->syncFromDiocese();
print_r($stats);
echo "Done.\n";
