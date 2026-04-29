<?php
/** @var array $schedules */
/** @var array $availableYears */
/** @var array $activeCourses */
/** @var int $year */
/** @var string $state */

$base = \App\Core\App::getInstance()->getBasePath();
$isAdmin = \App\Core\App::getInstance()->session()->getRole() !== 'bondang';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="margin-bottom: 0.5rem;">교육 일정 안내</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">교구 및 대리구에서 주관하는 교육 일정을 확인하고 관리합니다.</p>
    </div>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <?php if ($isAdmin): ?>
            <button class="btn btn-primary" onclick="openAddModal()" style="padding: 0.5rem 1rem; font-size: 0.8rem;">➕ 일정 추가</button>
        <?php endif; ?>
    </div>
</div>

<div class="schedule-list" style="display: flex; flex-direction: column; gap: 1.25rem;">
    <?php foreach ($schedules as $s): 
        $dateObj = new DateTime($s['edu_date']);
        $month = $dateObj->format('n월');
        $day = $dateObj->format('d');
        
        // State Badge Logic
        $stateClass = 'badge-upcoming';
        $stateText = '진행예정';
        if ($s['edu_state'] === '1') { $stateClass = 'badge-active'; $stateText = '접수중'; }
        if ($s['edu_state'] === '2') { $stateClass = 'badge-ongoing'; $stateText = '교육중'; }
        if ($s['edu_state'] === '3') { $stateClass = 'badge-closed'; $stateText = '교육종료'; }
        
        $courseName = !empty($s['standardized_name']) ? $s['standardized_name'] : $s['edu_subject'];
    ?>
    <div class="glass-card schedule-item" style="display: grid; grid-template-columns: 100px 1fr 150px; align-items: center; padding: 1.5rem; transition: var(--transition); border: 1px solid var(--glass-border); position: relative;">
        <div class="date-box" style="text-align: center; border-right: 1px solid var(--glass-border); padding-right: 1rem;">
            <div style="font-size: 0.85rem; color: var(--primary); font-weight: 700;"><?= $month ?></div>
            <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-main);"><?= $day ?></div>
        </div>
        
        <div style="padding: 0 2rem; cursor: pointer;" onclick="showDetail(<?= htmlspecialchars(json_encode($s)) ?>)">
            <div style="display: flex; gap: 0.6rem; align-items: center; margin-bottom: 0.6rem;">
                <span class="badge <?= $stateClass ?>"><?= $stateText ?></span>
                <span style="color: var(--text-muted); font-size: 0.8rem; font-weight: 500;">
                    <?= $s['edu_year'] ?>년 | <?= $s['edu_level'] == '1' ? '초등부' : ($s['edu_level'] == '2' ? '중고등부' : '통합') ?>
                </span>
            </div>
            <h3 style="margin-bottom: 0.4rem; font-size: 1.25rem;"><?= htmlspecialchars($courseName) ?></h3>
            <?php if (!empty($s['standardized_name']) && $s['standardized_name'] !== $s['edu_subject']): ?>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;"><?= htmlspecialchars($s['edu_subject']) ?></div>
            <?php endif; ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>📍 <?= htmlspecialchars($s['edu_place']) ?></span>
                <span style="opacity: 0.3;">|</span>
                <span>⏰ <?= substr($s['edu_date'], 0, 16) ?></span>
            </p>
        </div>
        
        <div style="text-align: right; display: flex; flex-direction: column; gap: 0.5rem;">
            <button class="btn btn-sm" onclick="showDetail(<?= htmlspecialchars(json_encode($s)) ?>)" style="background: rgba(255,255,255,0.05); color: var(--text-main);">상세보기</button>
            <?php if ($isAdmin): ?>
                <div style="display: flex; gap: 0.25rem; justify-content: flex-end;">
                    <button class="btn btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($s)) ?>)" style="padding: 0.4rem; font-size: 0.75rem; background: rgba(79, 70, 229, 0.1); color: var(--primary);">수정</button>
                    <button class="btn btn-sm" onclick="deleteSchedule(<?= $s['idx_num'] ?>)" style="padding: 0.4rem; font-size: 0.75rem; background: rgba(239, 68, 68, 0.1); color: #ef4444;">삭제</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; if(empty($schedules)): ?>
    <div class="glass-card" style="padding: 4rem; text-align: center; color: var(--text-muted); border: 1px dashed var(--glass-border);">
        <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">📅</div>
        등록된 교육 일정이 없습니다.
    </div>
    <?php endif; ?>
