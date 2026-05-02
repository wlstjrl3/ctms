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

---

## 6. 교사 식별자(ID) 최적화 (Phase 4)

### 6.1 불필요한 `login_id` 문자열 식별자 완전 폐기
- **문제**: 레거시에서는 교사를 `login_id` 문자열(`T00001`, `tmp2022...` 등)로 식별하여 `teachers`를 조회하고, `Migrator.php`에서도 이를 중간 키로 활용했습니다. 이로 인해 불필요한 문자열 컬럼이 생기고 인덱싱 효율과 유지보수성이 떨어졌습니다.
- **해결**: `teachers` 테이블의 `login_id` 컬럼을 완전히 DROP하고, 데이터베이스 기본 키인 `id` (정수)를 직접적인 식별자로 사용하도록 서비스 및 컨트롤러 레이어를 전부 리팩토링했습니다.
- **마이그레이션 방식 변경**: `Migrator.php`가 이전 데이터를 이관할 때 레거시 `login_id`를 임시 배열(`$legacyIdMap`)로 캐싱한 뒤, 새롭게 발급된 `teachers.id`를 기준으로 연관 테이블(근속, 휴직, 수상, 교육)을 INSERT 하도록 쿼리와 로직을 대대적으로 개편했습니다. 이 과정에서 `INSERT ... SELECT JOIN` 방식의 취약점(문자열 이름 매칭 등)이 해결되었습니다.
- **변경**: `edu_schedule_new` 테이블을 확장하여 표준 교육 과정(`course_id`)과 연동.
- **기능**: 일정 등록 시 표준 과정을 선택하면 과목명이 자동 제안되며, 장소/일시/대상/참가비/상태를 관리자 모드에서 수정 가능.
- **UI**: 일정을 카드 형태로 시각화하고 진행 상태(접수중/종료 등) 배지 적용.

---

## 7. 교사 관리 시스템 고도화 및 최적화 (Phase 4)

### 7.1 교사 식별자(ID) 체계 개선
- **문제**: 레거시의 `tmp2022...` 형태의 무작위 문자열 ID는 가독성이 떨어지고 시스템 관리에 비효율적임.
- **해결**: `T00001` 형태의 순차적 일련번호 체계를 도입. `TeacherService::generateCleanId`를 통해 신규 생성 시 자동 채번되며, 기존 데이터는 `fix_ids.php` 스크립트를 통해 일괄 보정함.

### 7.2 데이터 슬림화 및 개인정보 강화
- **변경**: 2026년 기준 실효성이 낮은 **자택전화(home_phone)** 및 **주소(address)** 필드를 전면 제거.
- **적용**: `Migrator.php` 이관 대상에서 제외, `TeacherService` CRUD 로직 삭제, `form.php` UI 필드 제거.

### 7.3 핵심 교육 수료 현황 시스템 구축
- **기능**: 교리교사 핵심 3단계 교육(기본, 심화, 양성)을 별도 섹션으로 분리 관리.
- **UI**: 교사 목록(`list.php`)에서 3단계 수료 현황을 컬러 배지로 시각화하여 N+1 쿼리 없이 즉시 확인 가능하도록 `getEducationBatch` 메서드로 최적화.

### 7.4 실시간 자동 저장 및 UX 개선
- **기능**: 수정 화면에서 필드 변경 시 즉시 저장되는 AJAX 로직을 체크박스(수료 토글)까지 확대 적용.
- **오류 수정**: 업데이트 시 `$teacherId` 변수 누락으로 인한 연관 데이터 저장 실패 버그 수정 및 `jumin_f` -> `birth_date` 필드명 매핑 정상화.

---

## 8. 향후 주의사항
- **마이그레이션 재실행 시**: 반드시 `php scripts/Migrator.php`를 사용하여 전체 프로세스를 초기화 후 재실행할 것. Migrator는 `ORG_INFO` 및 표준 교육 체계를 기준으로 시스템을 재구축합니다.
- **BCODE 금지**: 백엔드와 프론트엔드 어디에서도 3자리 BCODE(`parish_code`)를 사용하지 마십시오. 오직 `ORG_CD` 기반으로 로직을 작성해야 합니다.
- **연락처 매핑**: `bd_member_right` 테이블의 `phone2`가 실제 휴대전화 번호이므로 이관 시 주의가 필요합니다.

---

## 9. 본당 계정 연락처 체계 현대화 (Phase 5)

### 9.1 `phone/fax` -> `org_in_tel/org_out_tel` 변경
- **문제**: 단순 '전화/팩스' 명칭은 교구 행정 시스템의 '내선/국선' 체계와 일치하지 않으며, 데이터 소스인 `ORG_INFO`와 필드명이 달라 혼선 발생.
- **해결**: `users` 테이블의 컬럼명을 `org_in_tel`(내선), `org_out_tel`(국선)로 변경하고 UI 레이블을 업데이트함.

### 9.2 Migrator 동기화 및 오류 수정
- **개선**: `Migrator.php`가 `ORG_INFO`에서 `ORG_OUT_TEL`까지 가져와 `parishes` 및 `users` 테이블에 자동으로 동기화하도록 로직 보완.
- **오류 수정**: 스키마 변경으로 인한 `manualUsers` 배열의 파라미터 개수 불일치(`PDOException`) 해결.
