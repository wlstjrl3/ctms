<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\App;

class ParishService
{
    private $db;

    public function __construct()
    {
        $this->db = App::getInstance()->db();
    }

    /**
     * Get list of parishes with optional filters.
     * Reads from ORG_INFO (1311xxxx) joined with parent districts/vicariates.
     */
    public function getParishList(array $filters = [], int $page = 1, int $pageSize = 15): array
    {
        $offset = ($page - 1) * $pageSize;
        $where = "WHERE o.ORG_CD LIKE '1311%'";
        $params = [];

        if (!empty($filters['name'])) {
            $where .= " AND o.ORG_NM LIKE ?";
            $params[] = "%{$filters['name']}%";
        }
        if (!empty($filters['parish_code'])) {
            $where .= " AND p.parish_code LIKE ?";
            $params[] = "%{$filters['parish_code']}%";
        }
        if (!empty($filters['gcode'])) {
            $where .= " AND vic.ORG_CD = ?";
            $params[] = $filters['gcode'];
        }
        if (!empty($filters['jcode'])) {
            $where .= " AND dist.ORG_CD = ?";
            $params[] = $filters['jcode'];
        }

        $sql = "SELECT 
                    p.id, p.parish_code, p.org_cd, p.phone,
                    o.ORG_NM as parish_name, o.ORG_CD as org_code,
                    dist.ORG_NM as district_name, dist.ORG_CD as district_code,
                    vic.ORG_NM as diocese_name, vic.ORG_CD as diocese_code
                FROM ORG_INFO o
                LEFT JOIN parishes p ON p.org_cd = o.ORG_CD
                LEFT JOIN ORG_INFO dist ON o.UPPR_ORG_CD = dist.ORG_CD
                LEFT JOIN ORG_INFO vic ON dist.UPPR_ORG_CD = vic.ORG_CD
                {$where}
                ORDER BY vic.ORG_CD ASC, dist.ORG_CD ASC, o.ORG_NM ASC
                LIMIT {$pageSize} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    public function getParishCount(array $filters = []): int
    {
        $where = "WHERE o.ORG_CD LIKE '1311%'";
        $params = [];

        if (!empty($filters['name'])) {
            $where .= " AND o.ORG_NM LIKE ?";
            $params[] = "%{$filters['name']}%";
        }
        if (!empty($filters['parish_code'])) {
            $where .= " AND p.parish_code LIKE ?";
            $params[] = "%{$filters['parish_code']}%";
        }
        if (!empty($filters['gcode'])) {
            $where .= " AND vic.ORG_CD = ?";
            $params[] = $filters['gcode'];
        }
        if (!empty($filters['jcode'])) {
            $where .= " AND dist.ORG_CD = ?";
            $params[] = $filters['jcode'];
        }

        $sql = "SELECT COUNT(*) as count
                FROM ORG_INFO o
                LEFT JOIN parishes p ON p.org_cd = o.ORG_CD
                LEFT JOIN ORG_INFO dist ON o.UPPR_ORG_CD = dist.ORG_CD
                LEFT JOIN ORG_INFO vic ON dist.UPPR_ORG_CD = vic.ORG_CD
                {$where}";

        $row = $this->db->fetch($sql, $params);
        return (int)($row['count'] ?? 0);
    }

    /**
     * Get a single parish by parishes.id (used for edit page)
     */
    public function getParish(int $id): ?array
    {
        $sql = "SELECT 
                    p.id, p.parish_code, p.org_cd, p.phone,
                    o.ORG_NM as parish_name, o.ORG_CD as org_code,
                    dist.ORG_NM as district_name, dist.ORG_CD as district_code,
                    vic.ORG_NM as diocese_name, vic.ORG_CD as diocese_code
                FROM parishes p
                JOIN ORG_INFO o ON p.org_cd = o.ORG_CD
                LEFT JOIN ORG_INFO dist ON o.UPPR_ORG_CD = dist.ORG_CD
                LEFT JOIN ORG_INFO vic ON dist.UPPR_ORG_CD = vic.ORG_CD
                WHERE p.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get all vicariates (대리구) from ORG_INFO.
     * Returns id=ORG_CD (string), GYOGU=name, GCODE=ORG_CD
     */
    public function getDioceses(): array
    {
        // Use ORG_CD as ID for consistency with ORG_INFO focus
        $sql = "SELECT ORG_CD as id, ORG_NM as GYOGU, ORG_CD as GCODE 
                FROM ORG_INFO 
                WHERE ORG_CD LIKE '1306%' 
                ORDER BY ORG_CD ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get all districts (지구) from ORG_INFO, optionally filtered by vicariate ORG_CD.
     * Returns id=ORG_CD, JIGU=name, JCODE=ORG_CD, vicariate_id=UPPR_ORG_CD
     */
    public function getDistricts(?int $vicariateId = null): array
    {
        $where = $vicariateId ? "AND dist.UPPR_ORG_CD = ?" : "";
        $params = $vicariateId ? [$vicariateId] : [];

        $sql = "SELECT 
                    dist.ORG_CD as id, 
                    dist.ORG_NM as JIGU, 
                    dist.ORG_CD as JCODE, 
                    dist.UPPR_ORG_CD as vicariate_id,
                    dist.USE_YN,
                    vic.ORG_NM as GYOGU
                FROM ORG_INFO dist
                LEFT JOIN ORG_INFO vic ON dist.UPPR_ORG_CD = vic.ORG_CD
                WHERE dist.ORG_CD LIKE '1309%' AND dist.USE_YN = 'Y' {$where}
                ORDER BY dist.ORG_CD ASC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Search parishes with hierarchy filters (uses ORG_INFO directly).
     * vicariate_id and district_id are ORG_CD values (e.g., 13061001)
     */
    public function searchParishes(array $filters): array
    {
        $where = "WHERE o.ORG_CD LIKE '1311%'";
        $params = [];

        if (!empty($filters['vicariate_id'])) {
            $where .= " AND vic.ORG_CD = ?";
            $params[] = $filters['vicariate_id'];
        }
        if (!empty($filters['district_id'])) {
            $where .= " AND o.UPPR_ORG_CD = ?";
            $params[] = $filters['district_id'];
        }
        if (!empty($filters['keyword'])) {
            $where .= " AND o.ORG_NM LIKE ?";
            $params[] = "%{$filters['keyword']}%";
        }

        $sql = "SELECT p.id, o.ORG_NM as parish_name, vic.ORG_NM as diocese_name, dist.ORG_NM as district_name
                FROM ORG_INFO o
                LEFT JOIN ORG_INFO dist ON o.UPPR_ORG_CD = dist.ORG_CD
                LEFT JOIN ORG_INFO vic ON dist.UPPR_ORG_CD = vic.ORG_CD
                LEFT JOIN parishes p ON p.org_cd = o.ORG_CD
                {$where}
                ORDER BY o.ORG_NM ASC
                LIMIT 50";

        return $this->db->fetchAll($sql, $params);
    }

    // -------------------------------------------------------
    // Write operations: modify ORG_INFO + sync parishes table
    // -------------------------------------------------------

    /**
     * Create new parish in ORG_INFO and parishes table.
     * $data['jcode'] = UPPR_ORG_CD (district ORG_CD, e.g. 13090001)
     * $data['bondang'] = parish name
     * $data['bcode'] = legacy parish code
     */
    public function createParish(array $data): bool
    {
        try {
            $upprOrgCd = (int)($data['jcode'] ?? 0);

            // Generate next ORG_CD for parish (1311xxxx)
            $maxRow = $this->db->fetch("SELECT MAX(ORG_CD) as max_cd FROM ORG_INFO WHERE ORG_CD LIKE '1311%'");
            $nextOrgCd = ($maxRow['max_cd'] ?? 13110000) + 1;

            // Insert into ORG_INFO
            $this->db->query("
                INSERT INTO ORG_INFO (ORG_CD, ORG_NM, UPPR_ORG_CD, ORG_TYPE, ORG_IN_TEL, REG_DT)
                VALUES (?, ?, ?, 1, ?, NOW())
            ", [$nextOrgCd, trim($data['bondang'] ?? ''), $upprOrgCd, $data['phone'] ?? '']);

            // Sync to parishes table
            $dist = $this->db->fetch("
                SELECT d.id, d.name as district_name, d.code as district_code,
                       v.name as diocese_name, v.code as diocese_code
                FROM districts d JOIN vicariates v ON d.vicariate_id = v.id
                WHERE d.code = ?
            ", [(string)$upprOrgCd]);

            $this->db->query("
                INSERT INTO parishes (org_cd, district_id, parish_name, parish_code, diocese_name, diocese_code, district_name, district_code, phone)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $nextOrgCd,
                $dist['id'] ?? null,
                trim($data['bondang'] ?? ''),
                $data['bcode'] ?? null,
                $dist['diocese_name'] ?? '',
                $dist['diocese_code'] ?? '',
                $dist['district_name'] ?? '',
                $dist['district_code'] ?? '',
                $data['phone'] ?? ''
            ]);

            return true;
        } catch (\Exception $e) {
            error_log("createParish error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update parish in ORG_INFO and parishes table.
     * $id = parishes.id
     */
    public function updateParish(int $id, array $data): bool
    {
        try {
            $parish = $this->db->fetch("SELECT org_cd FROM parishes WHERE id = ?", [$id]);
            if (!$parish || !$parish['org_cd']) return false;

            $orgCd     = (int)$parish['org_cd'];
            $upprOrgCd = (int)($data['jcode'] ?? 0);

            // Update ORG_INFO
            $this->db->query("
                UPDATE ORG_INFO SET ORG_NM = ?, UPPR_ORG_CD = ?, ORG_IN_TEL = ?, REFRESH_DT = NOW()
                WHERE ORG_CD = ?
            ", [trim($data['bondang'] ?? ''), $upprOrgCd, $data['phone'] ?? '', $orgCd]);

            // Sync to parishes table
            $dist = $this->db->fetch("
                SELECT d.id, d.name as district_name, d.code as district_code,
                       v.name as diocese_name, v.code as diocese_code
                FROM districts d JOIN vicariates v ON d.vicariate_id = v.id
                WHERE d.code = ?
            ", [(string)$upprOrgCd]);

            $this->db->query("
                UPDATE parishes SET 
                    parish_name = ?, parish_code = ?, district_id = ?,
                    diocese_name = ?, diocese_code = ?, district_name = ?, district_code = ?, phone = ?
                WHERE id = ?
            ", [
                trim($data['bondang'] ?? ''),
                $data['bcode'] ?? null,
                $dist['id'] ?? null,
                $dist['diocese_name'] ?? '',
                $dist['diocese_code'] ?? '',
                $dist['district_name'] ?? '',
                $dist['district_code'] ?? '',
                $data['phone'] ?? '',
                $id
            ]);

            return true;
        } catch (\Exception $e) {
            error_log("updateParish error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete parish from ORG_INFO and parishes table.
     */
    public function deleteParish(int $id): bool
    {
        try {
            $parish = $this->db->fetch("SELECT org_cd FROM parishes WHERE id = ?", [$id]);
            if ($parish && $parish['org_cd']) {
                $this->db->query("DELETE FROM ORG_INFO WHERE ORG_CD = ?", [(int)$parish['org_cd']]);
            }
            $this->db->query("DELETE FROM parishes WHERE id = ?", [$id]);
            return true;
        } catch (\Exception $e) {
            error_log("deleteParish error: " . $e->getMessage());
            return false;
        }
    }

    // --- Vicariate Management (writes to ORG_INFO + vicariates) ---

    public function createVicariate(array $data): bool
    {
        $maxRow  = $this->db->fetch("SELECT MAX(ORG_CD) as max_cd FROM ORG_INFO WHERE ORG_CD LIKE '1306%'");
        $nextCd  = ($maxRow['max_cd'] ?? 13060000) + 1;
        $this->db->query("INSERT INTO ORG_INFO (ORG_CD, ORG_NM, UPPR_ORG_CD, ORG_TYPE, REG_DT) VALUES (?, ?, 0, 6, NOW())", [$nextCd, $data['name']]);
        return (bool)$this->db->query("INSERT INTO vicariates (name, code) VALUES (?, ?)", [$data['name'], (string)$nextCd]);
    }

    public function updateVicariate(int $orgCd, array $data): bool
    {
        $this->db->query("UPDATE ORG_INFO SET ORG_NM = ?, REFRESH_DT = NOW() WHERE ORG_CD = ?", [$data['name'], $orgCd]);
        return (bool)$this->db->query("UPDATE vicariates SET name = ? WHERE code = ?", [$data['name'], (string)$orgCd]);
    }

    public function deleteVicariate(int $orgCd): bool
    {
        $this->db->query("DELETE FROM ORG_INFO WHERE ORG_CD = ?", [$orgCd]);
        return (bool)$this->db->query("DELETE FROM vicariates WHERE code = ?", [(string)$orgCd]);
    }

    // --- District Management (writes to ORG_INFO + districts) ---

    public function createDistrict(array $data): bool
    {
        $vic    = $this->db->fetch("SELECT code FROM vicariates WHERE id = ?", [(int)$data['vicariate_id']]);
        $upprCd = $vic ? (int)$vic['code'] : 0;
        $maxRow = $this->db->fetch("SELECT MAX(ORG_CD) as max_cd FROM ORG_INFO WHERE ORG_CD LIKE '1309%'");
        $nextCd = ($maxRow['max_cd'] ?? 13090000) + 1;
        $this->db->query("INSERT INTO ORG_INFO (ORG_CD, ORG_NM, UPPR_ORG_CD, ORG_TYPE, USE_YN, REG_DT) VALUES (?, ?, ?, 9, 'Y', NOW())", [$nextCd, $data['name'], $upprCd]);
        return (bool)$this->db->query("INSERT INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)", [$data['vicariate_id'], $data['name'], (string)$nextCd]);
    }

    public function updateDistrict(int $orgCd, array $data): bool
    {
        // vicariate_id in $data is the ORG_CD of the vicariate
        $useYn = $data['use_yn'] ?? 'Y';
        $this->db->query("UPDATE ORG_INFO SET ORG_NM = ?, UPPR_ORG_CD = ?, USE_YN = ?, REFRESH_DT = NOW() WHERE ORG_CD = ?", [$data['name'], (int)$data['vicariate_id'], $useYn, $orgCd]);
        return (bool)$this->db->query("UPDATE districts SET vicariate_id = (SELECT id FROM vicariates WHERE code = ?), name = ? WHERE code = ?", [(string)$data['vicariate_id'], $data['name'], (string)$orgCd]);
    }

    public function deleteDistrict(int $orgCd): bool
    {
        $this->db->query("DELETE FROM ORG_INFO WHERE ORG_CD = ?", [$orgCd]);
        return (bool)$this->db->query("DELETE FROM districts WHERE code = ?", [(string)$orgCd]);
    }
}
