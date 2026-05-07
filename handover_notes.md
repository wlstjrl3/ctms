# 본당 관리 시스템 데이터 복구 및 동기화 핸드오버 노트

## 1. 현재 상황 (Current Status)
*   **증상**: 본당 목록 페이지(`parish_list`)에서 본당 이름은 뜨지만, **대리구명과 지구명이 비어 있음**. 상단 필터 드롭다운도 모두 비어 있음.
*   **데이터 상태 (CLI 진단 결과)**:
    *   `ORG_INFO`: 대리구(1306) 2개, 지구(1309) 27개, 본당(1311) 222개 존재 확인.
    *   `parishes`: 222개 레코드 존재 확인.
    *   **문제점**: `ORG_INFO` 내의 본당 레코드들이 삭제된 이전 지구 코드를 `UPPR_ORG_CD`로 참조하고 있어 `LEFT JOIN` 시 명칭이 누락됨.
*   **시도한 조치**:
    *   `EmergencyFix.php`: 중복 지구/본당 제거 (성공, 137개 -> 27개 지구).
    *   `ForceHeal.php`: 이름 기반 연결 복구 시도 (실패, **Fixed 0 links** 출력).

## 2. 핵심 원인 분석 (Root Cause)
1.  **이름 매칭 실패**: `parishes` 테이블의 `district_name` 컬럼 값과 `ORG_INFO`의 `ORG_NM` 값이 서로 달라(예: "권선" vs "권선지구") 자동 복구 로직이 연결을 맺어주지 못하고 있음.
2.  **웹 환경 이슈**: 터미널(CLI)에서는 데이터가 조회되나, 웹(PHP-FPM) 환경에서 드롭다운이 비어 나오는 현상(PDO 설정이나 인코딩 이슈 의심).

## 3. 다음 에이전트 작업 가이드 (Action Plan)
1.  **이름 매칭 정밀 확인**: 
    *   `parishes` 테이블의 `district_name`과 `ORG_INFO`의 `ORG_NM` 값을 직접 `SELECT`하여 대조해 볼 것.
    *   공백, 인코딩, 혹은 "지구" 접미사 유무를 확인하여 `ScraperService::preSyncCleanup`의 매칭 로직을 수정할 것.
2.  **연결 강제 복구**: 
    *   이름 매칭이 안 된다면 `parishes.district_code`를 활용하거나, 스크래퍼가 홈페이지에서 정보를 새로 긁어올 때 `ORG_INFO`의 상하 관계를 무조건 강제 갱신(OVERWRITE)하도록 할 것.
3.  **웹 드롭다운 빈 칸 해결**:
    *   `ParishService::getDioceses`와 `getDistricts` 쿼리가 웹 환경에서 빈 배열을 반환하는 이유를 찾을 것 (대소문자 구분, `USE_YN` 필터링 등).

## 4. 관련 주요 파일
*   `src/Service/ParishService.php`: 데이터 조회 쿼리 (`getDioceses`, `getDistricts`).
*   `src/Service/ScraperService.php`: 자가 치유 및 동기화 로직 (`preSyncCleanup`, `updateDatabase`).
*   `scripts/ForceHeal.php`: 현재까지의 복구 로직이 집약된 진단 스크립트.

> **Note**: `ForceHeal.php`에서 `Fixed 0 links`가 나온 원인을 해결하는 것이 전체 복구의 핵심입니다.
