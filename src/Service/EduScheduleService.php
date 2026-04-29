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
        
        $where = "WHERE s.edu_year = ?";
        $params = [$year];
        
        if ($state !== 'all') {
            $where .= " AND s.edu_state = ?";
            $params[] = $state;
        }

        $sql = "SELECT s.*, ec.course_name as standardized_name 
                FROM edu_schedule_new s
                LEFT JOIN education_courses ec ON s.course_id = ec.id
                {$where} ORDER BY s.edu_date DESC";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get a single schedule detail by its primary key
     */
    public function getSchedule(int $idx): ?array
    {
        $sql = "SELECT s.*, ec.course_name as standardized_name 
                FROM edu_schedule_new s
                LEFT JOIN education_courses ec ON s.course_id = ec.id
                WHERE s.idx_num = ?";
        return $this->db->fetch($sql, [$idx]);
    }

    /**
     * Get distinct years available in schedules
     */
    public function getAvailableYears(): array
    {
        $sql = "SELECT DISTINCT edu_year FROM edu_schedule_new ORDER BY edu_year DESC";
        $years = $this->db->fetchAll($sql);
        
        // If empty, add current year
        if (empty($years)) {
            return [['edu_year' => date('Y')]];
        }
        return $years;
    }

    public function saveSchedule(array $data): bool
    {
        if (!empty($data['idx_num'])) {
            $sql = "UPDATE edu_schedule_new SET 
                    course_id = ?, edu_subject = ?, edu_date = ?, edu_place = ?, 
                    edu_year = ?, edu_level = ?, edu_state = ?, edu_content = ?, 
                    edu_money = ?, edu_maxp = ?
                    WHERE idx_num = ?";
            $params = [
                $data['course_id'], $data['edu_subject'], $data['edu_date'], $data['edu_place'],
                $data['edu_year'], $data['edu_level'], $data['edu_state'], $data['edu_content'],
                $data['edu_money'], $data['edu_maxp'], $data['idx_num']
            ];
            $this->db->query($sql, $params);
        } else {
            $sql = "INSERT INTO edu_schedule_new 
                    (course_id, edu_subject, edu_date, edu_place, edu_year, edu_level, edu_state, edu_content, edu_money, edu_maxp, reg_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [
                $data['course_id'], $data['edu_subject'], $data['edu_date'], $data['edu_place'],
                $data['edu_year'], $data['edu_level'], $data['edu_state'], $data['edu_content'],
                $data['edu_money'], $data['edu_maxp']
            ];
            $this->db->query($sql, $params);
        }
        return true;
    }

    public function deleteSchedule(int $idx): bool
    {
        $this->db->query("DELETE FROM edu_schedule_new WHERE idx_num = ?", [$idx]);
        return true;
    }
}
