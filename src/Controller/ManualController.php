<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;

class ManualController
{
    public function index(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $title = '시스템 이용 매뉴얼';
        
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/manual.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }
}
