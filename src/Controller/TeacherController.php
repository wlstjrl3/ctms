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

        // casuwon/diocese 계정은 전체 교사 조회, bondang 계정은 자기 본당만
        $role = $session->getRole();
        $orgCd = ($role === 'bondang') ? (string)$session->get('org_cd', '') : '';
        $page = (int)($_GET['p'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 15);
        
        $filters = $_GET;

        $educationService = new \App\Service\EducationService();
        $courses = $educationService->getActiveCourses();

        $teachers = $this->service->getTeacherList($orgCd, $filters, $page, $pageSize);
        $totalCount = $this->service->getTeacherCount($orgCd, $filters);
        $pageCount = (int)ceil($totalCount / $pageSize);

        $parishes = []; // No longer needed for select box, using text input instead

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

        // casuwon/diocese 계정은 전체 교사 조회, bondang 계정은 자기 본당만
        $role = $session->getRole();
        $orgCd = ($role === 'bondang') ? (string)$session->get('org_cd', '') : '';
        $page = (int)($_GET['p'] ?? 1);
        $pageSize = (int)($_GET['page_size'] ?? 15);
        
        $filters = $_GET;

        $teachers = $this->service->getTeacherList($orgCd, $filters, $page, $pageSize);
        $totalCount = $this->service->getTeacherCount($orgCd, $filters);
        
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
        $vicariates = $parishService->getDioceses();
        $districts = $parishService->getDistricts();

        $teacher['awards'] = $this->service->getAwards($loginId);
        $teacher['edu_details'] = $this->service->getEducationDetails($loginId);

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
        $vicariates = $parishService->getDioceses();
        $districts = $parishService->getDistricts();

        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/teachers/form.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function delete(): void
    {
        $loginId = $_GET['login_id'] ?? '';
        if (empty($loginId)) return;
        
        if ($this->service->deleteTeacher($loginId)) {
            $base = \App\Core\App::getInstance()->getBasePath();
            header("Location: {$base}index.php?page=teacher_list");
            exit;
        }
    }

    public function ajaxSave(): void
    {
        $session = App::getInstance()->session();
        header('Content-Type: application/json');
        
        if (!$session->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
            exit;
        }

        $loginId = $_POST['login_id'] ?? '';
        $mode = $_POST['mode'] ?? 'edit';

        // Extract the same data mapping logic (simplified for AJAX if needed, but keeping consistency)
        $data = $this->parsePostData($session, $loginId);

        if ($mode === 'edit' && !empty($loginId)) {
            $success = $this->service->updateTeacher($loginId, $data);
            $message = '정보가 실시간으로 반영되었습니다.';
            $newId = $loginId;
        } else {
            $data['bcode'] = (string)$session->get('bcode', '');
            $newId = $this->service->createTeacher($data);
            $success = ($newId !== false);
            $message = $success ? '새로운 교사가 등록되었습니다.' : '등록 중 오류가 발생했습니다.';
        }

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => $success, 
            'message' => $message,
            'login_id' => $newId,
            'mode' => 'edit'
        ]);
        exit;
    }

    private function parsePostData($session, $loginId): array
    {
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
            'status'      => $_POST['status'] ?? 'active',
            'current_grade' => $_POST['ac_edsc'] ?? '',
            'cs_year'     => $_POST['cs_year'] ?? '',
            'cs_month'    => $_POST['cs_month'] ?? '',
            'furloughs' => [], 'education' => [], 'awards' => []
        ];

        // Parse Furloughs
        if (isset($_POST['furlough_reason']) && is_array($_POST['furlough_reason'])) {
            foreach ($_POST['furlough_reason'] as $i => $reason) {
                if ($reason !== '0' || !empty($_POST['furlough_start'][$i])) {
                    $data['furloughs'][] = [
                        'reason' => $reason,
                        'start_date' => $_POST['furlough_start'][$i] ?? null,
                        'end_date' => $_POST['furlough_end'][$i] ?? null
                    ];
                }
            }
        }

        // Parse Education
        if (isset($_POST['edu_course_id']) && is_array($_POST['edu_course_id'])) {
            foreach ($_POST['edu_course_id'] as $i => $courseId) {
                if (!empty($courseId)) {
                    $data['education'][] = [
                        'course_id' => (int)$courseId,
                        'date' => $_POST['edu_date'][$i] ?? null
                    ];
                }
            }
        }

        // Parse Awards
        if (isset($_POST['award_year']) && is_array($_POST['award_year'])) {
            foreach ($_POST['award_year'] as $i => $year) {
                $awardName = $_POST['award_name'][$i] ?? '';
                if (!empty($year) && !empty($awardName)) {
                    $data['awards'][] = [
                        'tml_year' => $year,
                        'tml' => $awardName,
                        'bcode' => (string)$session->get('bcode', '')
                    ];
                }
            }
        }

        // Handle Photo Upload
        $data['photo_path'] = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (in_array($_FILES['photo']['type'], $allowedTypes) && $_FILES['photo']['size'] <= $maxSize) {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $newFileName = 'teacher_' . ($loginId ?: 'new_' . uniqid()) . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../../public/uploads/photos/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newFileName)) {
                    $data['photo_path'] = 'uploads/photos/' . $newFileName;
                }
            }
        }

        return $data;
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
        $data = $this->parsePostData($session, $loginId);

        if ($mode === 'edit' && !empty($loginId)) {
            $success = $this->service->updateTeacher($loginId, $data);
            $msg = $success ? '정보가 수정되었습니다.' : '수정 중 오류가 발생했습니다.';
        } else {
            $data['bcode'] = (string)$session->get('bcode', '');
            $success = $this->service->createTeacher($data);
            $msg = $success ? '새로운 교사가 등록되었습니다.' : '등록 중 오류가 발생했습니다.';
        }

        $base = App::getInstance()->getBasePath();
        echo "<script>alert('{$msg}'); location.href='{$base}index.php?page=teacher_list';</script>";
        exit;
    }
}
