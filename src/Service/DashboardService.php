<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\App;

class DashboardService
{
    private $db;

    public function __construct()
    {
        $this->db = App::getInstance()->db();
    }

    /**
     * Get education schedules for the current month
     */
    public function getMonthlySchedules(int $limit = 10): array
    {
        $sql = "SELECT * FROM edu_schedule_new 
                WHERE MONTH(edu_sdate) = MONTH(CURRENT_DATE()) 
                OR MONTH(edu_edate) = MONTH(CURRENT_DATE())
                ORDER BY edu_sdate DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get application (registration) schedules for the current month
     */
    public function getMonthlyApplications(int $limit = 10): array
    {
        $sql = "SELECT * FROM edu_schedule_new 
                WHERE MONTH(edu_to_sdate) = MONTH(CURRENT_DATE()) 
                OR MONTH(edu_to_edate) = MONTH(CURRENT_DATE())
                ORDER BY edu_to_sdate DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get recent login history (Office Admin only)
     */
    public function getRecentLogins(int $limit = 20): array
    {
        // Joining with search_bondang if available for parish names
        $sql = "SELECT l.*, b.BONDANG as bondang_name 
                FROM ctms_person_login_list l
                LEFT JOIN search_bondang b ON l.bcode = b.bcode
                ORDER BY l.login_date DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get current application status for a specific parish (Bondang Admin only)
     */
    public function getParishApplicationStatus(string $bcode): array
    {
        // This replaces legacy v_edu_input_status logic
        $sql = "SELECT e.*, 
                (SELECT COUNT(*) FROM MPLUS_MEMBER_LIST WHERE bcode = ?) as teacher_count
                FROM edu_schedule_new e
                WHERE CURRENT_DATE() BETWEEN edu_to_sdate AND edu_to_edate
                ORDER BY edu_to_sdate ASC";
        return $this->db->fetchAll($sql, [$bcode]);
    }
}
