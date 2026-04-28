<?php
/** @var array $parishes */
/** @var array $dioceses */
/** @var array $districts */
/** @var string $title */
/** @var int $pageCount */
/** @var int $totalCount */
/** @var int $page */
/** @var int $pageSize */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;" class="m-hide">
    <button id="top-action-btn" class="btn btn-primary" onclick="showOrgModal('parish')">➕ 신규 본당 등록</button>
</div>

<!-- Tabs -->
<div class="tabs" style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
    <button class="tab-btn active" data-tab="parish" onclick="showTab('parish')">본당 관리</button>
    <button class="tab-btn" data-tab="district" onclick="showTab('district')">지구 관리</button>
    <button class="tab-btn" data-tab="vicariate" onclick="showTab('vicariate')">대리구 관리</button>
</div>

<!-- Parish Tab -->
<div id="tab-parish" class="tab-content">
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <div id="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>본당명</label>
                <input type="text" name="name" class="ajax-filter" placeholder="본당명 검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>대리구</label>
                <select name="gcode" id="filter-gcode" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                    <option value="">전체</option>
                    <?php foreach ($dioceses as $d): ?>
                        <option value="<?= $d['GCODE'] ?>"><?= htmlspecialchars($d['GYOGU']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>지구</label>
                <select name="jcode" id="filter-jcode" class="ajax-filter" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                    <option value="">전체</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= $d['JCODE'] ?>" data-gcode="<?= $d['GCODE'] ?>"><?= htmlspecialchars($d['JIGU']) ?></option>
                    <?php endforeach; ?>
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
                총 <span id="total-count" style="color: var(--primary); font-weight: 700;"><?= $totalCount ?></span>개의 본당 정보
            </div>
        </div>

        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                        <th style="padding: 1rem; width: 60px; text-align: center;">No</th>
                        <th style="padding: 1rem;">대리구</th>
                        <th style="padding: 1rem;">지구</th>
                        <th style="padding: 1rem;">본당명</th>
                        <th style="padding: 1rem;">ORG_CD</th>
                    </tr>
                </thead>
                <tbody id="parish-table-body">
                    <?php include __DIR__ . '/list_rows.php'; ?>
                </tbody>
            </table>
        </div>

        <div id="pagination-container" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; padding: 1rem;">
            <?php include __DIR__ . '/../../layouts/pagination.php'; ?>
        </div>
    </div>
</div>

<!-- District Tab -->
<div id="tab-district" class="tab-content" style="display: none;">
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>지구명/코드</label>
                <input type="text" id="search-district" oninput="filterDistricts()" placeholder="검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>대리구 필터</label>
                <select id="filter-district-vic" onchange="filterDistricts()" style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
                    <option value="">전체</option>
                    <?php foreach ($dioceses as $d): ?>
                        <option value="<?= $d['GCODE'] ?>"><?= htmlspecialchars($d['GYOGU']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                    <th style="padding: 1rem;">대리구</th>
                    <th style="padding: 1rem;">지구명</th>
                    <th style="padding: 1rem;">지구코드</th>
                </tr>
            </thead>
            <tbody id="district-list-body">
                <?php foreach ($districts as $d): ?>
                <tr class="clickable-row district-row" data-vic="<?= $d['vicariate_id'] ?>" data-name="<?= htmlspecialchars($d['JIGU']) ?>" data-code="<?= $d['JCODE'] ?>" data-use-yn="<?= $d['USE_YN'] ?>" onclick="editDistrict(<?= htmlspecialchars(json_encode($d, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" style="border-bottom: 1px solid var(--glass-border);">
                    <td style="padding: 1rem;"><?= htmlspecialchars($d['GYOGU'] ?? '') ?></td>
                    <td style="padding: 1rem; font-weight: 600;"><?= htmlspecialchars($d['JIGU']) ?></td>
                    <td style="padding: 1rem; font-family: monospace; color: var(--primary);"><?= $d['JCODE'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Vicariate Tab -->
<div id="tab-vicariate" class="tab-content" style="display: none;">
    <div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>대리구명/코드</label>
                <input type="text" id="search-vicariate" oninput="filterVicariates()" placeholder="검색..." style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--glass-border); background: var(--bg-dark); color: var(--text-main);">
            </div>
        </div>
    </div>

    <div class="glass-card">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                    <th style="padding: 1rem;">대리구명</th>
                    <th style="padding: 1rem;">대리구코드</th>
                </tr>
            </thead>
            <tbody id="vicariate-list-body">
                <?php foreach ($dioceses as $v): ?>
                <tr class="clickable-row vicariate-row" data-name="<?= htmlspecialchars($v['GYOGU']) ?>" data-code="<?= $v['GCODE'] ?>" onclick="editVicariate(<?= htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" style="border-bottom: 1px solid var(--glass-border);">
                    <td style="padding: 1rem; font-weight: 600;"><?= htmlspecialchars($v['GYOGU']) ?></td>
                    <td style="padding: 1rem; font-family: monospace; color: var(--primary);"><?= $v['GCODE'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals (Simple Forms) -->
<div id="org-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center;">
    <div class="glass-card" style="width: 400px; padding: 2rem;">
        <h3 id="modal-title">등록</h3>
        <form id="org-form" method="POST">
            <input type="hidden" name="id" id="org-id">
            <div id="vicariate-select-group" class="form-group" style="display: none;">
                <label>대리구 선택</label>
                <select name="vicariate_id" id="modal-vicariate-id" style="width: 100%; padding: 0.5rem;">
                    <?php foreach ($dioceses as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['GYOGU']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label id="name-label">명칭</label>
                <input type="text" name="name" id="modal-name" required style="width: 100%; padding: 0.5rem;">
            </div>
            <div class="form-group">
                <label id="code-label">코드</label>
                <input type="text" name="code" id="modal-code" required style="width: 100%; padding: 0.5rem;">
            </div>
            <div id="use-yn-group" class="form-group" style="display: none; margin-top: 1rem; align-items: center; gap: 0.5rem;">
                <input type="checkbox" name="use_yn" id="modal-use-yn" value="Y" checked style="width: auto;">
                <label for="modal-use-yn" style="margin-bottom: 0; cursor: pointer;">현재 사용 중 (미체크 시 리스트에서 제외)</label>
            </div>
            <div style="display: flex; justify-content: space-between; gap: 0.5rem; margin-top: 1.5rem;">
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" id="modal-delete-btn" class="btn" style="display: none; background: rgba(244, 63, 94, 0.1); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.2);">삭제</button>
                    <button type="button" id="modal-unused-btn" class="btn" style="display: none; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2);">미사용 처리</button>
                </div>
                <div style="display: flex; gap: 1rem; margin-left: auto;">
                    <button type="button" class="btn" onclick="closeOrgModal()">취소</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    .tab-btn {
        background: none; border: none; padding: 0.5rem 1rem; color: var(--text-muted); cursor: pointer; font-weight: 500; transition: all 0.3s ease;
    }
    .tab-btn.active {
        color: var(--primary); border-bottom: 2px solid var(--primary); background: rgba(var(--primary-rgb), 0.1);
    }
    .clickable-row { cursor: pointer; transition: background 0.2s ease; }
    .clickable-row:hover { background: rgba(255, 255, 255, 0.05); }
    .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
    .loader {
        width: 30px; height: 30px; border: 3px solid var(--glass-border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).style.display = 'block';
        
        const tabBtn = document.querySelector(`.tab-btn[data-tab="${tab}"]`);
        if (tabBtn) tabBtn.classList.add('active');

        const topBtn = document.getElementById('top-action-btn');
        if (tab === 'parish') {
            topBtn.textContent = '➕ 신규 본당 등록';
            topBtn.onclick = () => showOrgModal('parish');
        } else if (tab === 'district') {
            topBtn.textContent = '➕ 신규 지구 등록';
            topBtn.onclick = () => showOrgModal('district');
        } else if (tab === 'vicariate') {
            topBtn.textContent = '➕ 신규 대리구 등록';
            topBtn.onclick = () => showOrgModal('vicariate');
        }
    }

    function filterDistricts() {
        const search = document.getElementById('search-district').value.toLowerCase();
        const vicFilter = document.getElementById('filter-district-vic').value;
        const rows = document.querySelectorAll('.district-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name').toLowerCase();
            const code = row.getAttribute('data-code').toLowerCase();
            const vic = row.getAttribute('data-vic');
            
            const matchesSearch = name.includes(search) || code.includes(search);
            const matchesVic = !vicFilter || vic === vicFilter;
            
            row.style.display = (matchesSearch && matchesVic) ? '' : 'none';
        });
    }

    function filterVicariates() {
        const search = document.getElementById('search-vicariate').value.toLowerCase();
        const rows = document.querySelectorAll('.vicariate-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name').toLowerCase();
            const code = row.getAttribute('data-code').toLowerCase();
            
            const matchesSearch = name.includes(search) || code.includes(search);
            row.style.display = matchesSearch ? '' : 'none';
        });
    }

    function showOrgModal(type) {
        const modal = document.getElementById('org-modal');
        const form = document.getElementById('org-form');
        const vicSelect = document.getElementById('vicariate-select-group');
        const deleteBtn = document.getElementById('modal-delete-btn');
        
        form.reset();
        document.getElementById('org-id').value = '';
        deleteBtn.style.display = 'none';
        document.getElementById('modal-unused-btn').style.display = 'none';
        document.getElementById('use-yn-group').style.display = 'none';
        document.getElementById('modal-use-yn').checked = true;
        
        if (type === 'vicariate') {
            document.getElementById('modal-title').textContent = '대리구 등록';
            form.action = '<?= $base ?>index.php?action=save_vicariate';
            vicSelect.style.display = 'none';
        } else if (type === 'district') {
            document.getElementById('modal-title').textContent = '지구 등록';
            form.action = '<?= $base ?>index.php?action=save_district';
            vicSelect.style.display = 'block';
            document.getElementById('use-yn-group').style.display = 'flex';
        } else if (type === 'parish') {
            window.location.href = '<?= $base ?>index.php?page=parish_create';
            return;
        }
        
        modal.style.display = 'flex';
    }

    function editVicariate(v) {
        showOrgModal('vicariate');
        const deleteBtn = document.getElementById('modal-delete-btn');
        document.getElementById('modal-title').textContent = '대리구 수정';
        document.getElementById('org-id').value = v.id;
        document.getElementById('modal-name').value = v.GYOGU;
        document.getElementById('modal-code').value = v.GCODE;
        
        deleteBtn.style.display = 'block';
        deleteBtn.onclick = () => {
            if(confirm('이 대리구를 삭제하시겠습니까? 관련 지구와 본당 정보에 영향을 줄 수 있습니다.')) {
                window.location.href = '<?= $base ?>index.php?action=delete_vicariate&id=' + v.id;
            }
        };
    }

    function editDistrict(d) {
        showOrgModal('district');
        const deleteBtn = document.getElementById('modal-delete-btn');
        document.getElementById('modal-title').textContent = '지구 수정';
        document.getElementById('org-id').value = d.id;
        document.getElementById('modal-vicariate-id').value = d.vicariate_id;
        document.getElementById('modal-name').value = d.JIGU;
        document.getElementById('modal-code').value = d.JCODE;
        document.getElementById('modal-use-yn').checked = (d.USE_YN === 'Y');
        document.getElementById('use-yn-group').style.display = 'flex';
        
        deleteBtn.style.display = 'block';
        deleteBtn.onclick = () => {
            if(confirm('이 지구를 삭제하시겠습니까? 관련 본당 정보에 영향을 줄 수 있습니다.')) {
                window.location.href = '<?= $base ?>index.php?action=delete_district&id=' + d.id;
            }
        };

        const unusedBtn = document.getElementById('modal-unused-btn');
        unusedBtn.style.display = 'block';
        unusedBtn.onclick = () => {
            if(confirm('이 지구를 미사용 처리하시겠습니까? 리스트에서 제외됩니다.')) {
                document.getElementById('modal-use-yn').checked = false;
                document.getElementById('org-form').submit();
            }
        };
    }

    function closeOrgModal() {
        document.getElementById('org-modal').style.display = 'none';
    }

    const parishTableBody = document.getElementById('parish-table-body');
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
            action: 'ajax_parishes',
            p: page,
            ...filters
        });

        try {
            const response = await fetch(`<?= $base ?>index.php?${params.toString()}`);
            const data = await response.json();
            
            parishTableBody.innerHTML = data.html;
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

    const filterGcode = document.getElementById('filter-gcode');
    const filterJcode = document.getElementById('filter-jcode');

    if (filterGcode) {
        filterGcode.addEventListener('change', () => {
            const selectedGcode = filterGcode.value;
            Array.from(filterJcode.options).forEach(option => {
                if (!option.value) return; // Skip "전체"
                const gcode = option.getAttribute('data-gcode');
                if (!selectedGcode || gcode === selectedGcode) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            filterJcode.value = ''; // Reset district when diocese changes
            debouncedFetch();
        });
    }

    document.querySelectorAll('.ajax-filter').forEach(input => {
        if (input.id === 'filter-gcode') return; // Handled above
        
        input.addEventListener('input', debouncedFetch);
        if (input.tagName === 'SELECT') {
            input.addEventListener('change', debouncedFetch);
        }
    });
</script>
