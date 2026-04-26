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
        
        // Clear modern tables first to avoid duplicates
        $this->clearTables();
        
        $this->migrateParishes();
        $this->migrateUsers();
        $this->migrateTeachers();
        $this->migrateAwards();
        
        echo "Migration Finished Successfully.\n";
    }

    private function clearTables() {
        echo "- Clearing existing data...\n";
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->query("TRUNCATE TABLE teacher_awards");
        $this->db->query("TRUNCATE TABLE teacher_tenure");
        $this->db->query("TRUNCATE TABLE teacher_furloughs");
        $this->db->query("TRUNCATE TABLE education_records");
        $this->db->query("TRUNCATE TABLE teachers");
        $this->db->query("TRUNCATE TABLE users");
        $this->db->query("TRUNCATE TABLE parishes");
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    private function migrateParishes() {
        echo "- Migrating Parishes...\n";
        // Assuming search_bondang is the legacy parish table
        // Adjust if actual source is different. 
        // This is a placeholder for the logic discovered.
        $legacy = $this->db->query("SELECT * FROM search_bondang")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($legacy as $row) {
            $stmt = $this->db->prepare("INSERT IGNORE INTO parishes (parish_code, parish_name) VALUES (?, ?)");
            $stmt->execute([$row['BCODE'], $row['BONDANG']]);
        }
    }

    private function migrateUsers() {
        echo "- Migrating Users (Admin & Parish Accounts)...\n";
        
        // 1. Migrate from legacy table
        $legacy = $this->db->query("SELECT * FROM ctms_user_info")->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $this->db->prepare("INSERT IGNORE INTO users (login_id, password_hash, name, role) VALUES (?, ?, ?, ?)");
        
        foreach ($legacy as $row) {
            $role = 'bondang';
            $uid = $row['ctms_uid'];
            
            if (in_array($uid, ['jsyang', 'youthet', 'swscout', 'admin1004'])) {
                $role = 'casuwon';
            } elseif (in_array($uid, ['youth-v1', 'youth-v2', 'youthas'])) {
                $role = 'diocese';
            }
            
            $name = in_array($uid, ['jsyang', 'youthet', 'swscout', 'admin1004']) ? ($row['ctms_uname'] ?: '관리자') : $row['ctms_uname'];
            $stmt->execute([$uid, $row['ctms_upwd'], $name, $role]);
        }

        // 2. Ensure critical accounts from legacy files are present even if not in ctms_user_info
        $manualUsers = [
            ['admin1004', '카숸', '전체 관리자', 'casuwon'],
            ['youthas', '4..1', '안산대리구 관리자', 'diocese']
        ];

        foreach ($manualUsers as $user) {
            $stmt->execute($user);
        }

        // 3. Fix roles for existing users that might have been migrated with wrong roles (if any)
        $this->db->query("UPDATE users SET role = 'casuwon' WHERE login_id IN ('jsyang', 'youthet', 'swscout', 'admin1004')");
        $this->db->query("UPDATE users SET role = 'diocese' WHERE login_id IN ('youth-v1', 'youth-v2', 'youthas')");
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
        $insertRecord = $this->db->prepare("
            INSERT IGNORE INTO education_records (teacher_id, course_id, completion_date, status)
            SELECT t.id, c.id, ?, 'Completed'
            FROM teachers t
            JOIN education_courses c ON c.course_name = ?
            WHERE t.login_id = ?
        ");

        foreach ($eduData as $row) {
            // Ensure course exists
            $insertCourse->execute([$row['edu_title']]);
            
            // Insert record
            $insertRecord->execute([
                $row['edu_dt'],
                $row['edu_title'],
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
