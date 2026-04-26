<?php
/** @var array $teachers */
/** @var int $page */
/** @var int $pageCount */
/** @var int $totalCount */
/** @var array $filters */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<div class="top-bar">
    <h1 id="page-title">본당교사 관리</h1>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <button class="btn btn-primary" onclick="window.location.href='<?= $base ?>index.php?page=teacher_create'" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
            ➕ 교사 등록
        </button>
    </div>
</div>

<!-- Filters -->
<div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <div id="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <div class="form-group" style="margin-bottom: 0;">
            <label>이름</label>
            <input type="text" name="name" class="ajax-filter" placeholder="이름 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>세례명</label>
            <input type="text" name="bname" class="ajax-filter" placeholder="세례명 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>연령대</label>
            <div style="display: flex; gap: 0.25rem; align-items: center;">
                <input type="number" name="age_start" class="ajax-filter" placeholder="시작" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <span>~</span>
                <input type="number" name="age_end" class="ajax-filter" placeholder="끝" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>소속</label>
            <select name="academy" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <option value="all">전체</option>
                <option value="1">초등부</option>
                <option value="2">중고등부</option>
                <option value="125">초·중고등부</option>
                <option value="3">대건</option>
                <option value="4">장애아</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>직책</label>
            <input type="text" name="position" class="ajax-filter" placeholder="직책 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>근속연수</label>
            <div style="display: flex; gap: 0.25rem; align-items: center;">
                <input type="number" name="tenure_start" class="ajax-filter" placeholder="시작" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <span>~</span>
                <input type="number" name="tenure_end" class="ajax-filter" placeholder="끝" style="width: 50%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>연락처</label>
            <input type="text" name="phone" class="ajax-filter" placeholder="전화번호 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
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
                    <th style="padding: 1rem; width: 60px;">No</th>
                    <th style="padding: 1rem;">성명(세례명)</th>
                    <th style="padding: 1rem;">본당</th>
                    <th style="padding: 1rem;">소속/직책</th>
                    <th style="padding: 1rem;">근속 기간</th>
                    <th style="padding: 1rem;">연락처</th>
                    <th style="padding: 1rem; text-align: center;">수상</th>
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
