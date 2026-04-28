<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\ParishService;

class ParishController
{
    private ParishService $service;

    public function __construct()
    {
        $this->service = new ParishService();
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
        
        $page = (int)($_GET['p'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 15);
        $filters = $_GET;

        $parishes = $this->service->getParishList($filters, $page, $pageSize);
        $totalCount = $this->service->getParishCount($filters);
        $pageCount = (int)ceil($totalCount / $pageSize);

        $dioceses = $this->service->getDioceses();
        $districts = $this->service->getDistricts(); // Get all districts for filter options
        
        $title = '조직 코드 관리';

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/parish/list.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function create(): void
    {
        $this->checkPermission();
        
        $mode = 'create';
        $title = '신규 본당 등록';
        $parish = [];
        $dioceses = $this->service->getDioceses();
        $allDistricts = $this->service->getDistricts(); // For JS dynamic matching

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/parish/form.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function edit(): void
    {
        $this->checkPermission();
        
        $idx = (int)($_GET['idx'] ?? 0);
        $parish = $this->service->getParish($idx);
        
        if (!$parish) {
            header('Location: index.php?page=parish_list&error=not_found');
            exit;
        }

        $mode = 'edit';
        $title = '본당 정보 수정';
        $dioceses = $this->service->getDioceses();
        $allDistricts = $this->service->getDistricts();

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/parish/form.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function save(): void
    {
        $this->checkPermission();
        
        $idx  = (int)($_POST['idx'] ?? 0);
        $mode = $_POST['mode'] ?? 'edit';

        $data = [
            'jcode'   => $_POST['jcode']   ?? '',  // district ORG_CD (e.g. 13090001)
            'bondang' => $_POST['bondang'] ?? '',
            'bcode'   => $_POST['bcode']   ?? '',
            'phone'   => $_POST['phone']   ?? '',
        ];

        if ($mode === 'edit' && $idx > 0) {
            $this->service->updateParish($idx, $data);
            $msg = '수정되었습니다.';
        } else {
            $this->service->createParish($data);
            $msg = '등록되었습니다.';
        }

        $base = App::getInstance()->getBasePath();
        echo "<script>alert('{$msg}'); location.href='{$base}index.php?page=parish_list';</script>";
        exit;
    }

    public function delete(): void
    {
        $this->checkPermission();
        
        $idx = (int)($_GET['idx'] ?? 0);
        $this->service->deleteParish($idx);
        
        $base = App::getInstance()->getBasePath();
        header("Location: {$base}index.php?page=parish_list&msg=deleted");
        exit;
    }

    public function saveVicariate(): void {
        $this->checkPermission();
        $id = (int)($_POST['id'] ?? 0);
        $data = ['name' => $_POST['name'], 'code' => $_POST['code']];
        if ($id > 0) $this->service->updateVicariate($id, $data);
        else $this->service->createVicariate($data);
        header('Location: index.php?page=parish_list&msg=success');
    }

    public function deleteVicariate(): void {
        $this->checkPermission();
        $id = (int)($_GET['id'] ?? 0);
        $this->service->deleteVicariate($id);
        header('Location: index.php?page=parish_list&msg=deleted');
    }

    public function saveDistrict(): void {
        $this->checkPermission();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'vicariate_id' => (int)$_POST['vicariate_id'], 
            'name' => $_POST['name'], 
            'code' => $_POST['code'],
            'use_yn' => $_POST['use_yn'] ?? 'N'
        ];
        if ($id > 0) $this->service->updateDistrict($id, $data);
        else $this->service->createDistrict($data);
        header('Location: index.php?page=parish_list&msg=success');
    }

    public function deleteDistrict(): void {
        $this->checkPermission();
        $id = (int)($_GET['id'] ?? 0);
        $this->service->deleteDistrict($id);
        header('Location: index.php?page=parish_list&msg=deleted');
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

        $parishes = $this->service->getParishList($filters, $page, $pageSize);
        $totalCount = $this->service->getParishCount($filters);
        $pageCount = (int)ceil($totalCount / $pageSize);

        $base = App::getInstance()->getBasePath();
        
        ob_start();
        include __DIR__ . '/../../views/pages/parish/list_rows.php';
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

    public function ajaxSearch(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $keyword = $_GET['keyword'] ?? '';
        $vicariateId = $_GET['vicariate_id'] ?? '';
        $districtId = $_GET['district_id'] ?? '';
        
        $filters = [
            'keyword' => $keyword,
            'vicariate_id' => $vicariateId,
            'district_id' => $districtId
        ];

        $results = $this->service->searchParishes($filters);
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}
