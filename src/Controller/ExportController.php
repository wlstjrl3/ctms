<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\TeacherService;
use App\Service\ExportService;

class ExportController
{
    private TeacherService $teacherService;
    private ExportService $exportService;

    public function __construct()
    {
        $this->teacherService = new TeacherService();
        $this->exportService = new ExportService();
    }

    public function teachers(): void
    {
        ob_start();
        $session = App::getInstance()->session();
        if (!$session->isLoggedIn()) {
            header('Location: index.php?page=login');
            exit;
        }

        $bcode = (string)$session->get('bcode', '');
        $orgCd = ($session->getRole() === 'bondang') ? (string)$session->get('org_cd', '') : '';
        
        // Fetch all teachers matching filters but without pagination
        $filters = $_GET;

        // Release session lock for long-running export
        session_write_close();

        // We use a high limit for export
        $teachers = $this->teacherService->getTeacherList($orgCd, $filters, 1, 10000);
        
        // Solve N+1: Fetch awards and core education for all teachers in one go
        $teacherIds = array_column($teachers, 'id');
        $allAwards = $this->teacherService->getAwardsBatch($teacherIds);
        $allEdu = $this->teacherService->getEducationBatch($teacherIds);

        foreach ($teachers as &$teacher) {
            $teacher['awards'] = $allAwards[$teacher['id']] ?? [];
            $teacher['core_edu_list'] = $allEdu[$teacher['id']] ?? [];
        }

        $csv = $this->exportService->exportTeachersToCsv($teachers);

        $filename = "teachers_export_" . date('YmdHis') . ".csv";
        
        // Clear any previous output buffers
        if (ob_get_level()) ob_end_clean();

        header('Content-Type: application/force-download');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }
}
