<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\StatisticsService;

class StatisticsController
{
    private StatisticsService $service;

    public function __construct()
    {
        $this->service = new StatisticsService(App::getInstance()->db());
    }

    public function index(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $bcode = (string)$session->get('bcode', '');
        
        // Fetch various stats
        $academyStats = $this->service->getTeacherStatsByAcademy($bcode);
        $positionStats = $this->service->getTeacherStatsByPosition($bcode);

        $title = '각종 통계';
        
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/statistics/dashboard.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }
}
