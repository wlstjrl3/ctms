<?php
/**
 * Final Database Link Restoration Script (Enhanced)
 */
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$db = App\Core\App::getInstance()->db();

echo "=== Final Link Restoration Started ===\n";

// 1. Re-sync vicariates & districts tables from ORG_INFO
echo "Syncing vicariates/districts internal tables...\n";
$db->query("SET FOREIGN_KEY_CHECKS = 0");
$db->query("TRUNCATE TABLE vicariates");
$db->query("TRUNCATE TABLE districts");
$db->query("SET FOREIGN_KEY_CHECKS = 1");

$vics = $db->fetchAll("SELECT ORG_CD, ORG_NM FROM ORG_INFO WHERE ORG_CD LIKE '1306%'");
foreach ($vics as $v) {
    $db->query("INSERT INTO vicariates (name, code) VALUES (?, ?)", [$v['ORG_NM'], (string)$v['ORG_CD']]);
}

$dists = $db->fetchAll("SELECT ORG_CD, ORG_NM, UPPR_ORG_CD FROM ORG_INFO WHERE ORG_CD LIKE '1309%'");
foreach ($dists as $d) {
    $vic = $db->fetch("SELECT id FROM vicariates WHERE code = ?", [(string)$d['UPPR_ORG_CD']]);
    $db->query("INSERT INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)", [$vic['id'] ?? 0, $d['ORG_NM'], (string)$d['ORG_CD']]);
}

// 2. Fix broken links in ORG_INFO for parishes by matching district name from parishes table
echo "Restoring broken parish -> district links...\n";
$aliveDists = $db->fetchAll("SELECT ORG_CD, ORG_NM FROM ORG_INFO WHERE ORG_CD LIKE '1309%'");
$distNameMap = [];
foreach ($aliveDists as $ad) $distNameMap[trim($ad['ORG_NM'])] = $ad['ORG_CD'];

$parishesToFix = $db->fetchAll("SELECT p.org_cd, p.district_name FROM parishes p");
foreach ($parishesToFix as $pf) {
    $correctDistCd = $distNameMap[trim($pf['district_name'])] ?? null;
    if ($correctDistCd) {
        $db->query("UPDATE ORG_INFO SET UPPR_ORG_CD = ? WHERE ORG_CD = ?", [$correctDistCd, $pf['org_cd']]);
    }
}

// 3. Final cleanup of any orphaned ORG_INFO rows
echo "Removing orphaned records...\n";
$db->query("DELETE FROM ORG_INFO WHERE ORG_CD LIKE '1311%' AND UPPR_ORG_CD NOT IN (SELECT ORG_CD FROM ORG_INFO WHERE ORG_CD LIKE '1309%')");

echo "\n=== Restoration Completed. Please run Sync from Diocese button now. ===\n";
