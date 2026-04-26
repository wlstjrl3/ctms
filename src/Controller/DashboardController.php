<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\DashboardService;
use App\Service\TeacherService;

class DashboardController
{
    private DashboardService $service;
    private TeacherService $teacherService;

    public function __construct()
    {
        $this->service = new DashboardService();
        $this->teacherService = new TeacherService();
    }

    public function show(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $role = $session->getRole();
        $data = [
            'schedules' => $this->service->getMonthlySchedules(),
            'applications' => $this->service->getMonthlyApplications(),
        ];

        if ($role === 'casuwon' || $role === 'diocese') {
            $data['recentLogins'] = $this->service->getRecentLogins();
        }

        if ($role === 'bondang') {
            $bcode = (string)$session->get('bcode', '');
            $data['myApplications'] = $this->service->getParishApplicationStatus($bcode);
        }

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/dashboard.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }
}
