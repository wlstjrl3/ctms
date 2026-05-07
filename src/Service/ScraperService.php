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

    private function fetchUrl(string $url): ?string
    {
        $rootDir = realpath(__DIR__ . '/../../') . '/';
        $cookieFile = $rootDir . 'scratch/scraper_cookies.txt';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
        
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Upgrade-Insecure-Requests: 1'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $html = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Scraper CURL Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $html ?: null;
    }

    private function parseParishList(string $html, string $vicariateName): array
    {
        $dom = new \DOMDocument();
        // Handle UTF-8 encoding properly for DOMDocument
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new \DOMXPath($dom);

        $results = [
            'name' => $vicariateName,
            'districts' => []
        ];

        // Districts can be in div, h3, or h4
        $nodes = $xpath->query("//div[contains(@class, 'parish-list-tit')] | //h3[contains(text(), '지구')] | //h4[contains(text(), '지구')]");
        
        foreach ($nodes as $node) {
            $text = trim($node->nodeValue);
            // Example: ○ 권선지구 본당 : 12개 or 수지지구 본당 : 8개
            if (preg_match('/지구\s*본당\s*:\s*(\d+)개/', $text)) {
                $districtName = trim(explode('본당', $text)[0]);
                $districtName = ltrim($districtName, '○ ');
                $parishList = [];

                // Find the container that has the parishes (usually the next div)
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
                            $parishList[] = [
                                'name' => $name,
                                'code' => $m[1]
                            ];
                        }
                    }
                }

                $results['districts'][] = [
                    'name' => $districtName,
                    'parishes' => $parishList
                ];
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
            // Find next code for vicariate
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
                // Create district
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
                $pSerial = $pData['code']; // Website's serial

                // 1. Try to match by the stable website serial (parish_code)
                $parish = $this->db->fetch("SELECT * FROM parishes WHERE parish_code = ?", [$pSerial]);
                
                if ($parish) {
                    // SELF-HEALING: Check if there's ANOTHER record with the same name (legacy duplicate)
                    $duplicates = $this->db->fetchAll("SELECT * FROM parishes WHERE (parish_name = ? OR parish_name = ?) AND id != ?", [$pName, $pName . '성당', $parish['id']]);
                    foreach ($duplicates as $dup) {
                        // Merge logic: in this simple case, just delete the duplicate since ID $parish['id'] is already mapped correctly
                        $this->db->query("DELETE FROM parishes WHERE id = ?", [$dup['id']]);
                        $this->db->query("DELETE FROM ORG_INFO WHERE ORG_CD = ?", [$dup['org_cd']]);
                    }

                    // Update if name changed or if it moved to a different district
                    if ($parish['parish_name'] !== $pName || (int)$parish['district_id'] !== (int)$distId) {
                        $this->db->query("UPDATE parishes SET parish_name = ?, district_id = ?, district_code = ? WHERE id = ?", 
                            [$pName, $distId, (string)$distOrgCd, $parish['id']]);
                        
                        // Also update the linked ORG_INFO record
                        $this->db->query("UPDATE ORG_INFO SET ORG_NM = ?, UPPR_ORG_CD = ? WHERE ORG_CD = ?", 
                            [$pName, $distOrgCd, $parish['org_cd']]);
                        
                        $stats['updated']++;
                    }
                } else {
                    // 2. Fallback: try to match by name (if no serial match) - global search to catch district moves
                    $parishByName = $this->db->fetch("SELECT * FROM parishes WHERE parish_name = ? OR parish_name = ?", [$pName, $pName . '성당']);
                    
                    if ($parishByName) {
                        // Found by name, update its code and move it to the correct district
                        $this->db->query("UPDATE parishes SET parish_code = ?, district_id = ?, district_code = ? WHERE id = ?", 
                            [$pSerial, $distId, (string)$distOrgCd, $parishByName['id']]);
                        
                        // Also update ORG_INFO parent
                        $this->db->query("UPDATE ORG_INFO SET UPPR_ORG_CD = ? WHERE ORG_CD = ?", 
                            [$distOrgCd, $parishByName['org_cd']]);
                        
                        $stats['updated']++;

                        // SELF-HEALING: Double check if we still have duplicates after updating this one
                        $extraDups = $this->db->fetchAll("SELECT * FROM parishes WHERE (parish_name = ? OR parish_name = ?) AND id != ?", [$pName, $pName . '성당', $parishByName['id']]);
                        foreach ($extraDups as $dup) {
                            $this->db->query("DELETE FROM parishes WHERE id = ?", [$dup['id']]);
                            $this->db->query("DELETE FROM ORG_INFO WHERE ORG_CD = ?", [$dup['org_cd']]);
                        }
                    } else {
                        // 3. Create new parish
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
