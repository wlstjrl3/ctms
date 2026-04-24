<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\Database;

class StatisticsService
{
    public function __construct(private Database $db) {}

    /**
     * Get teacher distribution by Academy (Department)
     */
    public function getTeacherStatsByAcademy(string $bcode = ''): array
    {
        $where = "WHERE 1=1";
        $params = [];
        if ($bcode) {
            $where .= " AND bcode = ?";
            $params[] = $bcode;
        }

        $sql = "SELECT academy, COUNT(*) as count 
                FROM bd_member_right 
                {$where} 
                GROUP BY academy";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get teacher distribution by Position (type_num)
     */
    public function getTeacherStatsByPosition(string $bcode = ''): array
    {
        $where = "WHERE 1=1";
        $params = [];
        if ($bcode) {
            $where .= " AND bcode = ?";
            $params[] = $bcode;
        }

        $sql = "SELECT type_num, COUNT(*) as count 
                FROM bd_member_right 
                {$where} 
                GROUP BY type_num";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get Mass time statistics (legacy css_mng_info)
     */
    public function getMassTimeStats(string $academy = '1'): array
    {
        // academy 1: Kids, 2: Youth
        $sql = "SELECT mng_day, mng_hour, COUNT(*) as count 
                FROM css_mng_info 
                WHERE academy = ? AND mng_gubun = '1' AND mng_yn = '1'
                GROUP BY mng_day, mng_hour 
                ORDER BY mng_day, mng_hour";
        return $this->db->fetchAll($sql, [$academy]);
    }

    /**
     * Get Hymnal book statistics (legacy css_info_es)
     */
    public function getHymnalStats(): array
    {
        $sql = "SELECT sbook_source, COUNT(*) as count 
                FROM css_info_es 
                WHERE sbook_source IS NOT NULL
                GROUP BY sbook_source";
        return $this->db->fetchAll($sql);
    }
}
