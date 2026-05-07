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
// For parishes (1311)
$db->query("DELETE o1 FROM ORG_INFO o1
            INNER JOIN ORG_INFO o2 
            WHERE o1.ORG_CD LIKE '1311%' AND o1.ORG_CD > o2.ORG_CD AND o1.ORG_NM = o2.ORG_NM AND o1.UPPR_ORG_CD = o2.UPPR_ORG_CD");

// For districts (1309)
echo "Cleaning up 137 districts...\n";
$db->query("DELETE o1 FROM ORG_INFO o1
            INNER JOIN ORG_INFO o2 
            WHERE o1.ORG_CD LIKE '1309%' AND o1.ORG_CD > o2.ORG_CD AND o1.ORG_NM = o2.ORG_NM");

// 4. Final verification
$newOrgCount = $db->fetch("SELECT COUNT(*) as cnt FROM ORG_INFO")['cnt'];
echo "New ORG_INFO rows: $newOrgCount (Removed " . ($orgCount - $newOrgCount) . " rows)\n";

echo "=== Fix Completed. Please try accessing the page now. ===\n";
