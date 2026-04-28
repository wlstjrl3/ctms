<?php
/** @var array $users */
/** @var string $title */
/** @var int $pageCount */
/** @var int $totalCount */
/** @var int $page */
/** @var int $pageSize */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;" class="m-hide">
    <button class="btn btn-primary" onclick="window.location.href='<?= $base ?>index.php?page=user_create'">
        ➕ 신규 본당 계정 등록
    </button>
</div>

<!-- Filters -->
<div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <div id="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <div class="form-group" style="margin-bottom: 0;">
            <label>본당명 / 담당자</label>
            <input type="text" name="name" class="ajax-filter" placeholder="성명/본당명 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>아이디</label>
            <input type="text" name="login_id" class="ajax-filter" placeholder="아이디 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>ORG_CD</label>
            <input type="text" name="org_cd" class="ajax-filter" placeholder="코드 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>권한</label>
            <select name="role" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <option value="">전체</option>
                <option value="bondang">본당</option>
                <option value="diocese">대리구</option>
                <option value="casuwon">교구</option>
            </select>
        </div>
    </div>
</div>

<div class="glass-card" style="position: relative;">
    <div id="loading-overlay" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.1); backdrop-filter: blur(2px); z-index: 10; align-items: center; justify-content: center; border-radius: var(--radius);">
        <div class="loader"></div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div style="font-size: 0.9rem; color: var(--text-muted);">
            총 <span id="total-count" style="color: var(--primary); font-weight: 700;"><?= $totalCount ?></span>개의 계정
        </div>
    </div>

    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                    <th style="padding: 1rem;">본당명 / 담당자</th>
                    <th style="padding: 1rem;">아이디</th>
                    <th style="padding: 1rem;">ORG_CD</th>
                    <th style="padding: 1rem; text-align: center;">권한</th>
                    <th style="padding: 1rem;">연락처</th>
                    <th style="padding: 1rem; font-size: 0.8rem;">마지막 접속</th>
                </tr>
            </thead>
            <tbody id="user-table-body">
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
    const userTableBody = document.getElementById('user-table-body');
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
            action: 'ajax_users',
            p: page,
            ...filters
        });

        try {
            const response = await fetch(`<?= $base ?>index.php?${params.toString()}`);
            const data = await response.json();
            
            userTableBody.innerHTML = data.html;
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
