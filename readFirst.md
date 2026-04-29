# CTMS Modernization Project - Handover & Status

이 문서는 다음 에이전트가 현재 진행 상황을 즉시 파악하고 작업을 이어갈 수 있도록 작성된 종합 상태 보고서입니다.

## 1. 최신 아키텍처 변경사항: ORG_INFO 단일 진실 공급원 (Single Source of Truth) 통합

가장 최근 핵심 작업으로 레거시 3자리 본당코드(`BCODE`, `A01`, `C04` 등)를 완전히 폐기하고 교구 표준 조직 코드 시스템인 **`ORG_INFO`** 테이블(ORG_CD)로 전면 마이그레이션 및 리팩토링을 완료했습니다.

### [ORG_INFO 기반 시스템 구조]
*   **ORG_CD 체계**: 
    *   `1311xxxx`: 본당
    *   `1309xxxx`: 지구
    *   `1306xxxx`: 대리구
    *   `1301xxxx`: 성지 (참고용)
*   **계층 구조 (Hierarchy)**: `ORG_INFO`의 `UPPR_ORG_CD` 필드를 통해 본당 -> 지구 -> 대리구의 상하 관계를 맺습니다.
*   **통합 완료 사항**:
    *   `ParishService.php`: 조회/생성/수정/삭제를 모두 `ORG_INFO` 기준으로 작동하도록 완전히 재작성했습니다.
    *   `TeacherService.php` & `UserService.php`: 교사와 관리자 계정이 소속된 본당을 확인할 때 레거시 `parish_code`가 아닌 `org_cd`를 조회하도록 수정했습니다.
    *   `AuthService.php`: 로그인 시 세션에 `bcode` 대신 `org_cd`를 저장하고 사용합니다.
    *   **UI/UX**: 검색 필터, 테이블 컬럼, 생성/수정 폼의 모든 `parish_code(BCODE)` 노출 및 입력란을 `ORG_CD`로 변경했습니다. json_encode시 한글 깨짐 문제도 `JSON_UNESCAPED_UNICODE`로 해결했습니다.
    *   `Migrator.php`: 마이그레이션 스크립트가 구형 `search_bondang` 테이블을 참조하지 않고, `ORG_INFO` 테이블을 기준으로 222개 본당과 지구/대리구 계층을 구성하여 DB를 빌드하도록 개편했습니다. (`parishes` 테이블에 `org_cd` 컬럼 추가)

### [Advanced UI/UX Framework]
*   **실시간 AJAX 필터링 도입**: 모든 주요 목록(교사, 계정, 본당)에 버튼 없는 실시간 검색 기능을 구현했습니다. (300ms Debounce)
*   **반응형 사이드바 & 햄버거 메뉴**: 모바일/태블릿 토글 메뉴 및 고대비 토스트 알림 시스템 구축.
*   **사용자 편의성(UX) 강화**: 테이블 행 전체 클릭으로 수정 창 이동, 목록 외부로 삭제 버튼 숨김 등.

### [Teacher & Photo Management]
*   **사진 업로드 시스템**: JPG/PNG 프리뷰 및 업로드 로직.
*   **데이터 품질 보정**: 110세 이상 고령자 Y2K 버그 보정, 영명축일 형식(MM/DD) 강제 변환 등 마이그레이터에 반영.

### [Education & Schedule Management]
*   **표준 교육 과정 통합**: 파편화된 교육 데이터를 `education_courses` 테이블로 정규화하고, `course_id` 기반의 관계형 구조로 전환했습니다.
*   **교육 일정 관리**: 단순 조회만 가능했던 일정을 추가/수정/삭제할 수 있는 관리 기능을 구현했습니다. (`edu_schedule_new` 테이블 스키마 현대화 완료)
*   **검색 모달 UX**: 교사 정보 수정 및 일정 등록 시, 카테고리별 필터링이 가능한 통합 교육 검색 모달을 도입하여 데이터 입력의 정확성을 높였습니다.

---

## 2. 레거시(ctmsOLD) 대비 마이그레이션 현황

| 기능 영역 | 구현 상태 | 관련 파일 | 비고 |
| :--- | :---: | :--- | :--- |
| **조직 체계 (ORG_INFO)** | ✅ 완료 | `ParishService`, `Migrator.php` | 레거시 A01 형태의 bcode 전면 폐기, 1311로 시작하는 ORG_CD로 통합 |
| **실시간 교사 검색** | ✅ 완료 | `TeacherController::ajaxList` | 다중 필터(연령대, 근속 등) 지원, `org_cd` 기반 조회 |
| **사진 관리** | ✅ 완료 | `TeacherService::updatePhoto` | 실시간 프리뷰 및 서버 저장 |
| **본당 계정 관리** | ✅ 완료 | `UserService` | `org_cd` 매핑 및 `bondang` 권한 필터링 |
| **교육 과정/일정 관리** | ✅ 완료 | `EducationService`, `EduScheduleService` | 표준 과정 기반 일정 연동 및 검색 모달 도입 |
| **미사 시간 설정** | ⏳ 대기 | `bondang/css_mng_time.asp` | `css_mng_info` 테이블 연동 필요 |
| **학생/교동 관리** | ⏳ 대기 | `bondang/css_att_write_*.asp` | Phase 2 핵심 과제 |

---

## 3. 마이그레이션 및 데이터 보정 가이드 (Critical)

실서버 배포 및 DB 초기화 시 반드시 `scripts/Migrator.php`를 사용하십시오.
명령어: `php scripts/Migrator.php`

1.  **조직 데이터 무결성 (ORG_INFO)**: 
    *   Migrator는 `ORG_INFO`의 데이터를 기반으로 시스템의 `vicariates`, `districts`, `parishes` 테이블을 갱신합니다.
2.  **Y2K 나이 & 날짜 보정**: 
    *   2000년대생 표기 오류 및 `8자리 숫자(YYYYMMDD)` 형식을 `MM/DD` 형식으로 자동 정제.

---

## 4. 향후 중점 과제 (Next Steps)
1.  **본당 검색 모달 & 폼 잔여물 확인**:
    *   교사 수정 등에서 소속 본당을 교체하는 팝업/모달 로직이 ORG_CD 기반으로 완벽히 작동하는지 실테스트 필요.
2.  **학생 관리(CSS) 모듈 착수 (Phase 2)**:
    *   초등부/중고등부 학생 정보를 통합 관리하는 `StudentService` 및 `StudentController` 구현.
    *   학생 출석 및 성사 기록(레거시 `css_bs_add.asp`) 마이그레이션 기획.
3.  **통계 대시보드 시각화**:
    *   `TeacherService`의 연령별/근속별 통계 데이터를 Chart.js로 시각화.

---

## 5. 개발자 주의 사항
*   **절대 주의 - BCODE 사용 금지**: 기존 시스템의 3자리 BCODE(`parish_code`)는 아키텍처에서 삭제되었습니다. 새로운 기능 구현 시 반드시 `org_cd`를 사용하십시오.
*   **슈퍼관리자 계정**: `jsyang` / `js0136` (casuwon 권한)
*   **AJAX 데이터 로드**: 새로운 목록 페이지 생성 시 `fetchData(page)` 함수와 `list_rows.php` 패턴을 따르십시오.
*   **파일 업로드**: `public/uploads/photos/` 디렉토리의 쓰기 권한을 확인하십시오.