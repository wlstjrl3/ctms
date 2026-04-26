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
} elseif ($action === 'export_teachers') {
    (new \App\Controller\ExportController())->teachers();
} elseif ($action === 'save_user') {
    (new \App\Controller\UserController())->save();
} elseif ($action === 'user_delete') {
    (new \App\Controller\UserController())->delete();
} elseif ($action === 'save_parish') {
    (new \App\Controller\ParishController())->save();
} elseif ($action === 'parish_delete') {
    (new \App\Controller\ParishController())->delete();
} elseif ($action === 'save_vicariate') {
    (new \App\Controller\ParishController())->saveVicariate();
} elseif ($action === 'delete_vicariate') {
    (new \App\Controller\ParishController())->deleteVicariate();
} elseif ($action === 'save_district') {
    (new \App\Controller\ParishController())->saveDistrict();
} elseif ($action === 'delete_district') {
    (new \App\Controller\ParishController())->deleteDistrict();
} elseif ($action === 'ajax_teachers') {
    (new \App\Controller\TeacherController())->ajaxList();
} elseif ($action === 'ajax_users') {
    (new \App\Controller\UserController())->ajaxList();
} elseif ($action === 'ajax_parishes') {
    (new \App\Controller\ParishController())->ajaxList();
} elseif ($action === 'parish_search') {
    (new \App\Controller\ParishController())->ajaxSearch();
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
        case 'user_list':
            (new \App\Controller\UserController())->index();
            break;
        case 'user_create':
            (new \App\Controller\UserController())->create();
            break;
        case 'user_edit':
            (new \App\Controller\UserController())->edit();
            break;
        case 'parish_list':
            (new \App\Controller\ParishController())->index();
            break;
        case 'parish_create':
            (new \App\Controller\ParishController())->create();
            break;
        case 'parish_edit':
            (new \App\Controller\ParishController())->edit();
            break;
        case 'statistics':
            (new \App\Controller\StatisticsController())->index();
            break;
        case 'edu_schedule':
            (new \App\Controller\EduScheduleController())->index();
            break;
        case 'manual':
            (new \App\Controller\ManualController())->index();
            break;
        default:
            (new LoginController())->show();
            break;
    }
}
