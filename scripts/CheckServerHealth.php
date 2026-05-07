<?php
/**
 * Server Health & Query Test Script
 */
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$db = App\Core\App::getInstance()->db();
$service = new App\Service\ParishService();

echo "--- Server Health Check ---\n";

try {
    echo "1. Testing Dioceses Query... ";
    $d = $service->getDioceses(true);
    echo "OK (Count: " . count($d) . ")\n";

    echo "2. Testing Districts Query... ";
    $dist = $service->getDistricts(null, true);
    echo "OK (Count: " . count($dist) . ")\n";

    echo "3. Testing Parish List Query... ";
    $p = $service->getParishList([], 1, 15);
    echo "OK (Count: " . count($p) . ")\n";

    echo "4. Checking Write Permissions for scratch/... ";
    $testFile = __DIR__ . '/../scratch/test_write.txt';
    if (@file_put_contents($testFile, "test")) {
        echo "OK\n";
        unlink($testFile);
    } else {
        echo "FAILED (Check permissions!)\n";
    }

    echo "\n--- All Service Tests Passed ---\n";
    echo "If the page still 502s, the issue is likely in the VIEW (rendering) or Nginx timeout.\n";

} catch (\Exception $e) {
    echo "FAILED\nError: " . $e->getMessage() . "\n";
}
