<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\TeacherService;

class TeacherController
{
    private TeacherService $service;

    public function __construct()
    {
        $this->service = new TeacherService();
    }

    public function index(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $bcode = (string)$session->get('bcode', '');
        $page = (int)($_GET['p'] ?? 1);
        $pageSize = (int)($_GET['pagesize'] ?? 20);
        
        $filters = [
            'search'   => $_GET['search'] ?? '',
            'category' => $_GET['search_category'] ?? 'name',
            'academy'  => $_GET['academy'] ?? 'all'
        ];

        $teachers = $this->service->getTeacherList($bcode, $filters, $page, $pageSize);
        $totalCount = $this->service->getTeacherCount($bcode, $filters);
        $pageCount = (int)ceil($totalCount / $pageSize);

        // Solve N+1: Fetch awards for all teachers in one go
        $loginIds = array_column($teachers, 'login_id');
        $allAwards = $this->service->getAwardsBatch($loginIds);

        foreach ($teachers as &$teacher) {
            $teacher['awards'] = $allAwards[$teacher['login_id']] ?? [];
        }

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/teachers/list.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function edit(string $loginId): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $teacher = $this->service->getTeacher($loginId);
        if (!$teacher) {
            header('Location: index.php?page=teacher_list&error=not_found');
            exit;
        }

        $teacher['awards'] = $this->service->getAwards($loginId);
        $teacher['edu_details'] = $this->service->getEducationDetails($loginId);
        $teacher['participation'] = $this->service->getParticipationHistory($loginId);

        $mode = 'edit';
        $title = '교사 정보 수정';

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/teachers/form.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function create(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $mode = 'create';
        $title = '교사 신규 등록';
        $teacher = []; // Empty for creation

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/teachers/form.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function save(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $loginId = $_POST['login_id'] ?? '';
        $mode = $_POST['mode'] ?? 'edit';

        // Comprehensive data mapping
        $data = [
            'name'     => $_POST['name'] ?? '',
            'bname'    => $_POST['bname'] ?? '',
            'jumin_f'  => $_POST['jumin_f'] ?? '',
            'bday'     => $_POST['bday'] ?? '',
            'phone1'   => $_POST['phone1'] ?? '',
            'phone2'   => $_POST['phone2'] ?? '',
            'email'    => $_POST['email'] ?? '',
            'postcode' => $_POST['postcode'] ?? '',
            'addr1'    => $_POST['addr1'] ?? '',
            'addr2'    => $_POST['addr2'] ?? '',
            'academy'  => $_POST['academy'] ?? '1',
            'type_num' => $_POST['type_num'] ?? '5',
            'ac_edpart02' => $_POST['ac_edpart02'] ?? '',
            'ac_edsc'     => $_POST['ac_edsc'] ?? '',
            'cs_year'     => $_POST['cs_year'] ?? '',
            'cs_month'    => $_POST['cs_month'] ?? '',
            
            // Furlough
            'reason1' => $_POST['reason1'] ?? '0',
            'rsdt1'   => $_POST['rsdt1'] ?? null,
            'rsdt2'   => $_POST['rsdt2'] ?? null,
            'reason2' => $_POST['reason2'] ?? '0',
            'rsdt3'   => $_POST['rsdt3'] ?? null,
            'rsdt4'   => $_POST['rsdt4'] ?? null,
            'reason3' => $_POST['reason3'] ?? '0',
            'rsdt5'   => $_POST['rsdt5'] ?? null,
            'rsdt6'   => $_POST['rsdt6'] ?? null,
        ];

        // Education Details (1-10)
        for ($i = 1; $i <= 10; $i++) {
            $data["edu_title_$i"] = $_POST["edu_title_$i"] ?? '';
            $data["edu_dt_$i"]    = $_POST["edu_dt_$i"] ?? '';
        }

        if ($mode === 'edit' && !empty($loginId)) {
            $success = $this->service->updateTeacher($loginId, $data);
            $msg = $success ? '정보가 수정되었습니다.' : '수정 중 오류가 발생했습니다.';
        } else {
            // Include bcode for creation
            $data['bcode'] = (string)$session->get('bcode', '');
            $success = $this->service->createTeacher($data);
            $msg = $success ? '새로운 교사가 등록되었습니다.' : '등록 중 오류가 발생했습니다.';
        }

        $base = App::getInstance()->getBasePath();
        echo "<script>alert('{$msg}'); location.href='{$base}index.php?page=teacher_list';</script>";
        exit;
    }
}