</div>

<!-- Schedule Form Modal -->
<div id="scheduleModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="glass-card modal-content" style="width: 850px; max-width: 95%; padding: 3rem; position: relative; max-height: 90vh; overflow-y: auto;">
        <button onclick="closeScheduleModal()" style="position: absolute; right: 2rem; top: 2rem; background: none; border: none; color: var(--text-muted); font-size: 2rem; cursor: pointer; transition: var(--transition);" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">&times;</button>
        <h3 id="modalTitle" style="margin-bottom: 2.5rem; color: var(--primary); font-size: 1.5rem;">교육 일정 등록</h3>
        
        <form action="<?= $base ?>index.php?action=save_schedule" method="POST" id="scheduleForm">
            <input type="hidden" name="idx_num" id="form_idx_num">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group" style="grid-column: span 2;">
                    <label>표준 교육 과정 연계</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="hidden" name="course_id" id="form_course_id">
                        <input type="text" id="form_course_name_display" readonly placeholder="과정을 검색하여 선택하세요" style="flex: 1; background: rgba(255,255,255,0.05);">
                        <button type="button" class="btn btn-primary" onclick="openCourseSearchModal('schedule')" style="padding: 0 1rem; white-space: nowrap;">검색</button>
                    </div>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label>일정 명칭 (표준 과정명과 다를 경우 직접 입력)</label>
                    <input type="text" name="edu_subject" id="form_edu_subject" required placeholder="예: 2024년 상반기 POP 초급 과정">
                </div>

                <div class="form-group">
                    <label>교육 연도</label>
                    <input type="number" name="edu_year" id="form_edu_year" value="<?= date('Y') ?>" required>
                </div>

                <div class="form-group">
                    <label>대상 부서</label>
                    <select name="edu_level" id="form_edu_level">
                        <option value="0">통합</option>
                        <option value="1">초등부</option>
                        <option value="2">중고등부</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>일시</label>
                    <input type="datetime-local" name="edu_date" id="form_edu_date" required>
                </div>

                <div class="form-group">
                    <label>장소</label>
                    <input type="text" name="edu_place" id="form_edu_place" placeholder="예: 수원교구청 지하강당">
                </div>

                <div class="form-group">
                    <label>상태</label>
                    <select name="edu_state" id="form_edu_state">
                        <option value="0">진행예정</option>
                        <option value="1">접수중</option>
                        <option value="2">교육중</option>
                        <option value="3">교육종료</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>참가비 (원)</label>
                    <input type="number" name="edu_money" id="form_edu_money" value="0">
                </div>

                <div class="form-group">
                    <label>정원 (명)</label>
                    <input type="number" name="edu_maxp" id="form_edu_maxp" value="0" placeholder="0은 제한 없음">
                </div>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label>교육 상세 내용</label>
                <textarea name="edu_content" id="form_edu_content" rows="4" style="width: 100%;"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2.5rem;">
                <button type="button" class="btn" onclick="closeScheduleModal()" style="flex: 1; background: var(--glass-bg);">취소</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">저장하기</button>
            </div>
        </form>
    </div>
</div>

