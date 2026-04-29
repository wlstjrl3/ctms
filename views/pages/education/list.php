<?php
/** @var array $courses */
$base = \App\Core\App::getInstance()->getBasePath();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 style="margin-bottom: 0.5rem;">교육 과정 관리</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">시스템에 등록된 교육 과목을 관리하고 활성화 상태를 제어합니다.</p>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
        ➕ 새 교육 추가
    </button>
</div>

<!-- Filters -->
<div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <form id="filter-form" action="<?= $base ?>index.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: end;">
        <input type="hidden" name="page" value="education_list">
        
        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">교육명 검색</label>
            <input type="text" name="keyword" value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>" placeholder="교육 명칭 입력..." style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--glass-border); background-color: var(--bg-dark); color: var(--text-main);">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">카테고리</label>
            <select name="category" style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--glass-border); background-color: var(--bg-dark); color: var(--text-main);">
                <option value="all">전체 카테고리</option>
                <option value="[미분류]" <?= ($filters['category'] ?? '') === '[미분류]' ? 'selected' : '' ?>>[미분류]</option>
                <option value="영성 교육" <?= ($filters['category'] ?? '') === '영성 교육' ? 'selected' : '' ?>>영성 교육</option>
                <option value="교리/신학" <?= ($filters['category'] ?? '') === '교리/신학' ? 'selected' : '' ?>>교리/신학</option>
                <option value="교수법/심리" <?= ($filters['category'] ?? '') === '교수법/심리' ? 'selected' : '' ?>>교수법/심리</option>
                <option value="기능/기술" <?= ($filters['category'] ?? '') === '기능/기술' ? 'selected' : '' ?>>기능/기술</option>
                <option value="리더십/소통" <?= ($filters['category'] ?? '') === '리더십/소통' ? 'selected' : '' ?>>리더십/소통</option>
                <option value="기타" <?= ($filters['category'] ?? '') === '기타' ? 'selected' : '' ?>>기타</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">상태</label>
            <select name="status" style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid var(--glass-border); background-color: var(--bg-dark); color: var(--text-main);">
                <option value="all" <?= ($filters['status'] ?? 'all') === 'all' ? 'selected' : '' ?>>전체 상태</option>
                <option value="1" <?= ($filters['status'] ?? '') === '1' ? 'selected' : '' ?>>활성</option>
                <option value="0" <?= ($filters['status'] ?? '') === '0' ? 'selected' : '' ?>>비활성</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.2rem; flex: 1;">검색</button>
            <a href="<?= $base ?>index.php?page=education_list" class="btn" style="padding: 0.6rem; background: var(--glass-bg); color: var(--text-muted); border: 1px solid var(--glass-border); text-decoration: none; text-align: center;">초기화</a>
        </div>
    </form>
</div>

