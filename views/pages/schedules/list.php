<?php
/** @var array $schedules */
/** @var array $availableYears */
/** @var int $year */
/** @var string $state */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<div class="top-bar">
    <h1 id="page-title">교육 일정 안내</h1>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <form action="<?= $base ?>index.php" method="GET" style="display: flex; gap: 0.5rem;">
            <input type="hidden" name="page" value="edu_schedule">
            <select name="year" onchange="this.form.submit()" style="background: var(--glass-bg); color: var(--text-main); border: 1px solid var(--glass-border); padding: 0.5rem; border-radius: 8px;">
                <?php foreach ($availableYears as $y): ?>
                    <option value="<?= $y['edu_year'] ?>" <?= $y['edu_year'] == $year ? 'selected' : '' ?>><?= $y['edu_year'] ?>년</option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="schedule-list" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
    <?php foreach ($schedules as $s): 
        $dateObj = new DateTime($s['edu_date']);
        $month = $dateObj->format('M');
        $day = $dateObj->format('d');
        
        // State Badge Logic
        $stateClass = 'badge-upcoming';
        $stateText = '진행예정';
        if ($s['edu_state'] === '1') { $stateClass = 'badge-active'; $stateText = '접수중'; }
        if ($s['edu_state'] === '2') { $stateClass = 'badge-ongoing'; $stateText = '교육중'; }
        if ($s['edu_state'] === '3') { $stateClass = 'badge-closed'; $stateText = '교육종료'; }
    ?>
    <div class="glass-card schedule-item" style="display: grid; grid-template-columns: 100px 1fr 150px; align-items: center; padding: 1.5rem; transition: var(--transition);">
        <div class="date-box" style="text-align: center; border-right: 1px solid var(--glass-border); padding-right: 1rem;">
            <div style="font-size: 0.75rem; color: var(--primary); font-weight: 700; text-transform: uppercase;"><?= $month ?></div>
            <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);"><?= $day ?></div>
        </div>
        
        <div style="padding: 0 1.5rem;">
            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                <span class="badge <?= $stateClass ?>"><?= $stateText ?></span>
                <span style="color: var(--text-muted); font-size: 0.8rem;"><?= $s['edu_year'] ?>년 <?= $s['edu_level'] == '1' ? '초등부' : ($s['edu_level'] == '2' ? '중고등부' : '통합') ?></span>
            </div>
            <h3 style="margin-bottom: 0.25rem;"><?= htmlspecialchars($s['edu_subject']) ?></h3>
            <p style="color: var(--text-muted); font-size: 0.875rem;">📍 <?= htmlspecialchars($s['edu_place']) ?> | ⏰ <?= substr($s['edu_date'], 0, 16) ?></p>
        </div>
        
        <div style="text-align: right;">
            <button class="btn btn-primary" style="padding: 0.6rem 1.2rem; font-size: 0.875rem;" onclick="showDetail(<?= $s['idx_num'] ?>)">상세보기</button>
        </div>
    </div>
    <?php endforeach; if(empty($schedules)): ?>
    <div class="glass-card" style="padding: 3rem; text-align: center; color: var(--text-muted);">
        선택한 연도의 교육 일정이 없습니다.
    </div>
    <?php endif; ?>
</div>

<style>
    .schedule-item:hover {
        transform: translateX(10px);
        background: rgba(255, 255, 255, 0.05);
    }
    .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-weight: 700;
    }
    .badge-upcoming { background: rgba(148, 163, 184, 0.2); color: #94a3b8; }
    .badge-active { background: rgba(16, 185, 129, 0.2); color: #10b981; }
    .badge-ongoing { background: rgba(99, 102, 241, 0.2); color: #6366f1; }
    .badge-closed { background: rgba(244, 63, 94, 0.2); color: #f43f5e; }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 1.5rem;
        width: 90%;
        max-width: 600px;
        padding: 2.5rem;
        box-shadow: var(--shadow-lg);
        position: relative;
    }
    .modal-close {
        position: absolute;
        top: 1.5rem; right: 1.5rem;
        background: none; border: none;
        color: var(--text-muted);
        font-size: 1.5rem; cursor: pointer;
    }
</style>

<!-- Detail Modal -->
<div id="detailModal" class="modal">
    <div class="modal-content glass-card">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <div id="modalBody">
            <!-- Content loaded via AJAX -->
            <p>로딩 중...</p>
        </div>
    </div>
</div>

<script>
    async function showDetail(idx) {
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('modalBody');
        modal.style.display = 'flex';
        body.innerHTML = '<p style="text-align:center; padding: 2rem;">로딩 중...</p>';

        try {
            const response = await fetch(`<?= $base ?>index.php?action=schedule_detail&idx=${idx}`);
            const data = await response.json();

            if (data.error) {
                body.innerHTML = `<p style="color:var(--danger);">${data.error}</p>`;
                return;
            }

            body.innerHTML = `
                <div style="margin-bottom: 2rem;">
                    <span class="badge badge-active" style="margin-bottom: 1rem; display: inline-block;">상세 정보</span>
                    <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem;">${data.edu_subject}</h2>
                    <p style="color: var(--text-muted);">${data.edu_year}년 교육 과정</p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div class="info-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">장소</label>
                        <div style="font-weight: 600;">📍 ${data.edu_where}</div>
                    </div>
                    <div class="info-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">일시</label>
                        <div style="font-weight: 600;">⏰ ${data.edu_date.substring(0, 16)}</div>
                    </div>
                    <div class="info-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">참가비</label>
                        <div style="font-weight: 600; color: var(--accent);">₩ ${data.edu_money ? parseInt(data.edu_money).toLocaleString() : '0'}</div>
                    </div>
                    <div class="info-group">
                        <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.25rem;">정원</label>
                        <div style="font-weight: 600;">👥 ${data.edu_maxp || '-'} 명</div>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                    <label style="display: block; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.75rem;">교육 내용</label>
                    <div style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; font-size: 0.9rem; line-height: 1.6; max-height: 200px; overflow-y: auto;">
                        ${data.edu_content || '내용이 없습니다.'}
                    </div>
                </div>

                <div style="margin-top: 2.5rem; text-align: right;">
                    <button class="btn btn-primary" onclick="closeModal()">닫기</button>
                </div>
            `;
        } catch (err) {
            body.innerHTML = `<p style="color:var(--danger);">데이터를 불러오는 중 오류가 발생했습니다.</p>`;
        }
    }

    function closeModal() {
        document.getElementById('detailModal').style.display = 'none';
    }

    // Close on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('detailModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>
