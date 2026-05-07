<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\App;

class ScraperService
{
    private $db;
    private $parishService;

    public function __construct()
    {
        $this->db = App::getInstance()->db();
        $this->parishService = new ParishService();
    }

    public function syncFromDiocese(): array
    {
        set_time_limit(0);
        $this->preSyncCleanup(); // Auto-fix duplicates and broken links before sync

        $urls = [
            '제1대리구' => 'https://www.casuwon.or.kr/parish/parish/1?parish=%EC%A0%9C1%EB%8C%80%EB%A6%AC%EA%B5%AC',
            '제2대리구' => 'https://www.casuwon.or.kr/parish/parish/1?parish=%EC%A0%9C2%EB%8C%80%EB%A6%AC%EA%B5%AC'
        ];

        $stats = [
            'vicariates' => 0,
            'districts' => 0,
            'parishes' => 0,
            'updated' => 0,
            'created' => 0
        ];

        foreach ($urls as $vicariateName => $url) {
            $html = $this->fetchUrl($url);
            if (!$html) continue;
            
            $data = $this->parseParishList($html, $vicariateName);
            
            // Sync with DB
            $res = $this->updateDatabase($data);
            $stats['vicariates']++;
            $stats['districts'] += $res['districts'];
            $stats['parishes'] += $res['parishes'];
            $stats['updated'] += $res['updated'];
            $stats['created'] += $res['created'];
        }

        return $stats;
    }

