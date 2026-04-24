<?php
declare(strict_types=1);

// Simple Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\App;
use App\Controller\LoginController;

$app = App::getInstance();
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? null;

// Basic Router
if ($action === 'login') {
    (new LoginController())->login();
} elseif ($action === 'logout') {
    (new LoginController())->logout();
} elseif ($action === 'save_teacher') {
    (new \App\Controller\TeacherController())->save();
} elseif ($action === 'schedule_detail') {
    (new \App\Controller\EduScheduleController())->detail();
} else {
    switch ($page) {
        case 'login':
            (new LoginController())->show();
            break;
        case 'dashboard':
            if (!$app->session()->isLoggedIn()) {
                $base = $app->getBasePath();
                header("Location: {$base}index.php?page=login");
                exit;
            }
            (new \App\Controller\DashboardController())->show();
            break;
        case 'teacher_list':
            (new \App\Controller\TeacherController())->index();
            break;
        case 'teacher_edit':
            (new \App\Controller\TeacherController())->edit($_GET['login_id'] ?? '');
            break;
        case 'teacher_create':
            (new \App\Controller\TeacherController())->create();
            break;
        case 'statistics':
            (new \App\Controller\StatisticsController())->index();
            break;
        case 'edu_schedule':
            (new \App\Controller\EduScheduleController())->index();
            break;
        default:
            (new LoginController())->show();
            break;
    }
}
