<?php
/**
 * CTMS Modern Consolidated Migrator
 * 
 * This script handles the one-time migration from legacy tables to the modern v2 schema.
 * It addresses Y2K bugs, field mappings, and user restoration.
 */

class Migrator {
    private $db;

    public function __construct() {
        // Load .env file
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $_ENV[trim($parts[0])] = trim($parts[1]);
                }
            }
        }

        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $name = $_ENV['DB_NAME'] ?? 'CTMS';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $port = $_ENV['DB_PORT'] ?? '3306';

        $dsn = "mysql:host={$host};dbname={$name};port={$port};charset=utf8mb4";
        $this->db = new PDO($dsn, $user, $pass);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function run() {
        echo "Starting Migration...\n";
        
        // 1. Schema Updates
        $this->updateSchema();
        
        // 2. Clear modern tables first to avoid duplicates
        $this->clearTables();
        
        // 3. Data Migration
        $this->migrateParishes();
        $this->migrateUsers();
        $this->migrateTeachers();
        $this->migrateFurloughs();
        $this->migrateAwards();
        $this->consolidateCourses();
        
        echo "Migration Finished Successfully.\n";
    }

    private function updateSchema() {
        echo "- Updating Schema (Roles and Tables)...\n";
        
        // Update User Roles ENUM
        $this->db->query("ALTER TABLE users MODIFY COLUMN role ENUM('office', 'casuwon', 'diocese', 'bondang') DEFAULT 'bondang'");
        $this->db->query("UPDATE users SET role = 'casuwon' WHERE role = 'office'");
        $this->db->query("ALTER TABLE users MODIFY COLUMN role ENUM('casuwon', 'diocese', 'bondang') DEFAULT 'bondang'");

        // Create vicariates table
        $this->db->query("CREATE TABLE IF NOT EXISTS vicariates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Create districts table
        $this->db->query("CREATE TABLE IF NOT EXISTS districts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vicariate_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vicariate_id) REFERENCES vicariates(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Add district_id to parishes if not exists
        $columns = $this->db->query("SHOW COLUMNS FROM parishes LIKE 'district_id'")->fetchAll();
        if (empty($columns)) {
            $this->db->query("ALTER TABLE parishes ADD COLUMN district_id INT AFTER id");
        }
        
        // Add org_cd to parishes (ORG_INFO.ORG_CD reference) if not exists
        $orgCdCol = $this->db->query("SHOW COLUMNS FROM parishes LIKE 'org_cd'")->fetchAll();
        if (empty($orgCdCol)) {
            $this->db->query("ALTER TABLE parishes ADD COLUMN org_cd INT DEFAULT NULL AFTER district_id");
            $this->db->query("ALTER TABLE parishes ADD INDEX idx_org_cd (org_cd)");
        }
        
        // Add USE_YN to ORG_INFO if not exists
        $useYnCol = $this->db->query("SHOW COLUMNS FROM ORG_INFO LIKE 'USE_YN'")->fetchAll();
        if (empty($useYnCol)) {
            $this->db->query("ALTER TABLE ORG_INFO ADD COLUMN USE_YN CHAR(1) DEFAULT 'Y'");
        }

        // Update teachers status enum to match our code ('furlough' instead of 'on_leave')
        $this->db->query("ALTER TABLE teachers MODIFY COLUMN status ENUM('active', 'furlough', 'retired') DEFAULT 'active'");

        // --- Education System Updates ---
        
        // 0. Ensure edu_schedule_new table exists
        $this->db->query("CREATE TABLE IF NOT EXISTS edu_schedule_new (
            idx_num INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT,
            edu_subject VARCHAR(255),
            edu_year VARCHAR(10),
            edu_date DATETIME,
            edu_place VARCHAR(100),
            edu_level VARCHAR(2),
            edu_state VARCHAR(10) DEFAULT '0',
            edu_money VARCHAR(10),
            edu_maxp INT,
            edu_content TEXT,
            reg_date DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // 1. education_courses schema
        $cols = $this->db->query("SHOW COLUMNS FROM education_courses")->fetchAll(PDO::FETCH_ASSOC);
        $hasActive = false;
        $hasCategory = false;
        foreach ($cols as $c) {
            if ($c['Field'] === 'is_active') $hasActive = true;
            if ($c['Field'] === 'category') $hasCategory = true;
        }
        if (!$hasActive) $this->db->query("ALTER TABLE education_courses ADD COLUMN is_active TINYINT DEFAULT 1");
        if (!$hasCategory) $this->db->query("ALTER TABLE education_courses ADD COLUMN category VARCHAR(50) DEFAULT '[미분류]'");

        // 2. edu_schedule_new schema synchronization
        $sCols = $this->db->query("SHOW COLUMNS FROM edu_schedule_new")->fetchAll(PDO::FETCH_ASSOC);
        $hasCourseId = false;
        $hasEduPlace = false;
        $hasEduWhere = false;
        $hasEduState = false;
        foreach ($sCols as $c) {
            if ($c['Field'] === 'course_id') $hasCourseId = true;
            if ($c['Field'] === 'edu_place') $hasEduPlace = true;
            if ($c['Field'] === 'edu_where') $hasEduWhere = true;
            if ($c['Field'] === 'edu_state') $hasEduState = true;
        }

        if (!$hasCourseId) $this->db->query("ALTER TABLE edu_schedule_new ADD COLUMN course_id INT AFTER idx_num");
        if (!$hasEduState) $this->db->query("ALTER TABLE edu_schedule_new ADD COLUMN edu_state VARCHAR(10) DEFAULT '0' AFTER edu_level");
        
        if (!$hasEduPlace && $hasEduWhere) {
            $this->db->query("ALTER TABLE edu_schedule_new CHANGE edu_where edu_place VARCHAR(100)");
        } elseif (!$hasEduPlace) {
            $this->db->query("ALTER TABLE edu_schedule_new ADD COLUMN edu_place VARCHAR(100) AFTER edu_subject");
        }

        $this->db->query("ALTER TABLE edu_schedule_new MODIFY COLUMN edu_date DATETIME");
        $this->db->query("ALTER TABLE edu_schedule_new MODIFY COLUMN edu_subject VARCHAR(255)");
    }

    private function clearTables() {
        echo "- Clearing existing data...\n";
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->query("TRUNCATE TABLE teacher_awards");
        $this->db->query("TRUNCATE TABLE teacher_tenure");
        $this->db->query("TRUNCATE TABLE teacher_furloughs");
        $this->db->query("TRUNCATE TABLE education_records");
        $this->db->query("TRUNCATE TABLE education_courses");
        $this->db->query("TRUNCATE TABLE teachers");
        $this->db->query("TRUNCATE TABLE users");
        $this->db->query("TRUNCATE TABLE parishes");
        
        // Ensure parish_code is UNIQUE to prevent duplications and allow REPLACE INTO
        $this->db->query("ALTER TABLE parishes MODIFY parish_code VARCHAR(10) UNIQUE");
        
        // Ensure course_name is UNIQUE to prevent duplications
        $this->db->query("ALTER TABLE education_courses MODIFY course_name VARCHAR(255) UNIQUE");
        
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    private function migrateParishes() {
        echo "- Migrating from ORG_INFO (표준 조직 테이블)...\n";

        // STEP 1: Reset vicariates and districts
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->query("TRUNCATE TABLE vicariates");
        $this->db->query("TRUNCATE TABLE districts");
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");

        // STEP 2: Populate vicariates from ORG_INFO (ORG_CD prefix 1306)
        echo "  - Populating vicariates from ORG_INFO...\n";
        $vicRows = $this->db->query("
            SELECT ORG_CD, ORG_NM FROM ORG_INFO 
            WHERE ORG_CD LIKE '1306%' ORDER BY ORG_CD
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stmtVic = $this->db->prepare("INSERT INTO vicariates (name, code) VALUES (?, ?)");
        foreach ($vicRows as $row) {
            $stmtVic->execute([trim($row['ORG_NM']), (string)$row['ORG_CD']]);
        }

        // STEP 3: Populate districts from ORG_INFO (ORG_CD prefix 1309)
        echo "  - Populating districts from ORG_INFO...\n";
        $distRows = $this->db->query("
            SELECT ORG_CD, ORG_NM, UPPR_ORG_CD FROM ORG_INFO 
            WHERE ORG_CD LIKE '1309%' ORDER BY ORG_CD
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stmtDist = $this->db->prepare("INSERT INTO districts (vicariate_id, name, code) VALUES (?, ?, ?)");
        foreach ($distRows as $row) {
            $s = $this->db->prepare("SELECT id FROM vicariates WHERE code = ?");
            $s->execute([(string)$row['UPPR_ORG_CD']]);
            $vic = $s->fetch(PDO::FETCH_ASSOC);
            $stmtDist->execute([$vic['id'] ?? null, trim($row['ORG_NM']), (string)$row['ORG_CD']]);
        }

        // STEP 4: Populate parishes from ORG_INFO (ORG_CD prefix 1311)
        echo "  - Populating parishes from ORG_INFO...\n";
        $parishRows = $this->db->query("
            SELECT ORG_CD, ORG_NM, UPPR_ORG_CD, ORG_IN_TEL, EMAIL FROM ORG_INFO 
            WHERE ORG_CD LIKE '1311%' ORDER BY ORG_CD
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stmtParish = $this->db->prepare("
            REPLACE INTO parishes 
            (org_cd, district_id, parish_name, parish_code, diocese_name, diocese_code, district_name, district_code, phone)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($parishRows as $row) {
            $orgCd  = (int)$row['ORG_CD'];
            $upprCd = (string)$row['UPPR_ORG_CD'];  // 지구 ORG_CD

            // Find district and its vicariate
            $sd = $this->db->prepare("
                SELECT d.id, d.name as district_name, d.code as district_code,
                       v.name as diocese_name, v.code as diocese_code
                FROM districts d
                JOIN vicariates v ON d.vicariate_id = v.id
                WHERE d.code = ?
            ");
            $sd->execute([$upprCd]);
            $dist = $sd->fetch(PDO::FETCH_ASSOC);

            // Match bcode from legacy search_bondang by parish name
            $sb_stmt = $this->db->prepare("
                SELECT BCODE FROM search_bondang 
                WHERE TRIM(BONDANG) COLLATE utf8mb4_unicode_ci = TRIM(?) COLLATE utf8mb4_unicode_ci
            ");
            $sb_stmt->execute([trim($row['ORG_NM'])]);
            $sb = $sb_stmt->fetch(PDO::FETCH_ASSOC);

            $stmtParish->execute([
                $orgCd,
                $dist['id'] ?? null,
                trim($row['ORG_NM']),
                $sb['BCODE'] ?? null,
                $dist['diocese_name'] ?? '',
                $dist['diocese_code'] ?? '',
                $dist['district_name'] ?? '',
                $dist['district_code'] ?? '',
                trim((string)($row['ORG_IN_TEL'] ?? ''))
            ]);
        }

        echo "  - Parish migration complete: " . count($parishRows) . " records.\n";
    }

    private function migrateUsers() {
        echo "- Migrating Users (Admin & Parish Accounts)...\n";
        
        // 1. Migrate from legacy table
        $legacy = $this->db->query("SELECT * FROM ctms_user_info")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($legacy as $row) {
            $role = 'bondang';
            $uid = $row['ctms_uid'];
            
            if (in_array($uid, ['jsyang', 'youthet', 'swscout', 'admin1004'])) $role = 'casuwon';
            if (in_array($uid, ['youth-v1', 'youth-v2', 'youthas'])) $role = 'diocese';

            // Find parish_id by matching ctms_ucode with parish_code
            $parishId = null;
            if (!empty($row['ctms_ucode'])) {
                $stmtP = $this->db->prepare("SELECT id FROM parishes WHERE parish_code = ?");
                $stmtP->execute([$row['ctms_ucode']]);
                $p = $stmtP->fetch(PDO::FETCH_ASSOC);
                $parishId = $p['id'] ?? null;
            }

            $stmt = $this->db->prepare("INSERT IGNORE INTO users (login_id, password_hash, name, role, parish_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $uid,
                $row['ctms_upwd'],
                $row['ctms_uname'],
                $role,
                $parishId
            ]);
        }

        // 2. Ensure critical accounts from legacy files are present even if not in ctms_user_info
        $manualUsers = [
            ['admin1004', 'casuwon', '전체 관리자', 'casuwon', null],
            ['youthas', '4..1', '안산대리구 관리자', 'diocese', null]
        ];

        foreach ($manualUsers as $user) {
            $stmt->execute($user);
        }

        // 3. Fix roles for existing users that might have been migrated with wrong roles (if any)
        $this->db->query("UPDATE users SET role = 'casuwon' WHERE login_id IN ('jsyang', 'youthet', 'swscout', 'admin1004')");
        $this->db->query("UPDATE users SET role = 'diocese' WHERE login_id IN ('youth-v1', 'youth-v2', 'youthas')");

        // 4. Link users to parishes by matching login_id with parish_code
        echo "- Linking users to parishes...\n";
        $this->db->query("
            UPDATE users u
            JOIN parishes p ON u.login_id = p.parish_code
            SET u.parish_id = p.id
            WHERE u.role = 'bondang'
        ");
    }

    private function migrateTeachers() {
        echo "- Migrating Teachers (Active + Retired)...\n";
        
        // 1. Load parish mapping
        $parishes = $this->db->query("SELECT id, parish_code FROM parishes")->fetchAll(PDO::FETCH_KEY_PAIR);
        $deptMap = ['1'=>'elementary','2'=>'middle_high','3'=>'disabled','4'=>'integrated','5'=>'daegun'];
        $positions = ['1'=>'교사','2'=>'교감','3'=>'교무','4'=>'총무'];

        $stmt = $this->db->prepare("
            REPLACE INTO teachers 
            (parish_id, login_id, name, baptismal_name, birth_date, feast_day, mobile_phone, home_phone, email, department, position, status, current_grade)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // PHASE 1: Active Teachers from bd_member_right
        echo "  - Phase 1: Migrating Active Teachers...\n";
        $activeLegacy = $this->db->query("
            SELECT r.*, m.bitDelete 
            FROM bd_member_right r
            JOIN MPLUS_MEMBER_LIST m ON r.login_id = m.strLoginID
        ")->fetchAll(PDO::FETCH_ASSOC);
        $today = date('Y-m-d');
        foreach ($activeLegacy as $row) {
            $parishId = null;
            $bcode = trim((string)$row['bcode']);
            foreach($parishes as $id => $code) {
                if($code === $bcode) { $parishId = $id; break; }
            }

            $birthDate = null;
            if ($row['birthday'] && strlen($row['birthday']) === 8) {
                $year = (int)substr($row['birthday'], 0, 4);
                $month = (int)substr($row['birthday'], 4, 2);
                $day = (int)substr($row['birthday'], 6, 2);
                
                if ($year >= 1900 && $year <= 1925) $year += 100;
                if ($year >= 2050) $year -= 100;

                if (checkdate($month, $day, $year)) {
                    $birthDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
                }
            }

            $feastDay = null;
            if ($row['bday'] && strlen($row['bday']) === 4) {
                $feastDay = substr($row['bday'], 0, 2) . '-' . substr($row['bday'], 2, 2);
            }

            // Determine if currently on furlough
            $status = 'active';
            $isFurlough1 = ($row['reason1'] > 0 && substr($row['rsdt1'], 0, 10) <= $today && (substr($row['rsdt2'], 0, 10) >= $today || $row['rsdt2'] == '1900-01-01 00:00:00'));
            $isFurlough2 = ($row['reason2'] > 0 && substr($row['rsdt3'], 0, 10) <= $today && (substr($row['rsdt4'], 0, 10) >= $today || $row['rsdt4'] == '1900-01-01 00:00:00'));
            $isFurlough3 = ($row['reason3'] > 0 && substr($row['rsdt5'], 0, 10) <= $today && (substr($row['rsdt6'], 0, 10) >= $today || $row['rsdt6'] == '1900-01-01 00:00:00'));
            
            if ($isFurlough1 || $isFurlough2 || $isFurlough3) {
                $status = 'furlough';
            }

            if ($row['bitDelete'] == '1') {
                $status = 'retired';
            }

            // Consolidate various legacy 'etc' fields into current_grade (remarks)
            $remarksParts = [];
            foreach (['type_etc', 'type_etc7', 'type_etc30', 'type_etc31', 'type_etc40'] as $etcField) {
                $val = trim((string)($row[$etcField] ?? ''));
                if ($val !== '') $remarksParts[] = $val;
            }
            $finalRemarks = implode(' | ', $remarksParts);

            $stmt->execute([
                $parishId, $row['login_id'], $row['name'], $row['bname'], $birthDate, $feastDay, 
                $row['phone2'], $row['phone1'], null,
                $deptMap[$row['academy']] ?? 'elementary',
                $positions[$row['type_num']] ?? '교사',
                $status,
                $finalRemarks
            ]);
        }

        // PHASE 2: Retired Teachers from MPLUS_MEMBER_LIST (using INSERT IGNORE)
        echo "  - Phase 2: Migrating Retired Teachers...\n";
        $allMembers = $this->db->query("SELECT * FROM MPLUS_MEMBER_LIST WHERE bitDelete = '1'")->fetchAll(PDO::FETCH_ASSOC);
        
        $stmtRetired = $this->db->prepare("
            INSERT IGNORE INTO teachers 
            (parish_id, login_id, name, baptismal_name, birth_date, feast_day, mobile_phone, home_phone, email, department, position, status, current_grade)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($allMembers as $row) {
            $parishId = null;
            foreach($parishes as $id => $code) {
                if($code === $row['bcode']) { $parishId = $id; break; }
            }

            $birthDate = null;
            if ($row['strBirthday'] && strlen($row['strBirthday']) >= 8) {
                $year = (int)substr($row['strBirthday'], 0, 4);
                $month = (int)substr($row['strBirthday'], 4, 2);
                $day = (int)substr($row['strBirthday'], 6, 2);
                if ($year >= 1900 && $year <= 1925) $year += 100;
                if ($year >= 2050) $year -= 100;
                
                if (checkdate($month, $day, $year)) {
                    $birthDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
                }
            }

            $stmtRetired->execute([
                $parishId, $row['strLoginID'], $row['strLoginName'], $row['strNick'] ?? '', $birthDate, null,
                $row['strMobile'], $row['strHomeTel'], $row['strEmail'],
                'elementary', '교사', 'retired', 'Legacy Retired'
            ]);
        }

        $this->migrateTenure();
        $this->migrateEducation();
    }

    private function migrateEducation() {
        echo "- Migrating Education Records...\n";
        $stmt = $this->db->query("SELECT * FROM bd_member_education");
        $eduData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insertCourse = $this->db->prepare("INSERT IGNORE INTO education_courses (course_name, category) VALUES (?, ?)");
        
        $categories = [
            '영성' => '영성 교육', '교리' => '교리/신학', '신학' => '교리/신학',
            '교수' => '교수법/심리', '심리' => '교수법/심리', '레크' => '기능/기술',
            '기능' => '기능/기술', '줌' => '기능/기술', '구글' => '기능/기술',
            '대화' => '리더십/소통', '소통' => '리더십/소통', '리더' => '리더십/소통'
        ];

        // Use a more robust join-based insert with DISTINCT or specific mapping
        $insertRecord = $this->db->prepare("
            INSERT IGNORE INTO education_records (teacher_id, course_id, completion_date, status)
            SELECT t.id, c.id, ?, 'Completed'
            FROM teachers t
            JOIN education_courses c ON c.course_name = ?
            WHERE t.login_id = ?
            LIMIT 1
        ");

        foreach ($eduData as $row) {
            $courseName = trim($row['edu_title']);
            if (empty($courseName)) continue;

            $cat = '[미분류]';
            foreach ($categories as $key => $val) {
                if (mb_strpos($courseName, $key) !== false) {
                    $cat = $val;
                    break;
                }
            }
            
            // 1. Ensure course exists
            $insertCourse->execute([$courseName, $cat]);
            
            // 2. Insert record
            $insertRecord->execute([
                $row['edu_dt'],
                $courseName,
                $row['login_id']
            ]);
        }
    }

    private function migrateTenure() {
        echo "- Migrating Tenure Info...\n";
        $stmt = $this->db->query("SELECT * FROM bd_member_csdate");
        $tenures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insertTenure = $this->db->prepare("
            INSERT IGNORE INTO teacher_tenure (teacher_id, start_year, start_month)
            SELECT id, ?, ? FROM teachers WHERE login_id = ?
        ");

        foreach ($tenures as $row) {
            $insertTenure->execute([$row['cs_year'], $row['cs_month'], $row['login_id']]);
        }
    }

    private function migrateFurloughs() {
        echo "- Migrating Furlough History...\n";
        $legacy = $this->db->query("SELECT login_id, reason1, rsdt1, rsdt2, reason2, rsdt3, rsdt4, reason3, rsdt5, rsdt6 FROM bd_member_right WHERE reason1 > 0 OR reason2 > 0 OR reason3 > 0")->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO teacher_furloughs (teacher_id, reason, start_date, end_date)
            SELECT id, ?, ?, ? FROM teachers WHERE login_id = ?
        ");

        foreach ($legacy as $row) {
            if ($row['reason1'] > 0) {
                $stmt->execute([
                    $row['reason1'],
                    substr($row['rsdt1'], 0, 10),
                    ($row['rsdt2'] == '1900-01-01 00:00:00') ? null : substr($row['rsdt2'], 0, 10),
                    $row['login_id']
                ]);
            }
            if ($row['reason2'] > 0) {
                $stmt->execute([
                    $row['reason2'],
                    substr($row['rsdt3'], 0, 10),
                    ($row['rsdt4'] == '1900-01-01 00:00:00') ? null : substr($row['rsdt4'], 0, 10),
                    $row['login_id']
                ]);
            }
            if ($row['reason3'] > 0) {
                $stmt->execute([
                    $row['reason3'],
                    substr($row['rsdt5'], 0, 10),
                    ($row['rsdt6'] == '1900-01-01 00:00:00') ? null : substr($row['rsdt6'], 0, 10),
                    $row['login_id']
                ]);
            }
        }
    }

    private function migrateAwards() {
        echo "- Migrating Awards...\n";
        $stmt = $this->db->query("SELECT * FROM tch_tml");
        $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insertAward = $this->db->prepare("
            INSERT IGNORE INTO teacher_awards (teacher_id, award_type, award_year, remarks)
            SELECT id, ?, ?, ? FROM teachers WHERE login_id = ?
        ");

        foreach ($awards as $row) {
            $insertAward->execute([
                $row['tml'], 
                $row['tml_year'], 
                $row['tml_memo'] ?? null,
                $row['login_id']
            ]);
        }
    }

    /**
     * Consolidate duplicate education courses based on normalized names and manual mappings.
     */
    private function consolidateCourses() {
        echo "- Consolidating Education Courses...\n";
        
        // 1. Initial Rename/Merge based on specific rules
        $stmt = $this->db->query("SELECT * FROM education_courses");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($courses as $course) {
            $cleanName = $this->getCleanCourseName($course['course_name']);
            if ($cleanName !== $course['course_name']) {
                $check = $this->db->prepare("SELECT id FROM education_courses WHERE course_name = ? AND id != ?");
                $check->execute([$cleanName, $course['id']]);
                $existing = $check->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $this->db->prepare("UPDATE education_records SET course_id = ? WHERE course_id = ?")
                             ->execute([$existing['id'], $course['id']]);
                    $this->db->prepare("DELETE FROM education_courses WHERE id = ?")
                             ->execute([$course['id']]);
                } else {
                    $this->db->prepare("UPDATE education_courses SET course_name = ? WHERE id = ?")
                             ->execute([$cleanName, $course['id']]);
                }
            }
        }

        // 2. Secondary Merge based on normalization (spaces, dots, etc.)
        $stmt = $this->db->query("SELECT * FROM education_courses");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $groups = [];
        foreach ($courses as $c) {
            $norm = $this->normalizeCourseName($c['course_name']);
            $groups[$norm][] = $c;
        }

        foreach ($groups as $norm => $members) {
            if (count($members) > 1) {
                usort($members, function($a, $b) { return mb_strlen($a['course_name']) - mb_strlen($b['course_name']); });
                $rep = $members[0];
                for ($i = 1; $i < count($members); $i++) {
                    $other = $members[$i];
                    $this->db->prepare("UPDATE education_records SET course_id = ? WHERE course_id = ?")
                             ->execute([$rep['id'], $other['id']]);
                    $this->db->prepare("DELETE FROM education_courses WHERE id = ?")
                             ->execute([$other['id']]);
                }
            }
        }
    }

    private function getCleanCourseName($name) {
        // Remove date-like prefixes (e.g., 22-4, 22-10)
        $name = preg_replace('/^\d{2}-\d+-?/', '', $name);

        $lower = mb_strtolower($name, 'UTF-8');
        $noSpace = str_replace([' ', '.', '(', ')', '[', ']', '{', '}', ',', '론'], '', $lower);
        
        // Adolescent Dialogue
        if (in_array($noSpace, ['청소년대화법초등', '청소년대화법초등부'])) return '청소년대화법(초등부)';
        if (in_array($noSpace, ['청소년대화법중고등', '청소년대화법중고등부', '청소년대화법쭝고등'])) return '청소년대화법(중고등부)';
        
        // Spirituality
        if (in_array($noSpace, ['교리교사영성', '교리교사의영성', '교사영성'])) return '교리교사 영성';
        
        // Google Platforms
        if (in_array($noSpace, ['구글플랫폼', '구글플렛폼'])) return '구글 플랫폼';
        
        // Bible and Monthly Devotion
        if (in_array($noSpace, ['성경', '성경입문'])) return '성경입문';
        if (in_array($noSpace, ['성월', '성월교육'])) return '성월교육';
        
        // Zoom & Recreation
        if (in_array($noSpace, ['줌줌클래스', '줌활용법'])) return '줌 활용법';
        if (in_array($noSpace, ['레크', '레크레이션', '레크리에이션'])) return '레크레이션';

        // POP Hand-lettering
        if (strpos($noSpace, 'pop손글씨초급') !== false) return 'POP초급';
        if (strpos($noSpace, 'pop손글씨중급') !== false) return 'POP중급';
        if (strpos($noSpace, 'pop손글씨') !== false) return 'POP';
        
        // COVID-19
        if (in_array($noSpace, ['코로나아이이해', '코로나시대아이이해'])) return '코로나시대 아이 이해';
        if (in_array($noSpace, ['코로나시기지도방법', '코로나시대지도방법'])) return '코로나시대지도방법';

        return $name;
    }

    private function normalizeCourseName($name) {
        $name = preg_replace('/^\d{2}-\d+-?/', '', $name);
        return mb_strtolower(str_replace([' ', '.', '(', ')', '[', ']', '{', '}', ',', '론'], '', $name), 'UTF-8');
    }
}

$migrator = new Migrator();
$migrator->run();
