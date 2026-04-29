<?php
/** @var array $data */
$app = \App\Core\App::getInstance();
$session = $app->session();
$userRole = $session->getRole();
$base = $app->getBasePath();
?>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2rem;">
    
    <!-- 이달의 교육 일정 -->
    <section class="glass-card">
        <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            📅 이달의 교육 일정
        </h2>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                        <th style="padding: 1rem;">일정</th>
                        <th style="padding: 1rem; width: 60px; text-align: center;">대상</th>
                        <th style="padding: 1rem; width: 100px; text-align: center;">상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['schedules'] as $row): ?>
                    <tr class="clickable-row" onclick="showDetail(<?= htmlspecialchars(json_encode($row)) ?>)" style="border-bottom: 1px solid var(--glass-border); cursor: pointer; transition: background 0.2s;">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['edu_subject']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                📍 <?= htmlspecialchars($row['edu_place']) ?> | ⏰ <?= substr($row['edu_date'], 0, 16) ?>
                            </div>
                        </td>
                        <td style="padding: 1rem; text-align: center; font-size: 0.8rem;">
                            <?= $this->teacherService->getGradeName($row['edu_level']) ?>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php 
                                $states = ['진행예정', '접수중', '교육중', '교육종료'];
                                $state = $row['edu_state'] ?? 0;
                                $colors = ['#94a3b8', '#10b981', '#6366f1', '#f43f5e'];
                                $bgColor = ['rgba(148,163,184,0.1)', 'rgba(16,185,129,0.1)', 'rgba(99,102,241,0.1)', 'rgba(244,63,94,0.1)'];
                                $idx = (int)$state;
                                if ($idx < 0 || $idx > 3) $idx = 0;
                            ?>
                            <span class="badge" style="background: <?= $bgColor[$idx] ?>; color: <?= $colors[$idx] ?>; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 10px; font-weight: 700;">
                                <?= $states[$idx] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($data['schedules'])): ?>
                    <tr><td colspan="3" style="padding: 2rem; text-align: center; color: var(--text-muted);">이달의 교육 일정이 없습니다.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if (isset($data['recentLogins'])): ?>
    <!-- 최근 접속 사용자 (Admin) -->
    <section class="glass-card" style="grid-column: 1 / -1;">
        <h2 style="margin-bottom: 1.5rem;">👥 최근 접속 사용자</h2>
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                        <th style="padding: 1rem; width: 60px;">번호</th>
                        <th style="padding: 1rem;">관리본당</th>
                        <th style="padding: 1rem;">사용자</th>
                        <th style="padding: 1rem;">접속시간</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($data['recentLogins'] as $row): ?>
                    <tr style="border-bottom: 1px solid var(--glass-border);">
                        <td style="padding: 0.75rem 1rem; font-family: monospace;"><?= $i++ ?></td>
                        <td style="padding: 0.75rem 1rem;">
                            <?= htmlspecialchars($row['bondang_name'] ?? '전체') ?> (<?= htmlspecialchars($row['bcode']) ?>)
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <?= htmlspecialchars($row['login_name']) ?> (<?= htmlspecialchars($row['login_id']) ?>)
                        </td>
                        <td style="padding: 0.75rem 1rem; color: var(--accent); font-size: 0.8rem;">
                            <?= $row['login_date'] ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

</div>

<!-- Detail Modal -->
<div id="detailModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="glass-card modal-content" style="width: 600px; max-width: 95%; padding: 2.5rem; position: relative;">
        <button class="modal-close" onclick="closeDetailModal()" style="position: absolute; right: 1.5rem; top: 1.5rem; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        <div id="modalBody">
            <p>로딩 중...</p>
        </div>
    </div>
</div>

<style>
    .clickable-row:hover {
        background: rgba(79, 70, 229, 0.05);
    }
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-active { background: rgba(16, 185, 129, 0.15); color: #10b981; }
</style>

<script>
    function showDetail(data) {
        console.log('Detail Data:', data);
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('modalBody');
        modal.style.display = 'flex';

        if (!data) {
            body.innerHTML = `<p style="text-align:center; padding: 2rem;">데이터를 찾지 못했습니다. (객체 전달 방식)</p>`;
            return;
        }

        const courseName = data.standardized_name || data.edu_subject || '알 수 없는 교육';
        const eduDate = data.edu_date ? data.edu_date.substring(0, 16).replace('T', ' ') : '미정';

        body.innerHTML = `
            <div style="margin-bottom: 2rem;">
                <span class="badge badge-active" style="margin-bottom: 1rem; display: inline-block;">상세 정보</span>
                <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem;">${courseName}</h2>
                ${data.standardized_name && data.standardized_name !== data.edu_subject ? `<p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">[공식 명칭] ${data.edu_subject}</p>` : ''}
                <p style="color: var(--text-muted); font-size: 0.85rem;">${data.edu_year || ''}년 교육 과정</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="info-group">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">장소</label>
                    <div style="font-weight: 600;">📍 ${data.edu_place || '미정'}</div>
                </div>
                <div class="info-group">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">일시</label>
                    <div style="font-weight: 600;">⏰ ${eduDate}</div>
                </div>
                <div class="info-group">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">참가비</label>
                    <div style="font-weight: 600; color: var(--accent);">₩ ${data.edu_money ? parseInt(data.edu_money).toLocaleString() : '0'}</div>
                </div>
                <div class="info-group">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">정원</label>
                    <div style="font-weight: 600;">👥 ${data.edu_maxp || '무제한'} 명</div>
                </div>
            </div>

            <div style="border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem;">교육 내용</label>
                <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; font-size: 0.9rem; line-height: 1.6; max-height: 250px; overflow-y: auto; white-space: pre-wrap;">
                    ${data.edu_content || '내용이 없습니다.'}
                </div>
            </div>

            <div style="margin-top: 2.5rem; text-align: right;">
                <button class="btn btn-primary" onclick="closeDetailModal()">닫기</button>
            </div>
        `;
    }

    function closeDetailModal() {
        document.getElementById('detailModal').style.display = 'none';
    }
</script>
