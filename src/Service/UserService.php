<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\App;

class UserService
{
    private $db;

    public function __construct()
    {
        $this->db = App::getInstance()->db();
    }

    /**
     * Get all parish user accounts with their roles
     */
    public function getUserList(array $filters = [], int $page = 1, int $pageSize = 15): array
    {
        $offset = ($page - 1) * $pageSize;
        $whereSql = "WHERE 1=1";
        $params = [];

        if (!empty($filters['name'])) {
            $whereSql .= " AND (u.name LIKE ? OR p.parish_name LIKE ?)";
            $params[] = "%{$filters['name']}%";
            $params[] = "%{$filters['name']}%";
        }
        if (!empty($filters['login_id'])) {
            $whereSql .= " AND u.login_id LIKE ?";
            $params[] = "%{$filters['login_id']}%";
        }
        if (!empty($filters['org_cd'])) {
            $whereSql .= " AND p.org_cd = ?";
            $params[] = (int)$filters['org_cd'];
        }
        if (!empty($filters['role'])) {
            $whereSql .= " AND u.role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['search']) && !empty($filters['category'])) {
            if ($filters['category'] === 'name') {
                $whereSql .= " AND u.name LIKE ?";
                $params[] = "%{$filters['search']}%";
            } elseif ($filters['category'] === 'login_id') {
                $whereSql .= " AND u.login_id LIKE ?";
                $params[] = "%{$filters['search']}%";
            } elseif ($filters['category'] === 'parish') {
                $whereSql .= " AND p.parish_name LIKE ?";
                $params[] = "%{$filters['search']}%";
            }
        }

        $sql = "SELECT u.*, p.parish_name, p.org_cd
                FROM users u
                LEFT JOIN parishes p ON u.parish_id = p.id
                {$whereSql}
                ORDER BY u.role DESC, u.name ASC
                LIMIT {$pageSize} OFFSET {$offset}";
        return $this->db->fetchAll($sql, $params);
    }

    public function getUserCount(array $filters = []): int
    {
        $whereSql = "WHERE 1=1";
        $params = [];

        if (!empty($filters['name'])) {
            $whereSql .= " AND (u.name LIKE ? OR p.parish_name LIKE ?)";
            $params[] = "%{$filters['name']}%";
            $params[] = "%{$filters['name']}%";
        }
        if (!empty($filters['login_id'])) {
            $whereSql .= " AND u.login_id LIKE ?";
            $params[] = "%{$filters['login_id']}%";
        }
        if (!empty($filters['org_cd'])) {
            $whereSql .= " AND p.org_cd = ?";
            $params[] = (int)$filters['org_cd'];
        }
        if (!empty($filters['role'])) {
            $whereSql .= " AND u.role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['search']) && !empty($filters['category'])) {
            if ($filters['category'] === 'name') {
                $whereSql .= " AND u.name LIKE ?";
                $params[] = "%{$filters['search']}%";
            } elseif ($filters['category'] === 'login_id') {
                $whereSql .= " AND u.login_id LIKE ?";
                $params[] = "%{$filters['search']}%";
            } elseif ($filters['category'] === 'parish') {
                $whereSql .= " AND p.parish_name LIKE ?";
                $params[] = "%{$filters['search']}%";
            }
        }
        $sql = "SELECT COUNT(*) as total FROM users u LEFT JOIN parishes p ON u.parish_id = p.id {$whereSql}";
        $row = $this->db->fetch($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Get a single user account with role
     */
    public function getUser(int $id): ?array
    {
        $sql = "SELECT u.*, p.parish_name 
                FROM users u
                LEFT JOIN parishes p ON u.parish_id = p.id
                WHERE u.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
    * Update user account
    */
    public function updateUser(int $id, array $data): bool
    {
        $parish = $this->db->fetch("SELECT id FROM parishes WHERE org_cd = ?", [(int)($data['org_cd'] ?? 0)]);
        $parishId = $parish['id'] ?? null;

        $fields = ["login_id = ?", "name = ?", "parish_id = ?", "role = ?", "phone = ?", "fax = ?"];
        $params = [$data['login_id'], $data['name'], $parishId, $data['role'], $data['phone'] ?? null, $data['fax'] ?? null];

        if (!empty($data['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return (bool)$this->db->query($sql, $params);
    }

    /**
    * Create new user account
    */
    public function createUser(array $data): bool
    {
        $parish = $this->db->fetch("SELECT id FROM parishes WHERE org_cd = ?", [(int)($data['org_cd'] ?? 0)]);
        $parishId = $parish['id'] ?? null;

        $sql = "INSERT INTO users 
                (login_id, name, parish_id, role, password_hash, phone, fax, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        return (bool)$this->db->query($sql, [
            $data['login_id'], 
            $data['name'], 
            $parishId, 
            $data['role'], 
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['phone'] ?? null,
            $data['fax'] ?? null
        ]);
    }

    /**
     * Delete user account
     */
    public function deleteUser(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = ?";
        return (bool)$this->db->query($sql, [$id]);
    }
}
