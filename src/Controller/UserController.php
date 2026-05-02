<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\UserService;

class UserController
{
    private UserService $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    private function checkPermission(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn() || !$session->hasPermission('casuwon')) {
            $base = App::getInstance()->getBasePath();
            header("Location: {$base}index.php?page=login&error=unauthorized");
            exit;
        }
    }

    public function index(): void
    {
        $this->checkPermission();
        
        $page = (int)($_GET['page_num'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 15);
        $filters = [
            'search'   => $_GET['search'] ?? '',
            'category' => $_GET['search_category'] ?? 'name'
        ];

        $users = $this->service->getUserList($filters, $page, $pageSize);
        $totalCount = $this->service->getUserCount($filters);
        $pageCount = (int)ceil($totalCount / $pageSize);

        $title = '본당 계정 관리';

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/users/list.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function create(): void
    {
        $this->checkPermission();
        
        $mode = 'create';
        $title = '신규 본당 계정 등록';
        $user = [];

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/users/form.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function edit(): void
    {
        $this->checkPermission();
        
        $idx = (int)($_GET['idx'] ?? 0);
        $user = $this->service->getUser($idx);
        
        if (!$user) {
            header('Location: index.php?page=user_list&error=not_found');
            exit;
        }

        $mode = 'edit';
        $title = '본당 계정 수정';

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/users/form.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function save(): void
    {
        $this->checkPermission();
        
        $idx = (int)($_POST['idx'] ?? 0);
        $mode = $_POST['mode'] ?? 'edit';

        $data = [
            'login_id' => $_POST['login_id'] ?? '',
            'password' => $_POST['password'] ?? '',
            'name'     => $_POST['name'] ?? '',
            'org_cd'   => $_POST['org_cd'] ?? '',   // parish ORG_CD (e.g. 13110004)
            'org_in_tel'  => $_POST['org_in_tel'] ?? '',
            'org_out_tel' => $_POST['org_out_tel'] ?? '',
            'role'     => $_POST['role'] ?? 'bondang',
        ];

        if ($mode === 'edit' && $idx > 0) {
            $this->service->updateUser($idx, $data);
            $msg = '수정되었습니다.';
        } else {
            $this->service->createUser($data);
            $msg = '등록되었습니다.';
        }

        $base = App::getInstance()->getBasePath();
        echo "<script>alert('{$msg}'); location.href='{$base}index.php?page=user_list';</script>";
        exit;
    }

    public function delete(): void
    {
        $this->checkPermission();
        
        $idx = (int)($_GET['idx'] ?? 0);
        $this->service->deleteUser($idx);
        
        $base = App::getInstance()->getBasePath();
        header("Location: {$base}index.php?page=user_list&msg=deleted");
        exit;
    }

    public function ajaxList(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn() || !$session->hasPermission('casuwon')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $page = (int)($_GET['p'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 15);
        $filters = $_GET;

        $users = $this->service->getUserList($filters, $page, $pageSize);
        $totalCount = $this->service->getUserCount($filters);
        $pageCount = (int)ceil($totalCount / $pageSize);

        $base = App::getInstance()->getBasePath();
        
        ob_start();
        include __DIR__ . '/../../views/pages/users/list_rows.php';
        $rowsHtml = ob_get_clean();

        ob_start();
        include __DIR__ . '/../../views/layouts/pagination.php';
        $paginationHtml = ob_get_clean();

        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo json_encode([
            'html' => $rowsHtml,
            'paginationHtml' => $paginationHtml,
            'totalCount' => $totalCount,
            'pageCount' => $pageCount
        ]);
        exit;
    }
}
