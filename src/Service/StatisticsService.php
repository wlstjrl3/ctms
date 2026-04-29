<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

class StatisticsService
{
    public function __construct(private Database $db) {}

    /**
     * Get teacher distribution by Department (Academy)
     * Filters for currently active ('active') teachers only.
     */
    public function getTeacherStatsByAcademy(string $bcode = ''): array
    {
        $where = "WHERE t.status = 'active'";
        $params = [];
        
        $joinParish = "";
        if ($bcode) {
            $joinParish = " JOIN parishes p ON t.parish_id = p.id ";
            $where .= " AND p.org_cd = ?";
            $params[] = (int)$bcode;
        }

        $sql = "SELECT department as academy, COUNT(*) as count 
                FROM teachers t 
                {$joinParish}
                {$where} 
                GROUP BY department";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get teacher distribution by Position
     * Filters for currently active ('active') teachers only.
     */
    public function getTeacherStatsByPosition(string $bcode = ''): array
    {
        $where = "WHERE t.status = 'active'";
        $params = [];

        $joinParish = "";
        if ($bcode) {
            $joinParish = " JOIN parishes p ON t.parish_id = p.id ";
            $where .= " AND p.org_cd = ?";
            $params[] = (int)$bcode;
        }

        $sql = "SELECT position, COUNT(*) as count 
                FROM teachers t 
                {$joinParish}
                {$where} 
                GROUP BY position";
        return $this->db->fetchAll($sql, $params);
    }
}
