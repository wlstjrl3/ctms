<?php
/**
 * Force Heal & Diagnostic Script
 */
require __DIR__ . '/../src/Core/App.php';
spl_autoload_register(function($c){$p='App\\';$b=__DIR__.'/../src/';$l=strlen($p);if(strncmp($p,$c,$l)!==0)return;$r=substr($c,$l);$f=$b.str_replace('\\','/',$r).'.php';if(file_exists($f))require $f;});

$db = App\Core\App::getInstance()->db();

echo "--- Force Healing Started ---\n";

try {
    // 1. Cleanup ORG_INFO Duplicates
    echo "1. Cleaning up ORG_INFO duplicates... ";
    $db->query("DELETE o1 FROM ORG_INFO o1 INNER JOIN ORG_INFO o2 
                WHERE o1.ORG_CD LIKE '1309%' AND o1.ORG_CD > o2.ORG_CD AND o1.ORG_NM = o2.ORG_NM");
    $db->query("DELETE o1 FROM ORG_INFO o1 INNER JOIN ORG_INFO o2 
                WHERE o1.ORG_CD LIKE '1311%' AND o1.ORG_CD > o2.ORG_CD AND o1.ORG_NM = o2.ORG_NM AND o1.UPPR_ORG_CD = o2.UPPR_ORG_CD");
    echo "OK\n";

    // 2. Re-sync Internal Tables
    echo "2. Re-syncing internal tables... ";
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    $db->query("TRUNCATE TABLE vicariates");
    $db->query("TRUNCATE TABLE districts");
    $db->query("SET FOREIGN_KEY_CHECKS = 1");

    $vics = $db->fetchAll("SELECT ORG_CD, ORG_NM FROM ORG_INFO WHERE ORG_CD LIKE '1306%'");
    echo "(Found " . count($vics) . " vicariates) ";
    foreach ($vics as $v) {
        $db->query("INSERT INTO vicariates (name, code) VALUES (?, ?)", [$v['ORG_NM'], (string)$v['ORG_CD']]);
    }

    $dists = $db->fetchAll("SELECT ORG_CD, ORG_NM, UPPR_ORG_CD FROM ORG_INFO WHERE ORG_CD LIKE '1309%'");
    echo "(Found " . count($dists) . " districts) ";
    foreach ($dists as $d) {
        $vicRow = $db->fetch("SELECT id FROM vicariates WHERE code = ?", [(string)$d['UPPR_ORG_CD']]);
        $db->query("INSERT INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)", [$vicRow['id'] ?? 0, $d['ORG_NM'], (string)$d['ORG_CD']]);
    }
    echo "OK\n";

    // 3. Re-link Parishes
    echo "3. Re-linking parishes by name matching... ";
    $aliveDists = $db->fetchAll("SELECT ORG_CD, ORG_NM FROM ORG_INFO WHERE ORG_CD LIKE '1309%'");
    $distNameMap = [];
    foreach ($aliveDists as $ad) {
        $name = trim($ad['ORG_NM']);
        $distNameMap[$name] = $ad['ORG_CD'];
        $distNameMap[str_replace('지구', '', $name)] = $ad['ORG_CD'];
    }

    $parishesToFix = $db->fetchAll("SELECT p.org_cd, p.district_name FROM parishes p");
    $fixedCount = 0;
    foreach ($parishesToFix as $pf) {
        $pDistName = trim($pf['district_name']);
        $correctDistCd = $distNameMap[$pDistName] ?? $distNameMap[str_replace('지구', '', $pDistName)] ?? null;
        if ($correctDistCd) {
            $db->query("UPDATE ORG_INFO SET UPPR_ORG_CD = ? WHERE ORG_CD = ?", [$correctDistCd, $pf['org_cd']]);
            $fixedCount++;
        }
    }
    echo "OK (Fixed $fixedCount links)\n";

    echo "\n--- Force Heal Completed Successfully ---\n";
    echo "Please check the web page now. If still empty, we need to check ORG_INFO values.\n";

} catch (\Exception $e) {
    echo "FAILED\nError: " . $e->getMessage() . "\n";
}
