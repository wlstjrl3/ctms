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
            // 0. Get existing teacher
            $teacher = $this->db->fetch("SELECT * FROM teachers WHERE login_id = ?", [$loginId]);
            if (!$teacher) throw new \Exception("Teacher not found");

            // 1. Get parish_id by parish_id or org_cd
            $parishId = !empty($data['parish_id']) ? $data['parish_id'] : null;
            if (!$parishId && !empty($data['org_cd'])) {
                $parish = $this->db->fetch("SELECT id FROM parishes WHERE org_cd = ?", [(int)$data['org_cd']]);
                $parishId = $parish['id'] ?? null;
            }

            // 2. Update teachers (Main Profile)
            $sqlTeacher = "UPDATE teachers 
                           SET name = ?, baptismal_name = ?, birth_date = ?, feast_day = ?, mobile_phone = ?, home_phone = ?, 
                               email = ?, post_code = ?, address_basic = ?, address_detail = ?,
                               department = ?, status = ?, current_grade = ?, position = ?, parish_id = ?, photo_path = ?
                           WHERE login_id = ?";
            
            $this->db->query($sqlTeacher, [
                $data['name'], $data['bname'], $data['jumin_f'], $data['bday'],
                $data['phone2'], $data['phone1'],
                $data['email'], $data['postcode'], $data['addr1'], $data['addr2'],
                $this->mapDepartment($data['academy']),
                $data['status'] ?? 'active',
                $data['ac_edsc'] ?? null,
                $data['position'] ?? '',
                $parishId,
                $data['photo_path'] ?? $teacher['photo_path'],
                $loginId
            ]);

            $teacher = $this->db->fetch("SELECT id FROM teachers WHERE login_id = ?", [$loginId]);
            $teacherId = $teacher['id'];

            // 3. Update teacher_tenure
            $this->db->query("REPLACE INTO teacher_tenure (teacher_id, start_year, start_month) VALUES (?, ?, ?)", 
                [$teacherId, $data['cs_year'], $data['cs_month']]);

            // 4. Update Furloughs (Historical)
            $this->db->query("DELETE FROM teacher_furloughs WHERE teacher_id = ?", [$teacherId]);
            if (isset($data['furloughs']) && is_array($data['furloughs'])) {
                foreach ($data['furloughs'] as $f) {
                    if (!empty($f['reason']) || !empty($f['start_date'])) {
                        $this->db->query("INSERT INTO teacher_furloughs (teacher_id, reason, start_date, end_date) VALUES (?, ?, ?, ?)", [
                            $teacherId, $f['reason'], $f['start_date'], $f['end_date']
                        ]);
                    }
                }
            }

            // 5. Update Awards
            $this->updateAwards($loginId, $data['awards'] ?? []);

            // 6. Update Education Records
            $this->db->query("DELETE FROM education_records WHERE teacher_id = ?", [$teacherId]);
            if (isset($data['education']) && is_array($data['education'])) {
                foreach ($data['education'] as $edu) {
                    if (!empty($edu['course_id'])) {
                        $this->db->query("INSERT INTO education_records (teacher_id, course_id, completion_date, status) VALUES (?, ?, ?, 'Completed')", 
                            [$teacherId, $edu['course_id'], $edu['date']]);
                    }
                }
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Update Teacher V2 Error: " . $e->getMessage());
            return false;
        }
    }

    private function mapDepartment($legacyVal): string
    {
        $map = ['1' => 'elementary', '2' => 'middle_high', '5' => 'daegun', '3' => 'disabled', '4' => 'integrated'];
        return $map[$legacyVal] ?? 'elementary';
    }

    public function createTeacher(array $data): bool
    {
        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();

        try {
            $loginId = 'tmp' . date('YmdHis') . str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
            $parishId = !empty($data['parish_id']) ? $data['parish_id'] : null;
            if (!$parishId && !empty($data['org_cd'])) {
                $parish = $this->db->fetch("SELECT id FROM parishes WHERE org_cd = ?", [(int)$data['org_cd']]);
                $parishId = $parish['id'] ?? null;
            }

            $sqlTeacher = "INSERT INTO teachers (
                parish_id, login_id, name, baptismal_name, birth_date, feast_day, mobile_phone, home_phone, email,
                post_code, address_basic, address_detail, department, status, current_grade, position, photo_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)";

            $this->db->query($sqlTeacher, [
                $parishId, $loginId, $data['name'], $data['bname'], $data['jumin_f'], $data['bday'],
                $data['phone2'], $data['phone1'], $data['email'],
                $data['postcode'], $data['addr1'], $data['addr2'],
                $this->mapDepartment($data['academy']), $data['ac_edsc'] ?? null, $data['position'] ?? '',
                $data['photo_path'] ?? null
            ]);

            $teacherId = $pdo->lastInsertId();

            $this->db->query("INSERT INTO teacher_tenure (teacher_id, start_year, start_month) VALUES (?, ?, ?)", 
                [$teacherId, $data['cs_year'], $data['cs_month']]);

            // Optional: Initial furloughs/education if provided in $data
            if (isset($data['furloughs']) && is_array($data['furloughs'])) {
                foreach ($data['furloughs'] as $f) {
                    $this->db->query("INSERT INTO teacher_furloughs (teacher_id, reason, start_date, end_date) VALUES (?, ?, ?, ?)", [
                        $teacherId, $f['reason'], $f['start_date'], $f['end_date']
                    ]);
                }
            }
            if (isset($data['education']) && is_array($data['education'])) {
                foreach ($data['education'] as $edu) {
                    $this->db->query("INSERT INTO education_records (teacher_id, course_id, completion_date, status) VALUES (?, 1, ?, 'Completed')", [
                        $teacherId, $edu['date']
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Create Teacher V2 Error: " . $e->getMessage());
            return false;
        }
    }

    public function getTeacher(string $loginId): ?array
    {
        $sql = "SELECT t.*, tt.start_year as cs_year, tt.start_month as cs_month, p.org_cd
                FROM teachers t
                LEFT JOIN teacher_tenure tt ON t.id = tt.teacher_id
                LEFT JOIN parishes p ON t.parish_id = p.id
                WHERE t.login_id = ?";
        $teacher = $this->db->fetch($sql, [$loginId]);
        if ($teacher) {
            // Map back to legacy field names for views
            $teacher['phone1'] = $teacher['mobile_phone'];
            $teacher['phone2'] = $teacher['home_phone'];
            $teacher['bday'] = $teacher['feast_day'] ? str_replace('-', '/', $teacher['feast_day']) : '';
            $teacher['bname'] = $teacher['baptismal_name'];
            
            // Furloughs (New dynamic structure)
            $teacher['furloughs'] = $this->db->fetchAll("SELECT * FROM teacher_furloughs WHERE teacher_id = ? ORDER BY start_date ASC", [$teacher['id']]);
        }
        return $teacher;
    }

    public function getTeacherList(string $orgCd, array $filters = [], int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;
        $whereSql = "WHERE 1=1";
        $params = [];

        // Status Filter (Default: Hide Retired)
        $status = $filters['status'] ?? '';
        if ($status === 'all') {
            // No filter
        } elseif (!empty($status)) {
            $whereSql .= " AND t.status = ?";
            $params[] = $status;
        } else {
            $whereSql .= " AND t.status = 'active'";
        }
        
        if (!empty($orgCd)) {
            $whereSql .= " AND p.org_cd = ?";
            $params[] = (int)$orgCd;
        }
        
        if (!empty($filters['name'])) {
            $whereSql .= " AND t.name LIKE ?";
            $params[] = "%{$filters['name']}%";
        }
        if (!empty($filters['bname'])) {
            $whereSql .= " AND t.baptismal_name LIKE ?";
            $params[] = "%{$filters['bname']}%";
        }
        $dept = $filters['dept'] ?? $filters['academy'] ?? '';
        if (!empty($dept) && $dept !== 'all') {
            $mappedDept = $this->mapDepartment($dept);
            $whereSql .= " AND t.department = ?";
            $params[] = ($dept === $mappedDept) ? $dept : $mappedDept;
        }
        if (!empty($filters['pos'])) {
            $whereSql .= " AND t.position LIKE ?";
            $params[] = "%{$filters['pos']}%";
        }
        if (!empty($filters['phone'])) {
            $whereSql .= " AND (t.mobile_phone LIKE ? OR t.home_phone LIKE ?)";
            $params[] = "%{$filters['phone']}%";
            $params[] = "%{$filters['phone']}%";
        }
        if (!empty($filters['parish_name'])) {
            $whereSql .= " AND p.parish_name LIKE ?";
            $params[] = "%{$filters['parish_name']}%";
        }
        
        // Age filter (based on birth_date YYYY-MM-DD)
        if (!empty($filters['age_min'])) {
            $yearLimit = date('Y') - (int)$filters['age_min'];
            $whereSql .= " AND t.birth_date <= ?";
            $params[] = "{$yearLimit}-12-31";
        }
        if (!empty($filters['age_max'])) {
            $yearLimit = date('Y') - (int)$filters['age_max'];
            $whereSql .= " AND t.birth_date >= ?";
            $params[] = "{$yearLimit}-01-01";
        }

        // Tenure filter (based on tt.start_year)
        if (!empty($filters['tenure_min'])) {
            $yearLimit = date('Y') - (int)$filters['tenure_min'];
            $whereSql .= " AND tt.start_year <= ?";
            $params[] = $yearLimit;
        }
        if (!empty($filters['tenure_max'])) {
            $yearLimit = date('Y') - (int)$filters['tenure_max'];
            $whereSql .= " AND tt.start_year >= ?";
            $params[] = $yearLimit;
        }

        if (!empty($filters['search']) && !empty($filters['category'])) {
            $field = $filters['category'] === 'name' ? 't.name' : 't.login_id';
            $whereSql .= " AND {$field} LIKE ?";
            $params[] = "%{$filters['search']}%";
        }

        $sql = "SELECT t.*, tt.start_year as cs_year, tt.start_month as cs_month, p.org_cd, p.parish_name
                FROM teachers t
                LEFT JOIN teacher_tenure tt ON t.id = tt.teacher_id
                LEFT JOIN parishes p ON t.parish_id = p.id
                {$whereSql}
                ORDER BY t.name ASC
                LIMIT {$pageSize} OFFSET {$offset}";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getTeacherCount(string $orgCd, array $filters = []): int
    {
        $whereSql = "WHERE 1=1";
        $params = [];
        $joinParishes = false;
        $joinTenure = false;

        // Status Filter (Default: Hide Retired)
        $status = $filters['status'] ?? '';
        if ($status === 'all') {
            // No filter
        } elseif (!empty($status)) {
            $whereSql .= " AND t.status = ?";
            $params[] = $status;
        } else {
            $whereSql .= " AND t.status = 'active'";
        }

        if (!empty($orgCd)) {
            $whereSql .= " AND p.org_cd = ?";
            $params[] = (int)$orgCd;
            $joinParishes = true;
        }

        if (!empty($filters['name'])) {
            $whereSql .= " AND t.name LIKE ?";
            $params[] = "%{$filters['name']}%";
        }

        if (!empty($filters['bname'])) {
            $whereSql .= " AND t.baptismal_name LIKE ?";
            $params[] = "%{$filters['bname']}%";
        }

        $dept = $filters['dept'] ?? $filters['academy'] ?? '';
        if (!empty($dept) && $dept !== 'all') {
            $mappedDept = $this->mapDepartment($dept);
            $whereSql .= " AND t.department = ?";
            $params[] = ($dept === $mappedDept) ? $dept : $mappedDept;
        }

        if (!empty($filters['pos'])) {
            $whereSql .= " AND t.position LIKE ?";
            $params[] = "%{$filters['pos']}%";
        }

        if (!empty($filters['phone'])) {
            $whereSql .= " AND (t.mobile_phone LIKE ? OR t.home_phone LIKE ?)";
            $params[] = "%{$filters['phone']}%";
            $params[] = "%{$filters['phone']}%";
        }

        if (!empty($filters['parish_name'])) {
            $whereSql .= " AND p.parish_name LIKE ?";
            $params[] = "%{$filters['parish_name']}%";
            $joinParishes = true;
        }

        if (!empty($filters['age_min'])) {
            $yearLimit = date('Y') - (int)$filters['age_min'];
            $whereSql .= " AND t.birth_date <= ?";
            $params[] = "{$yearLimit}-12-31";
        }

        if (!empty($filters['age_max'])) {
            $yearLimit = date('Y') - (int)$filters['age_max'];
            $whereSql .= " AND t.birth_date >= ?";
            $params[] = "{$yearLimit}-01-01";
        }

        if (!empty($filters['tenure_min'])) {
            $yearLimit = date('Y') - (int)$filters['tenure_min'];
            $whereSql .= " AND tt.start_year <= ?";
            $params[] = $yearLimit;
            $joinTenure = true;
        }

        if (!empty($filters['tenure_max'])) {
            $yearLimit = date('Y') - (int)$filters['tenure_max'];
            $whereSql .= " AND tt.start_year >= ?";
            $params[] = $yearLimit;
            $joinTenure = true;
        }

        if (!empty($filters['search']) && !empty($filters['category'])) {
            $field = $filters['category'] === 'name' ? 't.name' : 't.login_id';
            $whereSql .= " AND {$field} LIKE ?";
            $params[] = "%{$filters['search']}%";
        }

        $sql = "SELECT COUNT(*) as total FROM teachers t ";
        if ($joinParishes) {
            $sql .= " LEFT JOIN parishes p ON t.parish_id = p.id ";
        }
        if ($joinTenure) {
            $sql .= " LEFT JOIN teacher_tenure tt ON t.id = tt.teacher_id ";
        }
        
        $sql .= $whereSql;
        
        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    public function getAwards(string $loginId): array
    {
        $sql = "SELECT ta.award_year as tml_year, ta.award_type as tml 
                FROM teacher_awards ta
                JOIN teachers t ON ta.teacher_id = t.id
                WHERE t.login_id = ? ORDER BY ta.award_year ASC";
        return $this->db->fetchAll($sql, [$loginId]);
    }

    public function getEducationDetails(string $loginId): array
    {
        $sql = "SELECT ec.course_name as edu_title, er.completion_date as edu_dt, ec.id as course_id
                FROM education_records er
                JOIN teachers t ON er.teacher_id = t.id
                JOIN education_courses ec ON er.course_id = ec.id
                WHERE t.login_id = ? ORDER BY er.completion_date ASC";
        return $this->db->fetchAll($sql, [$loginId]);
    }



    public function getAwardsBatch(array $teacherIds): array
    {
        if (empty($teacherIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
        $sql = "SELECT teacher_id, award_year as tml_year, award_type as tml 
                FROM teacher_awards 
                WHERE teacher_id IN ({$placeholders}) 
                ORDER BY award_year ASC";
        
        $results = $this->db->fetchAll($sql, $teacherIds);
        
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['teacher_id']][] = $row;
        }
        
        return $grouped;
    }

    public function updateAwards(string $loginId, array $awards): void
    {
        $teacher = $this->db->fetch("SELECT id FROM teachers WHERE login_id = ?", [$loginId]);
        if (!$teacher) return;
        $teacherId = $teacher['id'];

        $this->db->query("DELETE FROM teacher_awards WHERE teacher_id = ?", [$teacherId]);
        foreach ($awards as $award) {
            if (empty($award['tml_year']) || empty($award['tml'])) continue;
            $this->db->query("INSERT INTO teacher_awards (teacher_id, award_year, award_type) VALUES (?, ?, ?)", 
                [$teacherId, $award['tml_year'], $award['tml']]);
        }
    }
}
