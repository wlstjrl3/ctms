<div class="manual-container animate-fade-in">
    <div class="card glass" style="padding: 2.5rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 48px; height: 48px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                📖
            </div>
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; margin: 0;">시스템 이용 매뉴얼 (CTMS)</h2>
                <p style="color: var(--text-muted); margin: 0.25rem 0 0 0;">CTMS는 교구와 본당 교사를 잇는 통합 관리 시스템입니다.</p>
            </div>
        </div>

        <!-- 1. 권한 체계 섹션 -->
        <section style="margin-top: 3rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; border-left: 4px solid var(--primary); padding-left: 0.75rem; margin-bottom: 1.5rem;">1. 권한 체계 및 메뉴 구성</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--glass-border);">
                    <thead>
                        <tr style="background: rgba(79, 70, 229, 0.05);">
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);">구분</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);">본당 (bondang)</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);">대리구 (diocese)</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--glass-border);">교구 (casuwon)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600; border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);">관리 범위</td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);">소속 본당 데이터만 관리</td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--glass-border); border-right: 1px solid var(--glass-border);">교구 내 모든 데이터 조회/수정</td>
                            <td style="padding: 1rem; border-bottom: 1px solid var(--glass-border);">시스템 전체 및 코드 설정</td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; font-weight: 600; border-right: 1px solid var(--glass-border);">주요 특징</td>
                            <td style="padding: 1rem; border-right: 1px solid var(--glass-border);">교사 등록 및 정보 현행화</td>
                            <td style="padding: 1rem; border-right: 1px solid var(--glass-border);">본당별 현황 모니터링</td>
                            <td style="padding: 1rem;">계정 발급 및 조직 체계 관리</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 2. 주요 업무 프로세스 -->
        <section style="margin-top: 4rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; border-left: 4px solid var(--success); padding-left: 0.75rem; margin-bottom: 1.5rem;">2. 주요 업무 처리 흐름</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div class="glass" style="padding: 1.5rem; border-radius: 16px;">
                    <div style="color: var(--primary); font-weight: 800; font-size: 0.9rem; margin-bottom: 0.5rem;">STEP 01</div>
                    <h4 style="margin: 0 0 0.75rem 0;">신규 교사 등록</h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">본당 관리자는 인적사항, 부서, 서약일을 입력하여 교사를 등록합니다.</p>
                </div>
                <div class="glass" style="padding: 1.5rem; border-radius: 16px;">
                    <div style="color: var(--success); font-weight: 800; font-size: 0.9rem; margin-bottom: 0.5rem;">STEP 02</div>
                    <h4 style="margin: 0 0 0.75rem 0;">활동 및 이력 업데이트</h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">상훈, 교육 이수, 휴직/복직 정보를 주기적으로 업데이트하여 이력을 관리합니다.</p>
                </div>
                <div class="glass" style="padding: 1.5rem; border-radius: 16px;">
                    <div style="color: var(--accent); font-weight: 800; font-size: 0.9rem; margin-bottom: 0.5rem;">STEP 03</div>
                    <h4 style="margin: 0 0 0.75rem 0;">퇴임 및 현행화</h4>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">퇴임 시 상태값을 변경하여 현황 데이터의 정확성을 유지합니다.</p>
                </div>
            </div>
        </section>

        <!-- 3. 페이지별 기능 안내 -->
        <section style="margin-top: 4rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; border-left: 4px solid var(--accent); padding-left: 0.75rem; margin-bottom: 1.5rem;">3. 주요 기능 상세 가이드</h3>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <details class="manual-item glass">
                    <summary style="padding: 1.25rem; cursor: pointer; font-weight: 600; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                        📊 대시보드 (Dashboard)
                        <span style="font-size: 0.75rem; color: var(--text-muted);">클릭하여 열기</span>
                    </summary>
                    <div style="padding: 0 1.25rem 1.25rem 1.25rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.7;">
                        시스템 접속 시 첫 화면으로, 현재 활동 중인 전체 교사 수와 부서별 분포 현황을 그래프와 수치로 제공합니다. 실시간 통계를 통해 본당 또는 교구의 운영 상황을 한눈에 파악할 수 있는 CTMS의 핵심 대시보드입니다.
                    </div>
                </details>

                <details class="manual-item glass">
                    <summary style="padding: 1.25rem; cursor: pointer; font-weight: 600; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                        👥 본당교리교사 관리 (Teacher Management)
                        <span style="font-size: 0.75rem; color: var(--text-muted);">클릭하여 열기</span>
                    </summary>
                    <div style="padding: 0 1.25rem 1.25rem 1.25rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.7;">
                        - 성명, 세례명, 부서 등 다양한 필터로 교사를 검색할 수 있습니다.<br>
                        - 근속 기간은 등록된 서약일을 기준으로 **실시간 자동 계산**됩니다.<br>
                        - 교사 상세 페이지에서 상훈, 교육 이수, 활동 이력 등을 통합 관리합니다.
                    </div>
                </details>

                <details class="manual-item glass">
                    <summary style="padding: 1.25rem; cursor: pointer; font-weight: 600; list-style: none; display: flex; justify-content: space-between; align-items: center;">
                        🔑 계정 및 코드 관리 (Admin Only)
                        <span style="font-size: 0.75rem; color: var(--text-muted);">클릭하여 열기</span>
                    </summary>
                    <div style="padding: 0 1.25rem 1.25rem 1.25rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.7;">
                        - **본당 계정 관리**: 본당별 시스템 접속 계정을 생성하고 권한을 관리합니다.<br>
                        - **본당 코드 관리**: 대리구, 지구, 본당으로 이어지는 조직 체계를 등록하고 수정합니다.
                    </div>
                </details>
            </div>
        </section>

        <!-- 4. 운영 지원 -->
        <section style="margin-top: 4rem; padding: 2rem; background: rgba(79, 70, 229, 0.03); border-radius: 20px; border: 1px dashed var(--primary);">
            <h4 style="margin-top: 0; color: var(--primary);">💡 운영 팁 및 문의</h4>
            <ul style="padding-left: 1.25rem; font-size: 0.9rem; color: var(--text-muted); line-height: 1.8; margin-bottom: 0;">
                <li>목록에서 데이터 조회가 느리다면 상단 검색 필터를 활용해 범위를 좁혀보세요.</li>
                <li>정렬(이름순, 등록순 등) 기능을 사용하면 원하는 데이터를 더 빨리 찾을 수 있습니다.</li>
                <li>로그인이나 권한 관련 문제는 교구 담당자에게 문의해 주시기 바랍니다.</li>
            </ul>
        </section>
    </div>
</div>

<style>
    .manual-item summary::-webkit-details-marker { display: none; }
    .manual-item[open] summary { color: var(--primary); }
    .manual-item { transition: var(--transition); border-radius: 12px; margin-bottom: 0.5rem; border: 1px solid transparent; }
    .manual-item:hover { border-color: var(--glass-border); }
    
    table th { font-weight: 700; color: var(--text-main); }
    table td { color: var(--text-muted); font-size: 0.9rem; }
</style>
