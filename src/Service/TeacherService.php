<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\App;

class TeacherService
{
    private $db;

    public function __construct()
    {
        $this->db = App::getInstance()->db();
    }

    public function updateTeacher(string $loginId, array $data): bool
    {
        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();

        try {
            // 1. Update bd_member_right (Basic Info, Furlough)
            $sqlRight = "UPDATE bd_member_right 
                         SET name = ?, bname = ?, jumin_f = ?, bday = ?, phone1 = ?, phone2 = ?, 
                             academy = ?, type_num = ?, 
                             reason1 = ?, rsdt1 = ?, rsdt2 = ?, 
                             reason2 = ?, rsdt3 = ?, rsdt4 = ?,
                             reason3 = ?, rsdt5 = ?, rsdt6 = ?
                         WHERE login_id = ?";
            
            $this->db->query($sqlRight, [
                $data['name'], $data['bname'], $data['jumin_f'], $data['bday'], 
                $data['phone1'], $data['phone2'],
                $data['academy'], $data['type_num'],
                $data['reason1'], $data['rsdt1'], $data['rsdt2'],
                $data['reason2'], $data['rsdt3'], $data['rsdt4'],
                $data['reason3'], $data['rsdt5'], $data['rsdt6'],
                $loginId
            ]);

            // 2. Update/Insert bd_member_csdate (Tenure)
            $checkCs = $this->db->fetch("SELECT login_id FROM bd_member_csdate WHERE login_id = ?", [$loginId]);
            if ($checkCs) {
                $this->db->query("UPDATE bd_member_csdate SET cs_year = ?, cs_month = ? WHERE login_id = ?", 
                    [$data['cs_year'], $data['cs_month'], $loginId]);
            } else {
                $this->db->query("INSERT INTO bd_member_csdate (login_id, cs_year, cs_month) VALUES (?, ?, ?)", 
                    [$loginId, $data['cs_year'], $data['cs_month']]);
            }

            // 3. Update/Insert MPLUS_MEMBER_LIST (Address, Email, Photo)
            $checkMplus = $this->db->fetch("SELECT strLoginID FROM MPLUS_MEMBER_LIST WHERE strLoginID = ?", [$loginId]);
            if ($checkMplus) {
                $sqlMplus = "UPDATE MPLUS_MEMBER_LIST 
                             SET strLoginName = ?, strNick = ?, strEmail = ?, 
                                 strHomePost = ?, strHomeAddr1 = ?, strHomeAddr2 = ?,
                                 strHomeTel = ?, strMobile = ?
                             WHERE strLoginID = ?";
                $this->db->query($sqlMplus, [
                    $data['name'], $data['bname'], $data['email'],
                    $data['postcode'], $data['addr1'], $data['addr2'],
                    $data['phone1'], $data['phone2'], $loginId
                ]);
            } else {
                // If missing for some reason, insert it (using bcode from data or session)
                $bcode = $data['bcode'] ?? ''; 
                $sqlMplusInsert = "INSERT INTO MPLUS_MEMBER_LIST 
                                   (bcode, strLoginID, strGroup, strLoginName, strNick, strEmail, 
                                    strHomePost, strHomeAddr1, strHomeAddr2, strHomeTel, strMobile, dateRegDate)
                                   VALUES (?, ?, 'G001', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $this->db->query($sqlMplusInsert, [
                    $bcode, $loginId, $data['name'], $data['bname'], $data['email'],
                    $data['postcode'], $data['addr1'], $data['addr2'],
                    $data['phone1'], $data['phone2']
                ]);
            }

            // 4. Update/Insert academy_state (Class assignment, Remarks)
            $checkAc = $this->db->fetch("SELECT login_id FROM academy_state WHERE login_id = ?", [$loginId]);
            if ($checkAc) {
                $this->db->query("UPDATE academy_state SET ac_edpart02 = ?, ac_edsc = ? WHERE login_id = ?", 
                    [$data['ac_edpart02'], $data['ac_edsc'], $loginId]);
            } else {
                $this->db->query("INSERT INTO academy_state (login_id, ac_edpart02, ac_edsc) VALUES (?, ?, ?)", 
                    [$loginId, $data['ac_edpart02'], $data['ac_edsc']]);
            }

            // 5. Update bd_member_education (Subjects 1-10)
            for ($i = 1; $i <= 10; $i++) {
                $title = $data["edu_title_$i"] ?? '';
                $dt = $data["edu_dt_$i"] ?? '';
                
                if (empty($title) && empty($dt)) continue;

                $checkEdu = $this->db->fetch("SELECT login_id FROM bd_member_education WHERE login_id = ? AND edu_code = 'pro1' AND edu_count = ?", 
                    [$loginId, $i]);
                
                if ($checkEdu) {
                    $this->db->query("UPDATE bd_member_education SET edu_title = ?, edu_dt = ? WHERE login_id = ? AND edu_code = 'pro1' AND edu_count = ?", 
                        [$title, $dt, $loginId, $i]);
                } else {
                    $this->db->query("INSERT INTO bd_member_education (login_id, edu_code, edu_count, edu_title, edu_dt) VALUES (?, 'pro1', ?, ?, ?)", 
                        [$loginId, $i, $title, $dt]);
                }
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Update Teacher Comprehensive Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new teacher (Comprehensive Multi-table)
     */
    public function createTeacher(array $data): bool
    {
        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();

        try {
            // Generate login_id: tmp + YmdHis + 3 random digits
            $loginId = 'tmp' . date('YmdHis') . str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
            $bcode = $data['bcode'] ?? '';

            // 1. Insert MPLUS_MEMBER_LIST (Master)
            $sqlMplus = "INSERT INTO MPLUS_MEMBER_LIST 
                         (bcode, strLoginID, strGroup, strLoginName, strNick, strEmail, 
                          strHomePost, strHomeAddr1, strHomeAddr2, strHomeTel, strMobile, dateRegDate)
                         VALUES (?, ?, 'G001', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $this->db->query($sqlMplus, [
                $bcode, $loginId, $data['name'], $data['bname'], $data['email'],
                $data['postcode'], $data['addr1'], $data['addr2'],
                $data['phone1'], $data['phone2']
            ]);

            // 2. Insert bd_member_right
            $sqlRight = "INSERT INTO bd_member_right 
                         (login_id, bcode, name, bname, jumin_f, bday, phone1, phone2, academy, type_num, 
                          reason1, rsdt1, rsdt2, reason2, rsdt3, rsdt4, reason3, rsdt5, rsdt6)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sqlRight, [
                $loginId, $bcode, $data['name'], $data['bname'], $data['jumin_f'], $data['bday'],
                $data['phone1'], $data['phone2'], $data['academy'], $data['type_num'],
                $data['reason1'], $data['rsdt1'], $data['rsdt2'],
                $data['reason2'], $data['rsdt3'], $data['rsdt4'],
                $data['reason3'], $data['rsdt5'], $data['rsdt6']
            ]);

            // 3. Insert bd_member_csdate
            $this->db->query("INSERT INTO bd_member_csdate (login_id, cs_year, cs_month) VALUES (?, ?, ?)", 
                [$loginId, $data['cs_year'], $data['cs_month']]);

            // 4. Insert academy_state
            $this->db->query("INSERT INTO academy_state (login_id, ac_edpart02, ac_edsc) VALUES (?, ?, ?)", 
                [$loginId, $data['ac_edpart02'], $data['ac_edsc']]);

            // 5. Education Details (optional at creation)
            for ($i = 1; $i <= 10; $i++) {
                $title = $data["edu_title_$i"] ?? '';
                $dt = $data["edu_dt_$i"] ?? '';
                if (!empty($title) || !empty($dt)) {
                    $this->db->query("INSERT INTO bd_member_education (login_id, edu_code, edu_count, edu_title, edu_dt) VALUES (?, 'pro1', ?, ?, ?)", 
                        [$loginId, $i, $title, $dt]);
                }
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Create Teacher Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a single teacher's details
     */
    public function getTeacher(string $loginId): ?array
    {
        $sql = "SELECT r.*, 
                       c.cs_year, c.cs_month, c.cs_date as cs_standard_date,
                       m.strEmail, m.strHomePost, m.strHomeAddr1, m.strHomeAddr2, m.strHomeTel, m.strPhotoFile,
                       a.ac_edpart02, a.ac_edsc
                FROM bd_member_right r
                LEFT JOIN bd_member_csdate c ON r.login_id = c.login_id
                LEFT JOIN MPLUS_MEMBER_LIST m ON r.login_id = m.strLoginID
                LEFT JOIN academy_state a ON r.login_id = a.login_id
                WHERE r.login_id = ?";
        return $this->db->fetch($sql, [$loginId]);
    }

    /**
     * Get teacher list for a specific parish
     */
    public function getTeacherList(string $bcode, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;
        $params = [$bcode];
        
        $whereSql = "WHERE 1=1";
        $params = [];
        if (!empty($bcode)) {
            $whereSql .= " AND r.bcode = ?";
            $params[] = $bcode;
        }
        
        if (!empty($filters['search']) && !empty($filters['category'])) {
            $whereSql .= " AND r.{$filters['category']} LIKE ?";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['academy']) && $filters['academy'] !== 'all') {
            if ($filters['academy'] === '125') {
                $whereSql .= " AND r.academy IN ('1', '2', '5')";
            } else {
                $whereSql .= " AND r.academy = ?";
                $params[] = $filters['academy'];
            }
        }

        $pageSize = (int)($pageSize > 0 ? $pageSize : 20);
        $offset = (int)($offset >= 0 ? $offset : 0);

        $sql = "SELECT r.*, c.cs_year, c.cs_month, c.cs_date as cs_standard_date
                FROM bd_member_right r
                LEFT JOIN bd_member_csdate c ON r.login_id = c.login_id
                {$whereSql}
                ORDER BY r.name ASC
                LIMIT {$pageSize} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get total teacher count for pagination
     */
    public function getTeacherCount(string $bcode, array $filters = []): int
    {
        $whereSql = "WHERE 1=1";
        $params = [];
        if (!empty($bcode)) {
            $whereSql .= " AND bcode = ?";
            $params[] = $bcode;
        }

        if (!empty($filters['search']) && !empty($filters['category'])) {
            $whereSql .= " AND {$filters['category']} LIKE ?";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['academy']) && $filters['academy'] !== 'all') {
            if ($filters['academy'] === '125') {
                $whereSql .= " AND academy IN ('1', '2', '5')";
            } else {
                $whereSql .= " AND academy = ?";
                $params[] = $filters['academy'];
            }
        }

        $sql = "SELECT COUNT(*) as total FROM bd_member_right {$whereSql}";
        $result = $this->db->fetch($sql, $params);
        
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get award (tml) history for a single teacher
     */
    public function getAwards(string $loginId): array
    {
        $sql = "SELECT * FROM tch_tml WHERE login_id = ? ORDER BY tml_year ASC";
        return $this->db->fetchAll($sql, [$loginId]);
    }

    /**
     * Get detailed education subject status
     */
    public function getEducationDetails(string $loginId): array
    {
        $sql = "SELECT * FROM bd_member_education WHERE login_id = ? AND edu_code = 'pro1' ORDER BY edu_count ASC";
        return $this->db->fetchAll($sql, [$loginId]);
    }

    /**
     * Get participation history in training/events
     */
    public function getParticipationHistory(string $loginId): array
    {
        // Joining attendance with schedule
        $sql = "SELECT a.*, s.edu_subject, s.edu_date, s.edu_year, s.edu_level 
                FROM att_member_new a
                JOIN edu_schedule_new s ON a.edu_num = s.edu_num
                WHERE a.att_userid = ? 
                ORDER BY s.edu_date DESC";
        return $this->db->fetchAll($sql, [$loginId]);
    }

    /**
     * Get awards for multiple teachers at once
     */
    public function getAwardsBatch(array $loginIds): array
    {
        if (empty($loginIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($loginIds), '?'));
        $sql = "SELECT * FROM tch_tml WHERE login_id IN ({$placeholders}) ORDER BY tml_year ASC";
        
        $results = $this->db->fetchAll($sql, $loginIds);
        
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['login_id']][] = $row;
        }
        
        return $grouped;
    }
}