    /**
     * Self-healing logic to fix duplicates and broken internal table links before sync
     */
    private function preSyncCleanup(): void {
        // 1. Remove Duplicate Districts/Parishes in ORG_INFO
        $this->db->query("DELETE o1 FROM ORG_INFO o1 INNER JOIN ORG_INFO o2 
                         WHERE o1.ORG_CD LIKE '1309%' AND o1.ORG_CD > o2.ORG_CD AND o1.ORG_NM = o2.ORG_NM");
        
        $this->db->query("DELETE o1 FROM ORG_INFO o1 INNER JOIN ORG_INFO o2 
                         WHERE o1.ORG_CD LIKE '1311%' AND o1.ORG_CD > o2.ORG_CD AND o1.ORG_NM = o2.ORG_NM AND o1.UPPR_ORG_CD = o2.UPPR_ORG_CD");

        // 2. Re-sync Internal Reference Tables (vicariates, districts) from ORG_INFO
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->query("TRUNCATE TABLE vicariates");
        $this->db->query("TRUNCATE TABLE districts");
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");

        $vics = $this->db->fetchAll("SELECT ORG_CD, ORG_NM FROM ORG_INFO WHERE ORG_CD LIKE '1306%'");
        foreach ($vics as $v) {
            $this->db->query("INSERT INTO vicariates (name, code) VALUES (?, ?)", [$v['ORG_NM'], (string)$v['ORG_CD']]);
        }

        $dists = $this->db->fetchAll("SELECT ORG_CD, ORG_NM, UPPR_ORG_CD FROM ORG_INFO WHERE ORG_CD LIKE '1309%'");
        foreach ($dists as $d) {
            $vic = $this->db->fetch("SELECT id FROM vicariates WHERE code = ?", [(string)$d['UPPR_ORG_CD']]);
            $this->db->query("INSERT INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)", [$vic['id'] ?? 0, $d['ORG_NM'], (string)$d['ORG_CD']]);
        }
        
        // 3. Fix broken parish -> district links in ORG_INFO by matching district name flexibly
        $aliveDists = $this->db->fetchAll("SELECT ORG_CD, ORG_NM FROM ORG_INFO WHERE ORG_CD LIKE '1309%'");
        $distNameMap = [];
        foreach ($aliveDists as $ad) {
            $name = trim($ad['ORG_NM']);
            $distNameMap[$name] = $ad['ORG_CD'];
            $distNameMap[str_replace('지구', '', $name)] = $ad['ORG_CD']; // Add "권선" if name is "권선지구"
        }

        $parishesToFix = $this->db->fetchAll("SELECT p.org_cd, p.district_name FROM parishes p");
        foreach ($parishesToFix as $pf) {
            $pDistName = trim($pf['district_name']);
            $correctDistCd = $distNameMap[$pDistName] ?? $distNameMap[str_replace('지구', '', $pDistName)] ?? null;
            if ($correctDistCd) {
                $this->db->query("UPDATE ORG_INFO SET UPPR_ORG_CD = ? WHERE ORG_CD = ?", [$correctDistCd, $pf['org_cd']]);
            }
        }
    }

    private function fetchUrl(string $url): ?string
    {
        $rootDir = realpath(__DIR__ . '/../../') . '/';
        $cookieFile = $rootDir . 'scratch/scraper_cookies.txt';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');
        
        // Load cookies if exist
        if (file_exists($cookieFile)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        }
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: null;
    }

    private function parseParishList(string $html, string $vicariateName): array
    {
        $results = ['name' => $vicariateName, 'districts' => []];
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new \DOMXpath($dom);

        // Find district headers (usually h3 or h4 with ○)
        $nodes = $xpath->query("//h3 | //h4 | //div[contains(@class, 'title')]");
        foreach ($nodes as $node) {
            $text = trim($node->nodeValue);
            // Example: ○ 권선지구 본당 : 12개
            if (preg_match('/지구\s*본당\s*:\s*(\d+)개/', $text)) {
                $districtName = trim(explode('본당', $text)[0]);
                $districtName = ltrim($districtName, '○ ');
                $parishList = [];

                $container = null;
                $curr = $node->nextSibling;
                while ($curr) {
                    if ($curr->nodeType === XML_ELEMENT_NODE && ($curr->nodeName === 'div' || $curr->nodeName === 'ul')) {
                        $container = $curr;
                        break;
                    }
                    $curr = $curr->nextSibling;
                }

                if ($container) {
                    $links = $xpath->query(".//a[contains(@href, 'serial=')]", $container);
                    foreach ($links as $link) {
                        $href = $link->getAttribute('href');
                        $name = trim($link->nodeValue);
                        if (preg_match('/serial=(\d+)/', $href, $m)) {
                            $parishList[] = ['name' => $name, 'code' => $m[1]];
                        }
                    }
                }

                $results['districts'][] = ['name' => $districtName, 'parishes' => $parishList];
            }
        }
        return $results;
    }

    private function updateDatabase(array $data): array
    {
        $stats = ['districts' => 0, 'parishes' => 0, 'updated' => 0, 'created' => 0];
        
        // 1. Vicariate
        $vicName = $data['name'];
        $vic = $this->db->fetch("SELECT * FROM vicariates WHERE name = ?", [$vicName]);
        if (!$vic) {
            $last = $this->db->fetch("SELECT MAX(CAST(code AS UNSIGNED)) as m FROM vicariates WHERE code LIKE '1306%'");
            $nextCode = $last['m'] ? (int)$last['m'] + 1 : 13060001;
            $this->db->query("INSERT INTO ORG_INFO (ORG_CD, ORG_NM, UPPR_ORG_CD, ORG_TYPE, USE_YN, REG_DT) VALUES (?, ?, 13, 8, 'Y', NOW())", [$nextCode, $vicName]);
            $this->db->query("INSERT INTO vicariates (name, code) VALUES (?, ?)", [$vicName, (string)$nextCode]);
            $vicId = $this->db->getPdo()->lastInsertId();
            $vicOrgCd = $nextCode;
        } else {
            $vicId = $vic['id'];
            $vicOrgCd = (int)$vic['code'];
        }

        foreach ($data['districts'] as $dData) {
            $distName = $dData['name'];
            $stats['districts']++;

            $dist = $this->db->fetch("SELECT * FROM districts WHERE name = ? AND vicariate_id = ?", [$distName, $vicId]);
            if (!$dist) {
                $last = $this->db->fetch("SELECT MAX(CAST(code AS UNSIGNED)) as m FROM districts WHERE code LIKE '1309%'");
                $nextCode = $last['m'] ? (int)$last['m'] + 1 : 13090001;
                $this->db->query("INSERT INTO ORG_INFO (ORG_CD, ORG_NM, UPPR_ORG_CD, ORG_TYPE, USE_YN, REG_DT) VALUES (?, ?, ?, 9, 'Y', NOW())", [$nextCode, $distName, $vicOrgCd]);
                $this->db->query("INSERT INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)", [$vicId, $distName, (string)$nextCode]);
                $distId = $this->db->getPdo()->lastInsertId();
                $distOrgCd = $nextCode;
                $stats['created']++;
            } else {
                $distId = $dist['id'];
                $distOrgCd = (int)$dist['code'];
            }

            foreach ($dData['parishes'] as $pData) {
                $stats['parishes']++;
                $pName = $pData['name'];
                $pSerial = $pData['code'];

                // Try to match by website serial first
                $parish = $this->db->fetch("SELECT * FROM parishes WHERE parish_code = ?", [$pSerial]);
                if ($parish) {
                    // FORCE UPDATE Hierarchy
                    $this->db->query("UPDATE parishes SET parish_name = ?, district_id = ?, district_code = ? WHERE id = ?", 
                        [$pName, $distId, (string)$distOrgCd, $parish['id']]);
                    $this->db->query("UPDATE ORG_INFO SET ORG_NM = ?, UPPR_ORG_CD = ? WHERE ORG_CD = ?", 
                        [$pName, $distOrgCd, $parish['org_cd']]);
                    $stats['updated']++;
                } else {
                    // Try to match by name
                    $parishByName = $this->db->fetch("SELECT * FROM parishes WHERE parish_name = ? OR parish_name = ?", [$pName, $pName . '성당']);
                    if ($parishByName) {
                        $this->db->query("UPDATE parishes SET parish_code = ?, district_id = ?, district_code = ? WHERE id = ?", 
                            [$pSerial, $distId, (string)$distOrgCd, $parishByName['id']]);
                        $this->db->query("UPDATE ORG_INFO SET UPPR_ORG_CD = ? WHERE ORG_CD = ?", 
                            [$distOrgCd, $parishByName['org_cd']]);
                        $stats['updated']++;
                    } else {
                        // Create new
                        $last = $this->db->fetch("SELECT MAX(CAST(ORG_CD AS UNSIGNED)) as m FROM ORG_INFO WHERE ORG_CD LIKE '1311%'");
                        $nextOrgCd = $last['m'] ? (int)$last['m'] + 1 : 13110001;
                        $this->db->query("INSERT INTO ORG_INFO (ORG_CD, ORG_NM, UPPR_ORG_CD, ORG_TYPE, USE_YN, REG_DT) VALUES (?, ?, ?, 11, 'Y', NOW())", [$nextOrgCd, $pName, $distOrgCd]);
                        $this->db->query("INSERT INTO parishes (org_cd, district_id, parish_name, parish_code, district_code) VALUES (?, ?, ?, ?, ?)", 
                            [$nextOrgCd, $distId, $pName, $pSerial, (string)$distOrgCd]);
                        $stats['created']++;
                    }
                }
            }
        }

        return $stats;
    }
}
