<?php
/** @var array $teachers */
/** @var int $page */
/** @var int $pageCount */
/** @var int $totalCount */
/** @var array $filters */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;" class="m-hide">
    <button class="btn btn-primary" onclick="window.location.href='<?= $base ?>index.php?page=teacher_create'" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
        ➕ 교사 등록
    </button>
</div>

<!-- Filters -->
<div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <div id="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <?php if (\App\Core\App::getInstance()->session()->getRole() !== 'bondang'): ?>
        <div class="form-group" style="margin-bottom: 0;">
            <label>본당</label>
            <input type="text" name="parish_name" class="ajax-filter" value="<?= htmlspecialchars($filters['parish_name'] ?? '') ?>" placeholder="본당명 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <?php endif; ?>
        <div class="form-group" style="margin-bottom: 0;">
            <label>이름</label>
            <input type="text" name="name" class="ajax-filter" value="<?= htmlspecialchars($filters['name'] ?? '') ?>" placeholder="이름 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>세례명</label>
            <input type="text" name="bname" class="ajax-filter" value="<?= htmlspecialchars($filters['bname'] ?? '') ?>" placeholder="세례명 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>연령대</label>
            <div style="display: flex; gap: 0.25rem; align-items: center;">
                <input type="number" name="age_min" class="ajax-filter" value="<?= htmlspecialchars($filters['age_min'] ?? '') ?>" placeholder="시작" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <span>~</span>
                <input type="number" name="age_max" class="ajax-filter" value="<?= htmlspecialchars($filters['age_max'] ?? '') ?>" placeholder="끝" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>상태</label>
            <select name="status" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>전체</option>
                <option value="active" <?= ($filters['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>재직</option>
                <option value="furlough" <?= ($filters['status'] ?? '') === 'furlough' ? 'selected' : '' ?>>휴직</option>
                <option value="retired" <?= ($filters['status'] ?? '') === 'retired' ? 'selected' : '' ?>>퇴직</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>소속</label>
            <select name="academy" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <option value="all" <?= ($filters['academy'] ?? '') === 'all' ? 'selected' : '' ?>>전체</option>
                <option value="1" <?= ($filters['academy'] ?? '') === '1' ? 'selected' : '' ?>>초등부</option>
                <option value="2" <?= ($filters['academy'] ?? '') === '2' ? 'selected' : '' ?>>중고등부</option>
                <option value="125" <?= ($filters['academy'] ?? '') === '125' ? 'selected' : '' ?>>초·중고등부</option>
                <option value="3" <?= ($filters['academy'] ?? '') === '3' ? 'selected' : '' ?>>대건</option>
                <option value="4" <?= ($filters['academy'] ?? '') === '4' ? 'selected' : '' ?>>장애아</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>직책</label>
            <select name="position" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <option value="">전체</option>
                <option value="교사" <?= ($filters['position'] ?? '') === '교사' ? 'selected' : '' ?>>교사</option>
                <option value="교감" <?= ($filters['position'] ?? '') === '교감' ? 'selected' : '' ?>>교감</option>
                <option value="교무" <?= ($filters['position'] ?? '') === '교무' ? 'selected' : '' ?>>교무</option>
                <option value="총무" <?= ($filters['position'] ?? '') === '총무' ? 'selected' : '' ?>>총무</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>근속연수</label>
            <div style="display: flex; gap: 0.25rem; align-items: center;">
                <input type="number" name="tenure_min" class="ajax-filter" value="<?= htmlspecialchars($filters['tenure_min'] ?? '') ?>" placeholder="시작" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <span>~</span>
                <input type="number" name="tenure_max" class="ajax-filter" value="<?= htmlspecialchars($filters['tenure_max'] ?? '') ?>" placeholder="끝" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>연락처</label>
            <input type="text" name="phone" class="ajax-filter" value="<?= htmlspecialchars($filters['phone'] ?? '') ?>" placeholder="전화번호 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
    </div>
</div>

<div class="glass-card" style="position: relative;">
    <div id="loading-overlay" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.1); backdrop-filter: blur(2px); z-index: 10; align-items: center; justify-content: center; border-radius: var(--radius);">
        <div class="loader"></div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div style="font-size: 0.9rem; color: var(--text-muted);">
            총 <span id="total-count" style="color: var(--primary); font-weight: 700;"><?= $totalCount ?></span>명의 교사
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="window.location.href='<?= $base ?>index.php?action=export_teachers'" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--text-main);">
                📥 엑셀 출력
            </button>
        </div>
    </div>

    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                    <th style="padding: 1rem; width: 60px;" class="m-hide">No</th>
                    <th style="padding: 1rem;">성명(세례명)</th>
                    <th style="padding: 1rem;">본당</th>
                    <th style="padding: 1rem;">소속/직책</th>
                    <th style="padding: 1rem;" class="m-hide">근속 기간</th>
                    <th style="padding: 1rem;" class="m-hide">연락처</th>
                    <th style="padding: 1rem; text-align: center;" class="m-hide">수상</th>
                </tr>
            </thead>
            <tbody id="teacher-table-body">
                <?php include __DIR__ . '/list_rows.php'; ?>
            </tbody>
        </table>
    </div>

    <div id="pagination-container" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; padding: 1rem;">
        <?php include __DIR__ . '/../../layouts/pagination.php'; ?>
    </div>
</div>

<style>
    .loader {
        width: 30px;
        height: 30px;
        border: 3px solid var(--glass-border);
        border-top-color: var(--primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
    const teacherTableBody = document.getElementById('teacher-table-body');
    const totalCountSpan = document.getElementById('total-count');
    const loadingOverlay = document.getElementById('loading-overlay');
    const paginationContainer = document.getElementById('pagination-container');

    function debounce(func, timeout = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    async function fetchData(page = 1) {
        loadingOverlay.style.display = 'flex';
        
        const filters = {};
        document.querySelectorAll('.ajax-filter').forEach(input => {
            if (input.value) filters[input.name] = input.value;
        });
        
        const params = new URLSearchParams({
            action: 'ajax_teachers',
            p: page,
            ...filters
        });

        try {
            const response = await fetch(`<?= $base ?>index.php?${params.toString()}`);
            const data = await response.json();
            
            teacherTableBody.innerHTML = data.html;
            totalCountSpan.textContent = data.totalCount;
            paginationContainer.innerHTML = data.paginationHtml;
            
            if (data.pageCount <= 1) {
                paginationContainer.style.display = 'none';
            } else {
                paginationContainer.style.display = 'flex';
            }
        } catch (error) {
            console.error('Fetch error:', error);
        } finally {
            loadingOverlay.style.display = 'none';
        }
    }

    const debouncedFetch = debounce(() => fetchData(1));

    document.querySelectorAll('.ajax-filter').forEach(input => {
        input.addEventListener('input', debouncedFetch);
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', debouncedFetch);
        }
    });
</script>