<!-- Course Search Modal -->
<div id="courseSearchModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1100; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="glass-card modal-content" style="width: 500px; max-width: 90%; padding: 2rem; position: relative;">
        <button type="button" onclick="closeCourseSearchModal()" style="position: absolute; right: 1.5rem; top: 1.5rem; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h2 style="margin-bottom: 1.5rem;">교육 과정 검색</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 120px; gap: 0.5rem; margin-bottom: 1rem;">
            <input type="text" id="modal_edu_search_keyword" placeholder="교육 명칭 검색..." onkeyup="searchEducationInModal()" style="width: 100%; padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main);">
            <select id="modal_edu_search_category" onchange="searchEducationInModal()" style="padding: 0.75rem; background-color: var(--bg-dark); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main);">
                <option value="all">전체</option>
                <option value="[미분류]">[미분류]</option>
                <option value="영성 교육">영성 교육</option>
                <option value="교리/신학">교리/신학</option>
                <option value="교수법/심리">교수법/심리</option>
                <option value="기능/기술">기능/기술</option>
                <option value="리더십/소통">리더십/소통</option>
                <option value="기타">기타</option>
            </select>
        </div>

        <div id="modal_edu_search_results" style="max-height: 350px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
            <p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색어를 입력하거나 카테고리를 선택하세요</p>
        </div>
    </div>
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
    .schedule-item:hover {
        transform: translateY(-5px);
        border-color: var(--primary) !important;
        box-shadow: var(--shadow-lg);
    }
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-upcoming { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
    .badge-active { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .badge-ongoing { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
    .badge-closed { background: rgba(244, 63, 94, 0.15); color: #f43f5e; }
    
    .form-group label { display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; }
    .form-group input, .form-group select, .form-group textarea {
        border-radius: 10px;
        font-family: inherit;
    }

    .search-result-item:hover {
        background: rgba(79, 70, 229, 0.1) !important;
        border-color: var(--primary) !important;
    }
</style>

<script>
    let currentModalTarget = 'schedule';
    let eduSearchTimeout = null;

    function openAddModal() {
        document.getElementById('modalTitle').innerText = '교육 일정 등록';
        document.getElementById('form_idx_num').value = '';
        document.getElementById('form_course_id').value = '';
        document.getElementById('form_course_name_display').value = '';
        document.getElementById('scheduleForm').reset();
        document.getElementById('scheduleModal').style.display = 'flex';
    }

    function openEditModal(s) {
        document.getElementById('modalTitle').innerText = '교육 일정 수정';
        document.getElementById('form_idx_num').value = s.idx_num;
        document.getElementById('form_course_id').value = s.course_id || '';
        document.getElementById('form_course_name_display').value = s.standardized_name || '';
        document.getElementById('form_edu_subject').value = s.edu_subject;
        document.getElementById('form_edu_year').value = s.edu_year;
        document.getElementById('form_edu_level').value = s.edu_level;
        document.getElementById('form_edu_date').value = s.edu_date.replace(' ', 'T').substring(0, 16);
        document.getElementById('form_edu_place').value = s.edu_place;
        document.getElementById('form_edu_state').value = s.edu_state;
        document.getElementById('form_edu_money').value = s.edu_money;
        document.getElementById('form_edu_maxp').value = s.edu_maxp;
        document.getElementById('form_edu_content').value = s.edu_content || '';
        document.getElementById('scheduleModal').style.display = 'flex';
    }

    function closeScheduleModal() {
        document.getElementById('scheduleModal').style.display = 'none';
    }

    // --- Reusable Course Search Modal ---
    function openCourseSearchModal(target) {
        currentModalTarget = target;
        document.getElementById('courseSearchModal').style.display = 'flex';
        document.getElementById('modal_edu_search_keyword').value = '';
        document.getElementById('modal_edu_search_keyword').focus();
        searchEducationInModal();
    }

    function closeCourseSearchModal() {
        document.getElementById('courseSearchModal').style.display = 'none';
    }

    function searchEducationInModal() {
        const keyword = document.getElementById('modal_edu_search_keyword').value;
        const category = document.getElementById('modal_edu_search_category').value;
        
        clearTimeout(eduSearchTimeout);
        eduSearchTimeout = setTimeout(() => {
            fetch(`<?= $base ?>index.php?action=ajax_course_search&keyword=${encodeURIComponent(keyword)}&category=${encodeURIComponent(category)}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('modal_edu_search_results');
                    if (data.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색 결과가 없습니다</p>';
                        return;
                    }

                    container.innerHTML = data.map(c => `
                        <div class="search-result-item" onclick="selectCourseInModal(${c.id}, '${c.course_name.replace("'", "\\'")}')" style="padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px; cursor: pointer; transition: 0.2s; border: 1px solid transparent;">
                            <div style="font-weight: 600; color: var(--primary);">${c.course_name}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">${c.category}</div>
                        </div>
                    `).join('');
                });
        }, 300);
    }

    function selectCourseInModal(id, name) {
        if (currentModalTarget === 'schedule') {
            document.getElementById('form_course_id').value = id;
            document.getElementById('form_course_name_display').value = name;
            // Update subject if empty
            const subjectInput = document.getElementById('form_edu_subject');
            if (!subjectInput.value || subjectInput.value === '') {
                subjectInput.value = name;
            }
        }
        closeCourseSearchModal();
    }

    function deleteSchedule(idx) {
        if (confirm('이 교육 일정을 정말 삭제하시겠습니까?')) {
            window.location.href = `<?= $base ?>index.php?action=delete_schedule&idx=${idx}`;
        }
    }

    function showDetail(data) {
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('modalBody');
        modal.style.display = 'flex';

        if (!data) {
            body.innerHTML = `<p style="text-align:center; padding: 2rem;">데이터를 불러오는 중 오류가 발생했습니다.</p>`;
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
