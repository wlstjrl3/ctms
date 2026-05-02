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

    public function updateTeacher(int $teacherId, array $data): bool
    {
        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();

        try {
            // 1. Get parish_id by parish_id or org_cd
            $parishId = !empty($data['parish_id']) ? $data['parish_id'] : null;
            if (!$parishId && !empty($data['org_cd'])) {
                $parish = $this->db->fetch("SELECT id FROM parishes WHERE org_cd = ?", [(int)$data['org_cd']]);
                $parishId = $parish['id'] ?? null;
            }

            // 2. Update teachers (Main Profile)
            $sql = "UPDATE teachers SET 
                    name = ?, baptismal_name = ?, birth_date = ?, feast_day = ?, 
                    mobile_phone = ?, email = ?, department = ?, status = ?, 
                    current_grade = ?, position = ?, parish_id = ?, 
                    photo_path = COALESCE(?, photo_path)
                    WHERE id = ?";
            
            $this->db->query($sql, [
                $data['name'], $data['bname'], $data['birth_date'] ?? null, $data['bday'],
                $data['phone1'], $data['email'], $this->mapDepartment($data['academy']), 
                $data['status'] ?? 'active', $data['current_grade'] ?? null, 
                $data['position'] ?? '', $parishId, $data['photo_path'],
                $teacherId
            ]);

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

            // 7. Update Core Education (3 Stages)
            if (isset($data['core_edu']) && is_array($data['core_edu'])) {
                foreach ($data['core_edu'] as $courseName => $edu) {
                    // Find course_id for the core course
                    $course = $this->db->fetch("SELECT id FROM education_courses WHERE course_name = ?", [$courseName]);
                    if ($course) {
                        // Avoid deleting core records if they are already there? 
                        // No, we should update them based on the specific core_edu input.
                        // However, $this->db->query("DELETE FROM education_records WHERE teacher_id = ?", [$teacherId]); 
                        // already deleted ALL education_records above in step 6.
                        // So we just need to re-insert if completed.
                        
                        if (!empty($edu['is_completed'])) {
                            $month = !empty($edu['month']) ? str_pad((string)$edu['month'], 2, '0', STR_PAD_LEFT) : '01';
                            $date = "{$edu['year']}-{$month}-01";
                            $this->db->query("INSERT INTO education_records (teacher_id, course_id, completion_date, status) VALUES (?, ?, ?, 'Completed')", 
                                [$teacherId, $course['id'], $date]);
                        }
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

    public function createTeacher(array $data)
    {
        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();

        try {
            $parishId = !empty($data['parish_id']) ? $data['parish_id'] : null;
            if (!$parishId && !empty($data['org_cd'])) {
                $parish = $this->db->fetch("SELECT id FROM parishes WHERE org_cd = ?", [(int)$data['org_cd']]);
                $parishId = $parish['id'] ?? null;
            }

            $sqlTeacher = "INSERT INTO teachers (
                parish_id, name, baptismal_name, birth_date, feast_day, mobile_phone, email,
                department, status, current_grade, position, photo_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)";

            $this->db->query($sqlTeacher, [
                $parishId, $data['name'], $data['bname'], $data['birth_date'] ?? null, $data['bday'],
                $data['phone1'], $data['email'],
                $this->mapDepartment($data['academy']), $data['current_grade'] ?? null, $data['position'] ?? '',
                $data['photo_path'] ?? null
            ]);

            $teacherId = (int)$pdo->lastInsertId();

            $this->db->query("INSERT INTO teacher_tenure (teacher_id, start_year, start_month) VALUES (?, ?, ?)", 
                [$teacherId, $data['cs_year'], $data['cs_month']]);

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
            return $teacherId;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Create Teacher V2 Error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteTeacher(int $id): bool
    {
        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();
        try {
            $this->db->query("DELETE FROM teacher_tenure WHERE teacher_id = ?", [$id]);
            $this->db->query("DELETE FROM teacher_furloughs WHERE teacher_id = ?", [$id]);
            $this->db->query("DELETE FROM teacher_awards WHERE teacher_id = ?", [$id]);
            $this->db->query("DELETE FROM education_records WHERE teacher_id = ?", [$id]);
            $this->db->query("DELETE FROM teachers WHERE id = ?", [$id]);
            
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Delete Teacher Error: " . $e->getMessage());
            return false;
        }
    }

    public function getTeacher(int $id): ?array
    {
        $sql = "SELECT t.*, tt.start_year as cs_year, tt.start_month as cs_month, p.org_cd
                FROM teachers t
                LEFT JOIN teacher_tenure tt ON t.id = tt.teacher_id
                LEFT JOIN parishes p ON t.parish_id = p.id
                WHERE t.id = ?";
        $teacher = $this->db->fetch($sql, [$id]);
        if ($teacher) {
            $teacher['phone1'] = $teacher['mobile_phone'];
            $teacher['bday'] = $teacher['feast_day'] ? str_replace('-', '/', $teacher['feast_day']) : '';
            $teacher['bname'] = $teacher['baptismal_name'];
            $teacher['furloughs'] = $this->db->fetchAll("SELECT * FROM teacher_furloughs WHERE teacher_id = ? ORDER BY start_date ASC", [$id]);
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
        $pos = $filters['position'] ?? $filters['pos'] ?? '';
        if (!empty($pos)) {
            $whereSql .= " AND t.position = ?";
            $params[] = $pos;
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

        if (!empty($filters['course_id'])) {
            $whereSql .= " AND EXISTS (SELECT 1 FROM education_records er WHERE er.teacher_id = t.id AND er.course_id = ?)";
            $params[] = (int)$filters['course_id'];
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

        if (!empty($filters['search'])) {
            $whereSql .= " AND t.name LIKE ?";
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

        $pos = $filters['position'] ?? $filters['pos'] ?? '';
        if (!empty($pos)) {
            $whereSql .= " AND t.position = ?";
            $params[] = $pos;
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

        if (!empty($filters['course_id'])) {
            $whereSql .= " AND EXISTS (SELECT 1 FROM education_records er WHERE er.teacher_id = t.id AND er.course_id = ?)";
            $params[] = (int)$filters['course_id'];
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

        if (!empty($filters['search'])) {
            $whereSql .= " AND t.name LIKE ?";
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

    public function getAwards(int $id): array
    {
        $sql = "SELECT award_year as tml_year, award_type as tml 
                FROM teacher_awards
                WHERE teacher_id = ? ORDER BY award_year ASC";
        return $this->db->fetchAll($sql, [$id]);
    }

    public function getEducationDetails(int $id): array
    {
        $sql = "SELECT ec.course_name as edu_title, er.completion_date as edu_dt, ec.id as course_id
                FROM education_records er
                JOIN education_courses ec ON er.course_id = ec.id
                WHERE er.teacher_id = ? 
                AND ec.course_name NOT IN ('기본교육(구입문과정)', '구심화과정', '양성교육(구전문화과정)')
                ORDER BY er.completion_date ASC";
        return $this->db->fetchAll($sql, [$id]);
    }

    public function getCoreEducation(int $id): array
    {
        $stages = [
            '기본교육(구입문과정)',
            '구심화과정',
            '양성교육(구전문화과정)'
        ];
        
        $results = [];
        foreach ($stages as $name) {
            $sql = "SELECT er.completion_date, ec.id as course_id
                    FROM education_records er
                    JOIN education_courses ec ON er.course_id = ec.id
                    WHERE er.teacher_id = ? AND ec.course_name = ?";
            $row = $this->db->fetch($sql, [$id, $name]);
            
            if ($row) {
                $date = new \DateTime($row['completion_date']);
                $results[$name] = [
                    'year' => $date->format('Y'),
                    'month' => (int)$date->format('m'),
                    'is_completed' => true,
                    'course_id' => $row['course_id']
                ];
            } else {
                $results[$name] = [
                    'year' => '',
                    'month' => '',
                    'is_completed' => false,
                    'course_id' => null
                ];
            }
        }
        return $results;
    }

    public function getEducationBatch(array $teacherIds): array
    {
        if (empty($teacherIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
        $sql = "SELECT er.teacher_id, ec.course_name 
                FROM education_records er
                JOIN education_courses ec ON er.course_id = ec.id
                WHERE er.teacher_id IN ($placeholders)
                AND ec.course_name IN ('기본교육(구입문과정)', '구심화과정', '양성교육(구전문화과정)')";
        
        $rows = $this->db->fetchAll($sql, $teacherIds);
        
        $results = [];
        foreach ($rows as $row) {
            $results[$row['teacher_id']][] = $row['course_name'];
        }
        return $results;
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

    public function updateAwards(int $id, array $awards): void
    {
        $this->db->query("DELETE FROM teacher_awards WHERE teacher_id = ?", [$id]);
        foreach ($awards as $award) {
            if (empty($award['tml_year']) || empty($award['tml'])) continue;
            $this->db->query("INSERT INTO teacher_awards (teacher_id, award_year, award_type) VALUES (?, ?, ?)", 
                [$id, $award['tml_year'], $award['tml']]);
        }
    }

    /**
     * Map edu_level codes to human-readable names
     */
    public function getGradeName($level): string
    {
        $map = [
            '0' => '통합',
            '1' => '초등부',
            '2' => '중고등부',
            '3' => '장애인부',
            '4' => '기타'
        ];
        return $map[(string)$level] ?? '기타';
    }
}
