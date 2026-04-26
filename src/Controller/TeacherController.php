<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\TeacherService;
use App\Service\ParishService;

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
        $pageSize = (int)($_GET['page_size'] ?? 15);
        
        $filters = [
            'search'   => $_GET['search'] ?? '',
            'category' => $_GET['search_category'] ?? 'name',
            'academy'  => $_GET['academy'] ?? 'all'
        ];

        $teachers = $this->service->getTeacherList($bcode, $filters, $page, $pageSize);
        $totalCount = $this->service->getTeacherCount($bcode, $filters);
        $pageCount = (int)ceil($totalCount / $pageSize);

        // Solve N+1: Fetch awards for all teachers in one go
        $teacherIds = array_column($teachers, 'id');
        $allAwards = $this->service->getAwardsBatch($teacherIds);

        foreach ($teachers as &$teacher) {
            $teacher['awards'] = $allAwards[$teacher['id']] ?? [];
        }

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/teachers/list.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function ajaxList(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $bcode = (string)$session->get('bcode', '');
        $page = (int)($_GET['p'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 15);
        
        $filters = $_GET;

        $teachers = $this->service->getTeacherList($bcode, $filters, $page, $pageSize);
        $totalCount = $this->service->getTeacherCount($bcode, $filters);
        
        $teacherIds = array_column($teachers, 'id');
        $allAwards = $this->service->getAwardsBatch($teacherIds);
        foreach ($teachers as &$teacher) {
            $teacher['awards'] = $allAwards[$teacher['id']] ?? [];
        }

        $base = App::getInstance()->getBasePath();
        
        // Render Rows
        ob_start();
        include __DIR__ . '/../../views/pages/teachers/list_rows.php';
        $rowsHtml = ob_get_clean();

        // Render Pagination
        $pageCount = (int)ceil($totalCount / $pageSize);
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

        $parishService = new ParishService();
        $parishes = $parishService->getParishList([], 1, 500); // Fetch all for select

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

        $parishService = new ParishService();
        $parishes = $parishService->getParishList([], 1, 500);

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
            'name'      => $_POST['name'] ?? '',
            'parish_id' => $_POST['parish_id'] ?? null,
            'bname'     => $_POST['bname'] ?? '',
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
            'position'    => $_POST['position'] ?? '',
            'ac_edsc'     => $_POST['ac_edsc'] ?? '',
            'cs_year'     => $_POST['cs_year'] ?? '',
            'cs_month'    => $_POST['cs_month'] ?? '',
            
            // Furlough (Dynamic)
            'furloughs' => [],
            'education' => [],
            'awards'    => []
        ];

        if (isset($_POST['furlough_reason']) && is_array($_POST['furlough_reason'])) {
            foreach ($_POST['furlough_reason'] as $i => $reason) {
                if ($reason !== '0' || !empty($_POST['furlough_start'][$i])) {
                    $data['furloughs'][] = [
                        'reason'     => $reason,
                        'start_date' => $_POST['furlough_start'][$i] ?? null,
                        'end_date'   => $_POST['furlough_end'][$i] ?? null
                    ];
                }
            }
        }

        // Education (Dynamic)
        if (isset($_POST['edu_title']) && is_array($_POST['edu_title'])) {
            foreach ($_POST['edu_title'] as $i => $title) {
                if (!empty($title)) {
                    $data['education'][] = [
                        'title' => $title,
                        'date'  => $_POST['edu_date'][$i] ?? null
                    ];
                }
            }
        }

        // Parse awards
        if (isset($_POST['award_year']) && is_array($_POST['award_year'])) {
            foreach ($_POST['award_year'] as $i => $year) {
                if (!empty($year)) {
                    $data['awards'][] = [
                        'tml_year' => $year,
                        'tml'      => $_POST['award_name'][$i] ?? '',
                        'bcode'    => (string)$session->get('bcode', '')
                    ];
                }
            }
        }
        // Handle Photo Upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxSize) {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $newFileName = 'teacher_' . ($loginId ?: uniqid()) . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../../public/uploads/photos/';
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newFileName)) {
                    $photoPath = 'uploads/photos/' . $newFileName;
                }
            }
        }
        $data['photo_path'] = $photoPath;

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
