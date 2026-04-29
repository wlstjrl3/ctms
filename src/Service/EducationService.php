<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\App;

class EducationService
{
    private $db;

    public function __construct()
    {
        $this->db = App::getInstance()->db();
    }

    public function getCourseList(array $filters = []): array
    {
        $sql = "SELECT * FROM education_courses WHERE 1=1";
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND course_name LIKE ?";
            $params[] = "%{$filters['keyword']}%";
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['status'];
        }

        $sql .= " ORDER BY course_name ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getActiveCourses(): array
    {
        return $this->db->fetchAll("SELECT * FROM education_courses WHERE is_active = 1 ORDER BY course_name ASC");
    }

    public function searchCourses(string $keyword, string $category = 'all'): array
    {
        $sql = "SELECT * FROM education_courses WHERE is_active = 1";
        $params = [];
        
        if (!empty($keyword)) {
            $sql .= " AND course_name LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        if ($category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY course_name ASC LIMIT 50";
        return $this->db->fetchAll($sql, $params);
    }

    public function saveCourse(array $data): bool
    {
        if (!empty($data['id'])) {
            $this->db->query(
                "UPDATE education_courses SET course_name = ?, category = ? WHERE id = ?",
                [$data['course_name'], $data['category'] ?? '[미분류]', $data['id']]
            );
        } else {
            $this->db->query(
                "INSERT INTO education_courses (course_name, category, is_active) VALUES (?, ?, 1)",
                [$data['course_name'], $data['category'] ?? '[미분류]']
            );
        }
        return true;
    }

    public function toggleStatus(int $id): bool
    {
        $this->db->query(
            "UPDATE education_courses SET is_active = 1 - is_active WHERE id = ?",
            [$id]
        );
        return true;
    }
}
