# Migration Troubleshooting & Details Log

이 문서는 레거시 CTMS에서 차세대 CTMS로 데이터를 이관하는 과정에서 발생한 기술적 문제들과 그 해결책, 그리고 구조적 변경사항을 기록합니다. 다음 에이전트가 데이터베이스 구조 변경 이력을 추적할 때 유용합니다.

## 1. 최근 구조 개편: ORG_INFO 단일 진실 공급원 (Single Source of Truth) 통합

가장 핵심적인 데이터베이스 아키텍처 변경으로, 레거시 시스템의 3자리 영문/숫자 혼합 본당코드(BCODE, A01 등)를 폐기하고, 교구 공통 `ORG_INFO` 테이블의 `ORG_CD`를 도입했습니다.

### 1.1 레거시 BCODE의 문제점과 ORG_CD로의 전환
- **문제**: 기존 CTMS는 `search_bondang`의 BCODE('A01', 'C04' 등)를 외래 키처럼 사용하여 `users`와 `teachers`를 연결했습니다. 그러나 다른 교구 시스템들은 숫자형 계층 코드인 `ORG_CD`(1311~, 1309~, 1306~)를 표준으로 사용하고 있어 데이터 연동이 불가능했습니다.
- **해결**: `parishes` 테이블에 `org_cd` (INT) 컬럼을 추가하고, BCODE(`parish_code`)의 사용을 완전히 중단했습니다.
- **적용 범위**:
    - **DB**: `users`, `teachers` 조회 시 `parishes.org_cd`를 기준으로 조인 및 필터링.
    - **서비스**: `TeacherService`, `UserService`, `ParishService`, `AuthService`의 쿼리와 파라미터를 `$bcode`에서 `$orgCd`로 전면 교체.
    - **UI**: 뷰 파일(`list.php`, `form.php`)의 모든 BCODE 필드 및 입력란을 `ORG_CD`로 변경.
    - **마이그레이션**: `scripts/Migrator.php`가 더 이상 `search_bondang`을 읽지 않고 `ORG_INFO`를 읽어 계층(대리구-지구-본당) 구조를 구축.

---

## 2. 이전 주요 해결 이슈 (Phase 1)

### 2.1 데이터 중복 폭발 (Education Records)
- **문제**: `education_records` 마이그레이션 시 동일한 교육명이 수천 번 중복 생성됨.
- **해결**: `education_courses.course_name`에 `UNIQUE` 인덱스 추가 및 `INSERT IGNORE` 로직 전환.

### 2.2 콜레이션 충돌 (Collation Mismatch)
- **문제**: `parishes` 테이블과 `vicariates` 테이블을 `JOIN` 할 때 `Illegal mix of collations` 오류 발생.
- **해결**: `JOIN` 조건에 `COLLATE utf8mb4_unicode_ci`를 명시.

### 2.3 본당 계정 매핑 실패 (User-Parish Linkage)
- **문제**: 본당 계정(`asnv01` 등)의 소속 본당 정보 매핑 실패.
- **해결**: 레거시 `ctms_user_info`의 `ctms_ucode`를 참조하여 `parishes.id`를 찾아 `users.parish_id`에 저장.

### 2.4 세션 키 불일치 및 권한 오류
- **문제**: 로그인 후 '본당 계정 관리' 접근 시 권한 오류 발생.
- **해결**: 세션 키를 `role`로 통일하고, `Session::hasPermission` 로직 보완. 현재는 세션에 `bcode` 대신 `org_cd`를 저장하도록 추가 업데이트됨.

### 2.5 관리자 계정 교사 목록 필터링 오류
- **문제**: 관리자(`casuwon`) 계정 로그인 시 전체 교사 조회가 안 되는 현상.
- **해결**: `TeacherController`에서 계정 역할이 `casuwon`/`diocese`인 경우 `org_cd` 필터를 무시하고 전체 교사를 조회하도록 개선.

---

## 3. 최종 데이터 상태 요약 (Migrator 실행 기준)
- **본당(Parishes)**: 222개 (ORG_INFO의 1311xxxx 코드 기반, 지구 ID와 연결 완료)
- **대리구(Vicariates)**: 2개 (제1대리구 13061001, 제2대리구 13061002)
- **지구(Districts)**: 25개 (1309xxxx 코드 기반)
- **계정(Users)**: 약 235개 (본당 매핑 완료)
- **교사(Teachers)**: 약 4,703개 (근속, 수상, 교육 이력 이관 완료)

---

## 5. 교육 데이터 정규화 및 일정 관리 (Phase 3)

### 5.1 교육 과정 파편화 해결
- **문제**: 자유 텍스트 입력 방식으로 인해 'POP초급', 'POP손글씨', '22-10 POP' 등 동일 교육이 수십 개로 갈라져 관리 불가능.
- **해결**: `Migrator.php`에 정규화 엔진을 탑재하여 유사 명칭을 하나로 병합하고, 모든 교사 이력을 `course_id`로 연결함.
- **카테고리화**: 영성, 교리, 기능, 리더십 등 6개 표준 카테고리로 자동 분류.

### 5.2 교육 일정 시스템 현대화
- **변경**: `edu_schedule_new` 테이블을 확장하여 표준 교육 과정(`course_id`)과 연동.
- **기능**: 일정 등록 시 표준 과정을 선택하면 과목명이 자동 제안되며, 장소/일시/대상/참가비/상태를 관리자 모드에서 수정 가능.
- **UI**: 일정을 카드 형태로 시각화하고 진행 상태(접수중/종료 등) 배지 적용.

---

## 6. 향후 주의사항
- **마이그레이션 재실행 시**: 반드시 `php scripts/Migrator.php`를 사용하여 전체 프로세스를 초기화 후 재실행할 것. Migrator는 `ORG_INFO` 및 표준 교육 체계를 기준으로 시스템을 재구축합니다.
- **BCODE 금지**: 백엔드와 프론트엔드 어디에서도 3자리 BCODE(`parish_code`)를 사용하지 마십시오. 오직 `ORG_CD` 기반으로 로직을 작성해야 합니다.
