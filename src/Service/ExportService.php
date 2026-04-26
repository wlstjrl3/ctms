<?php
declare(strict_types=1);

namespace App\Service;

class ExportService
{
    /**
     * Convert teacher array to CSV string with UTF-8 BOM for Excel
     */
    public function exportTeachersToCsv(array $teachers): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        fputcsv($output, [
            'ID', '이름', '세례명', '본당', '본당코드', '생년월일', '영명축일', 
            '휴대전화', '이메일', '주소', '부서', '직책', '비고', '근속년월'
        ]);

        $deptMap = [
            'elementary' => '초등', 
            'middle_high' => '중고', 
            'daegun' => '대건', 
            'disabled' => '장애', 
            'integrated' => '통합'
        ];

        foreach ($teachers as $t) {
            fputcsv($output, [
                $t['login_id'],
                $t['name'],
                $t['baptismal_name'] ?? '',
                $t['parish_name'] ?? '',
                $t['bcode'] ?? '',
                $t['birth_date'] ?? '',
                $t['feast_day'] ?? '',
                $t['mobile_phone'] ?? '',
                $t['email'] ?? '',
                ($t['address_basic'] ?? '') . ' ' . ($t['address_detail'] ?? ''),
                $deptMap[$t['department'] ?? ''] ?? $t['department'],
                $t['position'] ?? '',
                $t['current_grade'] ?? '',
                ($t['cs_year'] ? $t['cs_year'] . '년 ' . $t['cs_month'] . '월' : '-')
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
