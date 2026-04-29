<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\EduScheduleService;

class EduScheduleController
{
    private EduScheduleService $service;

    public function __construct()
    {
        $this->service = new EduScheduleService(App::getInstance()->db());
    }

    public function index(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $year = (int)($_GET['year'] ?? date('Y'));
        $state = (string)($_GET['state'] ?? 'all');

        $schedules = $this->service->getSchedules($year, $state);
        $availableYears = $this->service->getAvailableYears();
        
        // Fetch active courses for the selection dropdown
        $educationService = new \App\Service\EducationService();
        $activeCourses = $educationService->getActiveCourses();

        $title = '교육 일정';
        
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/schedules/list.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function save(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $data = [
            'idx_num' => $_POST['idx_num'] ?? null,
            'course_id' => !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null,
            'edu_subject' => $_POST['edu_subject'] ?? '',
            'edu_date' => $_POST['edu_date'] ?? date('Y-m-d H:i:s'),
            'edu_place' => $_POST['edu_place'] ?? '',
            'edu_year' => $_POST['edu_year'] ?? date('Y'),
            'edu_level' => $_POST['edu_level'] ?? '0',
            'edu_state' => $_POST['edu_state'] ?? '0',
            'edu_content' => $_POST['edu_content'] ?? '',
            'edu_money' => $_POST['edu_money'] ?? 0,
            'edu_maxp' => $_POST['edu_maxp'] ?? 0
        ];

        $this->service->saveSchedule($data);

        $base = App::getInstance()->getBasePath();
        header("Location: {$base}index.php?page=edu_schedule&year={$data['edu_year']}");
        exit;
    }

    public function delete(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            exit;
        }

        $idx = (int)($_GET['idx'] ?? 0);
        if ($idx > 0) {
            $this->service->deleteSchedule($idx);
        }

        $base = App::getInstance()->getBasePath();
        header("Location: {$base}index.php?page=edu_schedule");
        exit;
    }

    public function detail(): void
    {
        ob_start(); // Start buffering to catch any potential notices
        
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            ob_clean();
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $idx = (int)($_GET['idx'] ?? 0);
        $schedule = $this->service->getSchedule($idx);

        ob_clean(); // Clean any notices/warnings before outputting JSON
        header('Content-Type: application/json');
        echo json_encode($schedule);
        exit;
    }
}
