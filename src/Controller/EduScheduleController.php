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

        $title = '교육 일정';
        
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/schedules/list.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function detail(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $idx = (int)($_GET['idx'] ?? 0);
        $schedule = $this->service->getSchedule($idx);

        header('Content-Type: application/json');
        echo json_encode($schedule);
        exit;
    }
}
