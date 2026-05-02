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
            '휴대전화', '이메일', '부서', '직책', '상태', '기본교육', '심화교육', '양성교육', '근속년월', '수상'
        ]);

        $deptMap = [
            'elementary' => '초등', 
            'middle_high' => '중고', 
            'daegun' => '대건', 
            'disabled' => '장애', 
            'integrated' => '통합'
        ];

        $statusMap = [
            'active' => '재직',
            'furlough' => '휴직',
            'retired' => '퇴직'
        ];

        foreach ($teachers as $t) {
            $coreEdu = $t['core_edu_list'] ?? [];
            
            $awards = [];
            foreach ($t['awards'] ?? [] as $a) {
                $awards[] = $a['tml_year'] . '년 ' . $a['tml'];
            }
            $awardsStr = implode(', ', $awards);

            fputcsv($output, [
                $t['id'],
                $t['name'],
                $t['baptismal_name'] ?? '',
                $t['parish_name'] ?? '',
                $t['bcode'] ?? '',
                $t['birth_date'] ?? '',
                $t['feast_day'] ?? '',
                $t['mobile_phone'] ?? '',
                $t['email'] ?? '',
                $deptMap[$t['department'] ?? ''] ?? $t['department'],
                $t['position'] ?? '',
                $statusMap[$t['status'] ?? 'active'] ?? $t['status'],
                in_array('기본교육(구입문과정)', $coreEdu) ? 'O' : 'X',
                in_array('구심화과정', $coreEdu) ? 'O' : 'X',
                in_array('양성교육(구전문화과정)', $coreEdu) ? 'O' : 'X',
                ($t['cs_year'] ? $t['cs_year'] . '년 ' . $t['cs_month'] . '월' : '-'),
                $awardsStr
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
