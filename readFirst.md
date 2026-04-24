# CTMS Modern Migration Project Context

이 문서는 MSSQL에서 MySQL/PHP 8.x로의 마이그레이션 진행 상황을 기록하며, 다음 세션의 인지 로드를 줄이기 위한 핵심 가이드라인입니다.

## 1. 프로젝트 현재 상태 (Current Status)
- **인증 시스템**: `AuthService` 기반 로그인 및 본당 코드(`bcode`) 세션 관리 완료.
- **대시보드**: 월간 교육 일정 및 공지사항 시각화 완료.
- **교사 관리 (Full Migration)**: 
    - 교사 목록(본당교리교사) 조회 및 페이징.
    - 4개 탭 기반의 상세 수정/등록 폼 (인적사항, 소속/휴직, 교육, 수상).
    - 트랜잭션 기반의 다중 테이블(`bd_member_right`, `bd_member_csdate`) 업데이트 로직.
- **통계**: Chart.js를 이용한 부서/직책/운영 현황 시각화 대시보드 구축.
- **데이터 마이그레이션**: `bd_member_education`, `att_member_new`, `css_info_es`, `css_info_mhs` 테이블 마이그레이션 및 `TeacherService` 연동 완료.

## 2. 기술적 특징 (Technical Context)
- **Stack**: PHP 8.x Native, MySQL 8.x, Vanilla CSS (Glassmorphism), Chart.js.
- **Performance**: N+1 문제 해결(`getAwardsBatch`), 주요 조회 컬럼(`login_id`, `bcode`) 인덱스 최적화 완료.
- **Base Path**: 하위 디렉토리(`ctms/public`) 호스팅 지원을 위한 동적 경로 처리(`App::getBasePath()`) 적용.

## 3. 남은 과제 (Next Steps) - **중요**
1.  **교사 신규 등록 로직 완성**: 현재 `edit`은 완성되었으나 `create` 시 `login_id` 생성 규칙 및 초기 삽입 로직 보강 필요.
2.  **교육 일정 상세**: 일정 리스트에서 '상세보기' 클릭 시 팝업 또는 상세 페이지 구현.
3.  **보안 강화**: CSRF 토큰 적용 및 데이터 입출력 Validation 강화.

## 4. 데이터베이스 참고
- 현재 테이블 목록: `bd_member_right`, `academy_state`, `bd_member_csdate`, `tch_tml`, `edu_schedule_new`, `ctms_user_info`, `bd_member_education`, `att_member_new`, `css_info_es`, `css_info_mhs`.
- **상태**: 모든 핵심 테이블이 MySQL로 마이그레이션 되었으며, `TeacherService`의 방어 로직이 제거됨.

---
*마지막 업데이트: 2026-04-25*
*이 문서를 읽고 다음 작업을 이어서 진행하세요.*