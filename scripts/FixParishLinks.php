<?php
/**
 * CTMS Server Parish Link & Duplicate Fixer
 * 
 * This script resolves issues where teachers' parish_id links are broken due to 
 * duplicate parish records or ID mismatches between local and server environments.
 */

require __DIR__ . '/../src/Core/App.php';

// Custom autoloader for script environment
spl_autoload_register(function($c){
    $p = 'App\\';
    $b = __DIR__ . '/../src/';
    $l = strlen($p);
    if (strncmp($p, $c, $l) !== 0) return;
    $r = substr($c, $l);
    $f = $b . str_replace('\\', '/', $r) . '.php';
    if (file_exists($f)) require $f;
});

$db = App\Core\App::getInstance()->db();

echo "=== Starting Parish Link & Duplicate Fixer ===\n";

// 1. Identify and Merge Duplicates
$parishes = $db->fetchAll("SELECT id, parish_name, parish_code, org_cd FROM parishes");
$nameGroups = [];
foreach ($parishes as $p) {
    // Normalize name to handle "의왕" vs "의왕성당"
    $name = str_replace('성당', '', $p['parish_name']);
    $nameGroups[$name][] = $p;
}

foreach ($nameGroups as $name => $members) {
    if (count($members) > 1) {
        echo "\nFound duplicates for [$name]: " . count($members) . " records.\n";
        
        // Strategy: 
        // 1. Prioritize records with 5-digit website serial codes (e.g. 13060)
        // 2. Then prioritize records with 8-digit org_cd (1311....)
        // 3. Finally, use the latest ID
        usort($members, function($a, $b) {
            $aSerScore = (is_numeric($a['parish_code']) && strlen($a['parish_code']) >= 4) ? 1000 : 0;
            $bSerScore = (is_numeric($b['parish_code']) && strlen($b['parish_code']) >= 4) ? 1000 : 0;
            $aOrgScore = (strlen((string)$a['org_cd']) >= 8) ? 100 : 0;
            $bOrgScore = (strlen((string)$b['org_cd']) >= 8) ? 100 : 0;
            
            return ($bSerScore + $bOrgScore + $b['id']) - ($aSerScore + $aOrgScore + $a['id']);
        });
        
        $winner = $members[0];
        echo "  -> Keeping Winner: ID {$winner['id']} (Code: {$winner['parish_code']}, OrgCD: {$winner['org_cd']})\n";
        
        for ($i = 1; $i < count($members); $i++) {
            $loser = $members[$i];
            echo "  -> Merging Loser: ID {$loser['id']} (Code: {$loser['parish_code']}) into ID {$winner['id']}\n";
            
            // Move Teachers linked to this loser
            $stmtT = $db->query("UPDATE teachers SET parish_id = ? WHERE parish_id = ?", [$winner['id'], $loser['id']]);
            echo "     * Moved " . $stmtT->rowCount() . " teachers.\n";
            
            // Move Users linked to this loser
            $stmtU = $db->query("UPDATE users SET parish_id = ? WHERE parish_id = ?", [$winner['id'], $loser['id']]);
            echo "     * Moved " . $stmtU->rowCount() . " users.\n";
            
            // Delete the duplicate parish
            $db->query("DELETE FROM parishes WHERE id = ?", [$loser['id']]);
            
            // Delete matching ORG_INFO only if it's not the same org_cd as winner
            if ($loser['org_cd'] != $winner['org_cd']) {
                $db->query("DELETE FROM ORG_INFO WHERE ORG_CD = ?", [$loser['org_cd']]);
            }
        }
    }
}

echo "\n2. Final Audit of Broken Links...\n";
$broken = $db->fetch("SELECT COUNT(*) as cnt FROM teachers WHERE parish_id IS NOT NULL AND parish_id NOT IN (SELECT id FROM parishes)");
if ($broken['cnt'] > 0) {
    echo "   WARNING: There are still {$broken['cnt']} teachers with invalid parish_id links.\n";
} else {
    echo "   SUCCESS: All teachers are now correctly linked to existing parishes.\n";
}

echo "\n=== Fix Completed ===\n";
