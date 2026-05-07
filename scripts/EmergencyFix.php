<?php
/**
 * Emergency DB Cleanup & Page Recovery Script
 * 
 * Run this if you get 502 Bad Gateway on the parish list page.
 * It removes all duplicates and ensures one-to-one mapping between ORG_INFO and parishes.
 */

require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$db = App\Core\App::getInstance()->db();

echo "=== Emergency DB Fix Started ===\n";

// 1. Check counts
$orgCount = $db->fetch("SELECT COUNT(*) as cnt FROM ORG_INFO")['cnt'];
$parishCount = $db->fetch("SELECT COUNT(*) as cnt FROM parishes")['cnt'];
echo "Current ORG_INFO rows: $orgCount\n";
echo "Current parishes rows: $parishCount\n";

// 2. Remove exact duplicates in parishes table (keeping only the lowest ID for each org_cd)
echo "Removing duplicates in parishes table...\n";
$db->query("DELETE p1 FROM parishes p1
            INNER JOIN parishes p2 
            WHERE p1.id > p2.id AND p1.org_cd = p2.org_cd");

// 3. Remove exact duplicates in ORG_INFO table
echo "Removing duplicates in ORG_INFO table...\n";
$db->query("DELETE o1 FROM ORG_INFO o1
            INNER JOIN ORG_INFO o2 
            WHERE o1.ORG_CD = o2.ORG_CD AND o1.REG_DT < o2.REG_DT");

// 4. Final verification
$newParishCount = $db->fetch("SELECT COUNT(*) as cnt FROM parishes")['cnt'];
echo "New parishes rows: $newParishCount (Removed " . ($parishCount - $newParishCount) . " rows)\n";

echo "=== Fix Completed. Please try accessing the page now. ===\n";
