<?php
declare(strict_types=1);

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\App;
use App\Service\TeacherService;

$app = App::getInstance();
$service = new TeacherService();

$testData = [
    'name' => '테스트교사',
    'bname' => '프란치스코',
    'jumin_f' => '900101',
    'bday' => '01/01',
    'phone1' => '02-123-4567',
    'phone2' => '010-1234-5678',
    'email' => 'test@example.com',
    'postcode' => '12345',
    'addr1' => '서울시 중구',
    'addr2' => '101호',
    'academy' => '1',
    'type_num' => '5',
    'ac_edpart02' => '3학년',
    'ac_edsc' => '테스트 비고입니다.',
    'cs_year' => '2020',
    'cs_month' => '03',
    'bcode' => '001', // Dummy bcode
    'reason1' => '0',
    'rsdt1' => null,
    'rsdt2' => null,
    'reason2' => '0',
    'rsdt3' => null,
    'rsdt4' => null,
    'reason3' => '0',
    'rsdt5' => null,
    'rsdt6' => null,
];

echo "--- Testing Teacher Creation ---\n";
$success = $service->createTeacher($testData);

if ($success) {
    echo "Teacher created successfully.\n";
    
    // Find the teacher (since login_id is generated, we might need to find it by name or just check the latest)
    $db = $app->db();
    $teacher = $db->fetch("SELECT login_id FROM bd_member_right WHERE name = '테스트교사' ORDER BY num DESC LIMIT 1");
    
    if ($teacher) {
        $loginId = $teacher['login_id'];
        echo "Found created teacher ID: $loginId\n";
        
        echo "--- Testing Data Fetching ---\n";
        $data = $service->getTeacher($loginId);
        
        $fieldsToVerify = [
            'name' => '테스트교사',
            'strEmail' => 'test@example.com',
            'ac_edsc' => '테스트 비고입니다.',
            'cs_year' => '2020'
        ];
        
        foreach ($fieldsToVerify as $key => $expected) {
            $actual = $data[$key] ?? 'MISSING';
            if ($actual === $expected) {
                echo "[PASS] $key matches: $actual\n";
            } else {
                echo "[FAIL] $key mismatch! Expected: $expected, Actual: $actual\n";
            }
        }
        
        echo "--- Testing Update ---\n";
        $testData['name'] = '테스트교사_수정';
        $updateSuccess = $service->updateTeacher($loginId, $testData);
        if ($updateSuccess) {
            $updated = $service->getTeacher($loginId);
            if ($updated['name'] === '테스트교사_수정') {
                echo "[PASS] Update verified.\n";
            } else {
                echo "[FAIL] Update verification failed.\n";
            }
        } else {
            echo "[FAIL] Update failed.\n";
        }
        
    } else {
        echo "Could not find created teacher in DB.\n";
    }
} else {
    echo "Teacher creation failed.\n";
}
