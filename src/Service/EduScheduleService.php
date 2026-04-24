<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

class EduScheduleService
{
    public function __construct(private Database $db) {}

    /**
     * Get list of education schedules with optional filters
     */
    public function getSchedules(int $year = 0, string $state = 'all'): array
    {
        if ($year === 0) $year = (int)date('Y');
        
        $where = "WHERE edu_year = ?";
        $params = [$year];
        
        if ($state !== 'all') {
            $where .= " AND edu_state = ?";
            $params[] = $state;
        }

        $sql = "SELECT * FROM edu_schedule_new {$where} ORDER BY edu_date DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get a single schedule detail by its primary key
     */
    public function getSchedule(int $idx): ?array
    {
        $sql = "SELECT * FROM edu_schedule_new WHERE idx_num = ?";
        return $this->db->fetch($sql, [$idx]);
    }

    /**
     * Get distinct years available in schedules
     */
    public function getAvailableYears(): array
    {
        $sql = "SELECT DISTINCT edu_year FROM edu_schedule_new ORDER BY edu_year DESC";
        return $this->db->fetchAll($sql);
    }
}
