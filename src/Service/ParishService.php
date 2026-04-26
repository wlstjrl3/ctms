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
     * Get list of parishes with optional filters
     */
    public function getParishList(array $filters = [], int $page = 1, int $pageSize = 15): array
    {
        $offset = ($page - 1) * $pageSize;
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['name'])) {
            $where .= " AND parish_name LIKE ?";
            $params[] = "%{$filters['name']}%";
        }
        if (!empty($filters['parish_code'])) {
            $where .= " AND parish_code LIKE ?";
            $params[] = "%{$filters['parish_code']}%";
        }
        if (!empty($filters['gcode'])) {
            $where .= " AND diocese_code = ?";
            $params[] = $filters['gcode'];
        }
        if (!empty($filters['jcode'])) {
            $where .= " AND district_code = ?";
            $params[] = $filters['jcode'];
        }

        $sql = "SELECT * FROM parishes 
                {$where} 
                ORDER BY diocese_code ASC, district_code ASC, parish_code ASC 
                LIMIT {$pageSize} OFFSET {$offset}";
        return $this->db->fetchAll($sql, $params);
    }

    public function getParishCount(array $filters = []): int
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['name'])) {
            $where .= " AND p.parish_name LIKE ?";
            $params[] = "%{$filters['name']}%";
        }
        if (!empty($filters['parish_code'])) {
            $where .= " AND p.parish_code LIKE ?";
            $params[] = "%{$filters['parish_code']}%";
        }
        if (!empty($filters['gcode'])) {
            $where .= " AND v.code = ?";
            $params[] = $filters['gcode'];
        }
        if (!empty($filters['jcode'])) {
            $where .= " AND d.code = ?";
            $params[] = $filters['jcode'];
        }

        $sql = "SELECT COUNT(*) as count 
                FROM parishes p
                LEFT JOIN districts d ON p.district_id = d.id
                LEFT JOIN vicariates v ON d.vicariate_id = v.id
                {$where}";
        $row = $this->db->fetch($sql, $params);
        return (int)($row['count'] ?? 0);
    }

    /**
     * Get a single parish record
     */
    public function getParish(int $id): ?array
    {
        $sql = "SELECT p.*, v.name as diocese_name, v.code as diocese_code, d.name as district_name, d.code as district_code 
                FROM parishes p
                LEFT JOIN districts d ON p.district_id = d.id
                LEFT JOIN vicariates v ON d.vicariate_id = v.id
                WHERE p.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get all unique vicariates (대리구)
     */
    public function getDioceses(): array
    {
        $sql = "SELECT id, name as GYOGU, code as GCODE FROM vicariates ORDER BY code ASC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Get all unique districts (지구) for a vicariate
     */
    public function getDistricts(int $vicariateId = null): array
    {
        $where = $vicariateId ? "WHERE vicariate_id = ?" : "";
        $params = $vicariateId ? [$vicariateId] : [];
        
        $sql = "SELECT d.id, d.name as JIGU, d.code as JCODE, v.code as GCODE, d.vicariate_id 
                FROM districts d
                JOIN vicariates v ON d.vicariate_id = v.id
                {$where} 
                ORDER BY d.code ASC";
        return $this->db->fetchAll($sql, $params);
    }

    // --- Vicariate Management ---
    public function createVicariate(array $data): bool {
        return (bool)$this->db->query("INSERT INTO vicariates (name, code) VALUES (?, ?)", [$data['name'], $data['code']]);
    }
    public function updateVicariate(int $id, array $data): bool {
        return (bool)$this->db->query("UPDATE vicariates SET name = ?, code = ? WHERE id = ?", [$data['name'], $data['code'], $id]);
    }
    public function deleteVicariate(int $id): bool {
        return (bool)$this->db->query("DELETE FROM vicariates WHERE id = ?", [$id]);
    }

    // --- District Management ---
    public function createDistrict(array $data): bool {
        return (bool)$this->db->query("INSERT INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)", [$data['vicariate_id'], $data['name'], $data['code']]);
    }
    public function updateDistrict(int $id, array $data): bool {
        return (bool)$this->db->query("UPDATE districts SET vicariate_id = ?, name = ?, code = ? WHERE id = ?", [$data['vicariate_id'], $data['name'], $data['code'], $id]);
    }
    public function deleteDistrict(int $id): bool {
        return (bool)$this->db->query("DELETE FROM districts WHERE id = ?", [$id]);
    }

    /**
     * Create new parish
     */
    public function createParish(array $data): bool
    {
        // Find district_id by codes if not provided
        $districtId = $data['district_id'] ?? null;
        if (!$districtId && !empty($data['jcode'])) {
            $dist = $this->db->fetch("SELECT id FROM districts WHERE code = ?", [$data['jcode']]);
            $districtId = $dist['id'] ?? null;
        }

        $sql = "INSERT INTO parishes (district_id, parish_name, parish_code, pastor_name, address_basic, phone) 
                VALUES (?, ?, ?, ?, ?, ?)";
        return (bool)$this->db->query($sql, [
            $districtId, $data['bondang'], $data['bcode'],
            $data['pastor'] ?? null, $data['address'] ?? null, $data['phone'] ?? null
        ]);
    }

    /**
     * Update parish
     */
    public function updateParish(int $id, array $data): bool
    {
        $districtId = $data['district_id'] ?? null;
        if (!$districtId && !empty($data['jcode'])) {
            $dist = $this->db->fetch("SELECT id FROM districts WHERE code = ?", [$data['jcode']]);
            $districtId = $dist['id'] ?? null;
        }

        $sql = "UPDATE parishes 
                SET district_id = ?, parish_name = ?, parish_code = ?,
                    pastor_name = ?, address_basic = ?, phone = ?
                WHERE id = ?";
        return (bool)$this->db->query($sql, [
            $districtId, $data['bondang'], $data['bcode'],
            $data['pastor'] ?? null, $data['address'] ?? null, $data['phone'] ?? null,
            $id
        ]);
    }

    /**
     * Delete parish
     */
    public function deleteParish(int $id): bool
    {
        $sql = "DELETE FROM parishes WHERE id = ?";
        return (bool)$this->db->query($sql, [$id]);
    }
}
