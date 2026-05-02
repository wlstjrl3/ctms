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
            <div style="display: flex; gap: 4px;">
                <input type="text" id="parish_filter_display" name="parish_name" class="ajax-filter" value="<?= htmlspecialchars($filters['parish_name'] ?? '') ?>" placeholder="본당 검색..." readonly onclick="openParishModal()" style="flex: 1; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main); cursor: pointer;">
                <button type="button" onclick="clearParishFilter()" style="background: none; border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-muted); width: 34px; cursor: pointer;">&times;</button>
            </div>
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
                <option value="5" <?= ($filters['academy'] ?? '') === '5' ? 'selected' : '' ?>>대건</option>
                <option value="3" <?= ($filters['academy'] ?? '') === '3' ? 'selected' : '' ?>>장애아</option>
                <option value="4" <?= ($filters['academy'] ?? '') === '4' ? 'selected' : '' ?>>초·중고 통합</option>
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
        <div class="form-group" style="margin-bottom: 0;">
            <label>수료 교육</label>
            <select name="course_id" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                <option value="">전체 교육</option>
                <?php if (isset($courses)): foreach ($courses as $course): ?>
                    <option value="<?= $course['id'] ?>" <?= ($filters['course_id'] ?? '') == $course['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['course_name']) ?>
                    </option>
                <?php endforeach; endif; ?>
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
            총 <span id="total-count" style="color: var(--primary); font-weight: 700;"><?= $totalCount ?></span>명의 교사
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="exportExcel()" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--text-main);">
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
                    <th style="padding: 1rem; text-align: center;">교육 수료</th>
                    <th style="padding: 1rem; text-align: center;">상태</th>
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

<!-- Parish Search Modal -->
<div id="parishModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; backdrop-filter: blur(5px); align-items: center; justify-content: center;">
    <div class="glass-card" style="width: 550px; max-width: 95%; padding: 1.25rem; position: relative;">
        <button type="button" onclick="closeParishModal()" style="position: absolute; right: 1rem; top: 1rem; background: none; border: none; color: var(--text-muted); font-size: 1.25rem; cursor: pointer;">&times;</button>
        <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem;">본당 검색</h3>
        
        <div style="display: grid; grid-template-columns: 120px 110px 1fr; gap: 0.5rem; margin-bottom: 1rem;">
            <div class="form-group" style="margin: 0;">
                <select id="parish_search_vicariate" onchange="filterDistricts(this.value); searchParish()" style="width: 100%; background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 6px; padding: 0.4rem; font-size: 0.85rem;">
                    <option value="">대리구 전체</option>
                    <?php if (isset($vicariates)): foreach ($vicariates as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['GYOGU']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <select id="parish_search_district" onchange="searchParish()" style="width: 100%; background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 6px; padding: 0.4rem; font-size: 0.85rem;">
                    <option value="">지구 전체</option>
                    <?php if (isset($districts)): foreach ($districts as $d): ?>
                        <option value="<?= $d['id'] ?>" data-vicariate="<?= $d['vicariate_id'] ?>"><?= htmlspecialchars($d['JIGU']) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <input type="text" id="parish_search_keyword" placeholder="본당명 검색..." onkeyup="searchParish()" style="width: 100%; padding: 0.4rem 0.75rem; background: var(--bg-dark); border: 1px solid var(--glass-border); border-radius: 6px; color: var(--text-main); font-size: 0.85rem;">
            </div>
        </div>

        <div id="parish_search_results" style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px;">
            <p style="text-align: center; color: var(--text-muted); padding: 2rem;">로딩 중...</p>
        </div>
    </div>
</div>

<style>
    .modal-overlay { display: flex; }
    .search-result-item:hover {
        background: rgba(79, 70, 229, 0.1) !important;
        border-color: var(--primary) !important;
    }
    .search-result-item {
        padding: 0.6rem 0.85rem !important;
        background: rgba(255,255,255,0.03) !important;
        border-radius: 6px !important;
    }
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

    function exportExcel() {
        const filters = {};
        document.querySelectorAll('.ajax-filter').forEach(input => {
            if (input.value && input.value !== 'all') filters[input.name] = input.value;
        });
        
        const params = new URLSearchParams({
            action: 'export_teachers',
            ...filters
        });
        
        window.location.href = `<?= $base ?>index.php?${params.toString()}`;
    }

    document.querySelectorAll('.ajax-filter').forEach(input => {
        input.addEventListener('input', debouncedFetch);
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', debouncedFetch);
        }
    });

    // --- Parish Modal Logic ---
    let parishSearchTimeout = null;

    function openParishModal() {
        document.getElementById('parishModal').style.display = 'flex';
        searchParish();
    }

    function closeParishModal() {
        document.getElementById('parishModal').style.display = 'none';
    }

    function filterDistricts(vicariateId) {
        const districtSelect = document.getElementById('parish_search_district');
        const options = districtSelect.options;
        for (let i = 1; i < options.length; i++) {
            const vId = options[i].getAttribute('data-vicariate');
            if (!vicariateId || vId === vicariateId) {
                options[i].hidden = false;
            } else {
                options[i].hidden = true;
            }
        }
        districtSelect.value = '';
    }

    function searchParish() {
        const vicariateId = document.getElementById('parish_search_vicariate').value;
        const districtId = document.getElementById('parish_search_district').value;
        const keyword = document.getElementById('parish_search_keyword').value;

        clearTimeout(parishSearchTimeout);
        parishSearchTimeout = setTimeout(() => {
            fetch(`index.php?action=parish_search&vicariate_id=${vicariateId}&district_id=${districtId}&keyword=${encodeURIComponent(keyword)}`)
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('parish_search_results');
                    if (data.length === 0) {
                        container.innerHTML = '<p style="text-align: center; color: var(--text-muted); padding: 2rem;">검색 결과가 없습니다</p>';
                        return;
                    }

                    container.innerHTML = data.map(p => `
                        <div class="search-result-item" onclick="selectParish('${p.parish_name.replace("'", "\\'")}')" style="cursor: pointer; transition: 0.2s;">
                            <div style="font-weight: 600; font-size: 0.875rem; display: flex; align-items: center; gap: 4px;">
                                <span style="color: var(--text-muted); font-size: 0.75rem;">[${p.diocese_name || '대리구'}]</span>
                                <span style="color: var(--accent); font-size: 0.75rem;">[${p.district_name || '지구'}]</span>
                                <span style="color: var(--primary); margin-left: 2px;">${p.parish_name}</span>
                            </div>
                        </div>
                    `).join('');
                });
        }, 300);
    }

    function selectParish(name) {
        const input = document.getElementById('parish_filter_display');
        input.value = name;
        closeParishModal();
        debouncedFetch();
    }

    function clearParishFilter() {
        const input = document.getElementById('parish_filter_display');
        input.value = '';
        debouncedFetch();
    }
</script>
