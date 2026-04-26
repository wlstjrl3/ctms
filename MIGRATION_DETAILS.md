# Migration Troubleshooting & Details Log (2026-04-26)

이 문서는 레거시 CTMS에서 차세대 CTMS로 데이터를 이관하는 과정에서 발생한 기술적 문제들과 그 해결책을 기록합니다.

## 1. 주요 해결 이슈

### 1.1 데이터 중복 폭발 (Education Records)
- **문제**: `education_records` 마이그레이션 시 동일한 교육명이 수천 번 중복 생성되어 데이터가 기하급수적으로 늘어남.
- **원인**: `education_courses` 테이블에 교육명(`course_name`) 유니크 제약 조건이 없어, 매 레코드마다 새로운 코스 ID가 생성됨.
- **해결**:
    - `education_courses.course_name`에 `UNIQUE` 인덱스 추가.
    - `REPLACE INTO` 또는 `INSERT IGNORE` 로직으로 전환하여 중복 방지.

### 1.2 콜레이션 충돌 (Collation Mismatch)
- **문제**: `parishes` 테이블과 `vicariates` 테이블을 `JOIN` 할 때 `Illegal mix of collations` 오류 발생.
- **원인**: 레거시에서 생성된 테이블과 신규 테이블 간의 기본 콜레이션(`utf8mb4_general_ci` vs `utf8mb4_unicode_ci`)이 다름.
- **해결**: `JOIN` 조건에 `COLLATE utf8mb4_unicode_ci`를 명시하여 강제로 일치시킴.

### 1.3 본당 계정 매핑 실패 (User-Parish Linkage)
- **문제**: 본당 계정(`asnv01` 등)의 소속 본당 정보가 `-`로 표시됨.
- **원인**: `users.login_id`와 `parishes.parish_code`가 직접 일치하지 않음 (`asnv01` != `V01`).
- **분석**: 레거시 `ctms_user_info`의 `ctms_ucode`가 실제 본당 코드(`BCODE`)와 일치함을 발견.
- **해결**: 마이그레이션 루프 내에서 `ctms_ucode`를 참조하여 `parishes.id`를 찾아 `users.parish_id`에 저장하도록 로직 보완.

### 1.4 조직 체계(대리구/지구) 부재
- **문제**: 본당 관리 페이지에서 대리구/지구 필터가 작동하지 않고 목록이 비어 보임.
- **해결**: 텍스트로만 존재하던 본당 정보를 바탕으로 `vicariates`와 `districts` 테이블을 자동 생성하고 본당과 외래 키로 연결하는 로직 추가.

### 1.5 세션 키 불일치 및 권한 오류 (Session Key & Permission Issue)
- **문제**: 로그인 후 대시보드는 접근 가능하나 '본당 계정 관리' 및 '본당 코드 관리' 페이지 접근 시 권한 오류 발생.
- **원인**: `AuthService`에서는 `ctms_admin` 키로 역할을 저장했으나, `Session` 클래스의 `hasPermission`은 `role` 키를 참조하여 권한 체크가 항상 실패함.
- **해결**: 세션 키를 `role`로 통일하고, `Session::hasPermission` 로직을 보완하여 정확한 권한 체크가 가능하도록 수정.

### 1.6 대시보드 치명적 오류 (Dashboard Fatal Error)
- **문제**: 대시보드 진입 시 `getGradeName()` 메서드 부재로 인한 치명적 오류(Fatal Error) 발생.
- **해결**: `TeacherService` 클래스에 `getGradeName` 메서드를 추가하여 학급(초등/중고등 등) 명칭이 정상 출력되도록 수정.

### 1.7 관리자 계정 교사 목록 필터링 오류 (Admin Teacher List Filter)
- **문제**: 관리자(`casuwon`) 계정으로 로그인했음에도 특정 본당 코드로 교사 목록이 필터링되어 1명만 표시되는 현상.
- **해결**: `TeacherController`에서 계정 역할이 `casuwon` 또는 `diocese`인 경우 본당 코드(`bcode`) 필터를 무시하고 전체 교사를 조회하도록 검색 로직 개선.

## 2. 최종 데이터 상태 요약
- **본당(Parishes)**: 226개 (모두 지구 ID와 연결 완료)
- **대리구(Vicariates)**: 2개 (제1대리구, 제2대리구)
- **지구(Districts)**: 23개
- **계정(Users)**: 235개 (본당 및 관리자 계정 매핑 완료)
- **교사(Teachers)**: 4,703개 (근속, 수상, 교육 이력 이관 완료)

## 3. 향후 주의사항
- **세션 갱신**: 권한 로직 수정 후에는 반드시 로그아웃 후 재로그인하여 새로운 세션 데이터를 반영해야 함.
- **마이그레이션 재실행 시**: 반드시 `php scripts/Migrator.php`를 사용하여 전체 프로세스를 초기화 후 재실행할 것.
- **비밀번호**: 관리자(`jsyang`)의 비밀번호는 `js0136`임.
- **검색 로직**: 본당 검색은 이제 `ParishController::ajaxSearch`를 통해 모달 창에서 대리구/지구/본당명으로 통합 검색 가능.
