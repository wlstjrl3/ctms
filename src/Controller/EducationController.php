<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\EducationService;

class EducationController
{
    private EducationService $service;

    public function __construct()
    {
        $this->service = new EducationService();
    }

    public function index(): void
    {
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $filters = [
            'keyword' => $_GET['keyword'] ?? '',
            'category' => $_GET['category'] ?? 'all',
            'status' => $_GET['status'] ?? 'all'
        ];

        $courses = $this->service->getCourseList($filters);
        
        require __DIR__ . '/../../views/layouts/header.php';
        require __DIR__ . '/../../views/pages/education/list.php';
        require __DIR__ . '/../../views/layouts/footer.php';
    }

    public function save(): void
    {
        $id = $_POST['id'] ?? null;
        $data = [
            'id' => $id ? (int)$id : null,
            'course_name' => $_POST['course_name'] ?? '',
            'category' => $_POST['category'] ?? 'General'
        ];

        if (!empty($data['course_name'])) {
            $this->service->saveCourse($data);
        }

        $base = App::getInstance()->getBasePath();
        header("Location: {$base}index.php?page=education_list");
        exit;
    }

    public function toggleActive(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->service->toggleStatus($id);
        }

        $base = App::getInstance()->getBasePath();
        header("Location: {$base}index.php?page=education_list");
        exit;
    }

    public function ajaxSearch(): void
    {
        $keyword = $_GET['keyword'] ?? '';
        $category = $_GET['category'] ?? 'all';
        $results = $this->service->searchCourses($keyword, $category);

        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}
