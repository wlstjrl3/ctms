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
        $this->migrateAwards();
        
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
        echo "- Migrating Parishes...\n";
        $legacy = $this->db->query("SELECT * FROM search_bondang")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($legacy as $row) {
            $stmt = $this->db->prepare("
                REPLACE INTO parishes 
                (parish_code, parish_name, diocese_name, diocese_code, district_name, district_code) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $row['BCODE'], 
                $row['BONDANG'],
                $row['GYOGU'],
                $row['GCODE'],
                $row['JIGU'],
                $row['JCODE']
            ]);
        }

        // Populate vicariates and districts tables for management
        echo "- Populating Vicariates and Districts tables...\n";
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->query("TRUNCATE TABLE vicariates");
        $this->db->query("TRUNCATE TABLE districts");
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
        
        $this->db->query("
            INSERT INTO vicariates (name, code)
            SELECT DISTINCT diocese_name, diocese_code 
            FROM parishes 
            WHERE diocese_name IS NOT NULL AND diocese_name != ''
        ");

        $this->db->query("
            INSERT IGNORE INTO districts (vicariate_id, name, code)
            SELECT DISTINCT v.id, p.district_name, p.district_code
            FROM parishes p
            JOIN vicariates v ON p.diocese_code COLLATE utf8mb4_unicode_ci = v.code COLLATE utf8mb4_unicode_ci
            WHERE p.district_name IS NOT NULL AND p.district_name != ''
        ");

        // Link parishes to district_id
        $this->db->query("
            UPDATE parishes p
            JOIN districts d ON p.district_code COLLATE utf8mb4_unicode_ci = d.code COLLATE utf8mb4_unicode_ci
            SET p.district_id = d.id
        ");
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
        echo "- Migrating Teachers (with Y2K and Field mapping)...\n";
        $legacy = $this->db->query("SELECT * FROM bd_member_right")->fetchAll(PDO::FETCH_ASSOC);
        
        // Load parish mapping
        $parishes = $this->db->query("SELECT id, parish_code FROM parishes")->fetchAll(PDO::FETCH_KEY_PAIR);

        // Load position mapping
        $positions = [
            '1' => '교사',
            '2' => '교감',
            '3' => '교무',
            '4' => '총무'
        ];

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO teachers 
            (parish_id, login_id, name, baptismal_name, birth_date, feast_day, mobile_phone, home_phone, email, department, position, status, current_grade)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($legacy as $row) {
            $parishId = array_search($row['bcode'], $parishes) ?: null;
            if (!$parishId) {
                 // Try to find by code if the flip didn't work as expected
                 $parishId = null;
                 foreach($parishes as $id => $code) {
                     if($code === $row['bcode']) { $parishId = $id; break; }
                 }
            }

            // Y2K Correction for birthday
            $birthDate = null;
            if ($row['birthday'] && strlen($row['birthday']) === 8) {
                $year = (int)substr($row['birthday'], 0, 4);
                $month = substr($row['birthday'], 4, 2);
                $day = substr($row['birthday'], 6, 2);
                
                // Fix: 1900-1925 -> 2000-2025
                if ($year >= 1900 && $year <= 1925) $year += 100;
                // Fix: 2089 -> 1989
                if ($year >= 2050) $year -= 100;
                
                $birthDate = sprintf("%04d-%s-%s", $year, $month, $day);
            }

            // Feast day correction
            $feastDay = null;
            if ($row['bday'] && strlen($row['bday']) === 4) {
                $feastDay = substr($row['bday'], 0, 2) . '-' . substr($row['bday'], 2, 2);
            }

            $deptMap = [
                '1' => 'elementary',
                '2' => 'middle_high',
                '3' => 'disabled',
                '4' => 'integrated',
                '5' => 'daegun'
            ];

            $stmt->execute([
                $parishId,
                $row['login_id'],
                $row['name'],
                $row['bname'],
                $birthDate,
                $feastDay,
                $row['phone2'], // mobile
                $row['phone1'], // home
                null, // email (not in legacy main)
                $deptMap[$row['academy']] ?? 'elementary',
                $positions[$row['type_num']] ?? '교사',
                ($row['state'] == '0') ? 'active' : 'inactive',
                $row['type_etc'] // remarks
            ]);
        }
        $this->migrateTenure();
        $this->migrateEducation();
    }

    private function migrateEducation() {
        echo "- Migrating Education Records...\n";
        $stmt = $this->db->query("SELECT * FROM bd_member_education");
        $eduData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insertCourse = $this->db->prepare("INSERT IGNORE INTO education_courses (course_name, category) VALUES (?, 'General')");
        
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

            // 1. Ensure course exists (UNIQUE constraint will prevent duplicates now)
            $insertCourse->execute([$courseName]);
            
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
}

$migrator = new Migrator();
$migrator->run();