<div class="glass-card" style="position: relative; padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div style="font-size: 0.9rem; color: var(--text-muted);">
            총 <span style="color: var(--primary); font-weight: 700;"><?= count($courses) ?></span>개의 교육 과정
        </div>
    </div>

    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                    <th style="padding: 1rem; width: 80px; text-align: center;">No</th>
                    <th style="padding: 1rem;">교육 명칭</th>
                    <th style="padding: 1rem;">카테고리</th>
                    <th style="padding: 1rem; width: 120px; text-align: center;">상태</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $index => $course): ?>
                <tr class="clickable-row" onclick="openEditModal(<?= $course['id'] ?>, '<?= addslashes($course['course_name']) ?>', '<?= addslashes($course['category']) ?>', <?= $course['is_active'] ?>)" style="border-bottom: 1px solid var(--glass-border); transition: var(--transition); cursor: pointer;">
                    <td style="padding: 1rem; text-align: center; font-family: monospace; color: var(--text-muted);"><?= $index + 1 ?></td>
                    <td style="padding: 1rem;">
                        <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($course['course_name']) ?></div>
                    </td>
                    <td style="padding: 1rem;">
                        <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);"><?= htmlspecialchars($course['category']) ?></span>
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        <?php if ($course['is_active']): ?>
                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 700;">활성</span>
                        <?php else: ?>
                            <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; font-weight: 700;">비활성</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; if(empty($courses)): ?>
                <tr><td colspan="4" style="padding: 3rem; text-align: center; color: var(--text-muted);">등록된 교육 과정이 없습니다.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="courseModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="glass-card modal-content" style="width: 450px; max-width: 95%; padding: 2.5rem; position: relative;">
        <button onclick="closeModal()" style="position: absolute; right: 1.5rem; top: 1.5rem; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
        <h3 id="modalTitle" style="margin-bottom: 2rem; color: var(--primary);">교육 과정 추가</h3>
        
        <form action="<?= $base ?>index.php?action=save_course" method="POST">
            <input type="hidden" name="id" id="course_id">
            <div class="form-group">
                <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">교육 명칭</label>
                <input type="text" name="course_name" id="course_name" required placeholder="예: POP초급" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--glass-border); background-color: var(--bg-dark); color: var(--text-main);">
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; display: block;">카테고리</label>
                <select name="category" id="course_category" style="width: 100%; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--glass-border); background-color: var(--bg-dark); color: var(--text-main);">
                    <option value="[미분류]">[미분류]</option>
                    <option value="영성 교육">영성 교육</option>
                    <option value="교리/신학">교리/신학</option>
                    <option value="교수법/심리">교수법/심리</option>
                    <option value="기능/기술">기능/기술 (레크/미디어 등)</option>
                    <option value="리더십/소통">리더십/소통</option>
                    <option value="기타">기타</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 0.75rem; margin-top: 2.5rem;">
                <button type="button" class="btn" onclick="closeModal()" style="padding: 0.5rem; background: var(--glass-bg); color: var(--text-muted);">취소</button>
                <a id="toggle_status_btn" href="#" class="btn" style="padding: 0.5rem; flex: 1; text-decoration: none; text-align: center; font-weight: 600; display: none;">비활성화</a>
                <button type="submit" class="btn btn-primary" style="flex: 1; font-weight: 600;">저장하기</button>
            </div>
        </form>
    </div>
</div>

<style>
    .clickable-row:hover {
        background: rgba(79, 70, 229, 0.05);
    }
</style>

<script>
    function openAddModal() {
        document.getElementById('modalTitle').innerText = '교육 과정 추가';
        document.getElementById('course_id').value = '';
        document.getElementById('course_name').value = '';
        document.getElementById('course_category').value = '[미분류]';
        document.getElementById('toggle_status_btn').style.display = 'none';
        document.getElementById('courseModal').style.display = 'flex';
    }

    function openEditModal(id, name, cat, isActive) {
        document.getElementById('modalTitle').innerText = '교육 과정 수정';
        document.getElementById('course_id').value = id;
        document.getElementById('course_name').value = name;
        document.getElementById('course_category').value = cat;
        
        const toggleBtn = document.getElementById('toggle_status_btn');
        toggleBtn.style.display = 'block';
        toggleBtn.href = `<?= $base ?>index.php?action=toggle_course_status&id=${id}`;
        
        if (isActive) {
            toggleBtn.innerText = '비활성화';
            toggleBtn.style.background = 'rgba(239, 68, 68, 0.1)';
            toggleBtn.style.color = '#ef4444';
            toggleBtn.style.border = '1px solid rgba(239, 68, 68, 0.2)';
        } else {
            toggleBtn.innerText = '활성화';
            toggleBtn.style.background = 'rgba(16, 185, 129, 0.1)';
            toggleBtn.style.color = '#10b981';
            toggleBtn.style.border = '1px solid rgba(16, 185, 129, 0.2)';
        }
        
        document.getElementById('courseModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('courseModal').style.display = 'none';
    }
</script>
