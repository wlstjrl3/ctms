<?php
/** @var array $parish */
/** @var array $dioceses */
/** @var array $allDistricts */
/** @var string $mode */
/** @var string $title */
$base = \App\Core\App::getInstance()->getBasePath();
?>

<div class="top-bar">
    <h1 id="page-title"><?= $title ?></h1>
    <div style="display: flex; gap: 1rem;">
        <button class="btn" onclick="history.back()" style="background: var(--glass-bg); color: var(--text-main);">취소</button>
        <?php if ($mode === 'edit'): ?>
            <button class="btn" onclick="if(confirm('정말 삭제하시겠습니까?\n이 본당 코드를 사용하는 모든 데이터에 영향을 줄 수 있습니다.')) window.location.href='<?= $base ?>index.php?action=parish_delete&idx=<?= $parish['id'] ?>'" style="background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.2); color: #f43f5e;">삭제하기</button>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="document.getElementById('parishForm').submit()">저장하기</button>
    </div>
</div>

<div class="glass-card" style="max-width: 700px; margin: 2rem auto; padding: 2.5rem;">
    <form id="parishForm" action="<?= $base ?>index.php?action=save_parish" method="POST">
        <input type="hidden" name="mode" value="<?= $mode ?>">
        <input type="hidden" name="idx" value="<?= $parish['id'] ?? '' ?>">
        
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <!-- Diocese (대리구) -->
            <div class="form-group">
                <label>대리구 선택</label>
                <div style="display: flex; gap: 1rem;">
                    <select id="dioceseSelect" onchange="handleDioceseChange(this.value)" style="flex: 2;">
                        <option value="">새로 입력...</option>
                        <?php foreach ($dioceses as $d): ?>
                            <option value="<?= $d['GCODE'] ?>" data-name="<?= htmlspecialchars($d['GYOGU']) ?>" <?= ($parish['diocese_code'] ?? '') === $d['GCODE'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['GYOGU']) ?> (<?= $d['GCODE'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="gyogu" id="gyoguInput" value="<?= htmlspecialchars($parish['diocese_name'] ?? '') ?>" placeholder="대리구명" style="flex: 2;" required>
                    <input type="text" name="gcode" id="gcodeInput" value="<?= htmlspecialchars($parish['diocese_code'] ?? '') ?>" placeholder="코드" style="flex: 1;" required>
                </div>
            </div>

            <!-- District (지구) -->
            <div class="form-group">
                <label>지구 선택</label>
                <div style="display: flex; gap: 1rem;">
                    <select id="districtSelect" onchange="handleDistrictChange(this.value)" style="flex: 2;">
                        <option value="">새로 입력...</option>
                        <!-- Options will be populated by JS -->
                    </select>
                    <input type="text" name="jigu" id="jiguInput" value="<?= htmlspecialchars($parish['district_name'] ?? '') ?>" placeholder="지구명" style="flex: 2;" required>
                    <input type="text" name="jcode" id="jcodeInput" value="<?= htmlspecialchars($parish['district_code'] ?? '') ?>" placeholder="코드" style="flex: 1;" required>
                </div>
            </div>

            <!-- Parish (본당) -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>본당명</label>
                    <div style="position: relative;">
                        <input type="text" name="bondang" value="<?= htmlspecialchars($parish['parish_name'] ?? '') ?>" required placeholder="예: 평화">
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">성당</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>본당코드</label>
                    <input type="text" name="bcode" value="<?= htmlspecialchars($parish['parish_code'] ?? '') ?>" maxlength="6" required placeholder="001">
                </div>
            </div>

        </div>
    </form>
</div>

<script>
const allDistricts = <?= json_encode($allDistricts) ?>;
const initialGcode = '<?= $parish['diocese_code'] ?? '' ?>';
const initialJcode = '<?= $parish['district_code'] ?? '' ?>';

function handleDioceseChange(gcode) {
    const select = document.getElementById('dioceseSelect');
    const nameInput = document.getElementById('gyoguInput');
    const codeInput = document.getElementById('gcodeInput');
    
    if (gcode) {
        const option = select.options[select.selectedIndex];
        nameInput.value = option.getAttribute('data-name');
        codeInput.value = gcode;
        nameInput.readOnly = true;
        codeInput.readOnly = true;
    } else {
        nameInput.value = '';
        codeInput.value = '';
        nameInput.readOnly = false;
        codeInput.readOnly = false;
    }
    
    updateDistrictOptions(gcode);
}

function updateDistrictOptions(gcode) {
    const select = document.getElementById('districtSelect');
    const nameInput = document.getElementById('jiguInput');
    const codeInput = document.getElementById('jcodeInput');
    
    // Clear existing
    select.innerHTML = '<option value="">새로 입력...</option>';
    
    const filtered = allDistricts.filter(d => !gcode || d.GCODE === gcode);
    
    filtered.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.JCODE;
        opt.text = `${d.JIGU} (${d.JCODE})`;
        opt.setAttribute('data-name', d.JIGU);
        if (d.JCODE === initialJcode) opt.selected = true;
        select.appendChild(opt);
    });

    // If initial load and matches, lock it
    if (select.value) {
        nameInput.readOnly = true;
        codeInput.readOnly = true;
    }
}

function handleDistrictChange(jcode) {
    const select = document.getElementById('districtSelect');
    const nameInput = document.getElementById('jiguInput');
    const codeInput = document.getElementById('jcodeInput');
    
    if (jcode) {
        const option = select.options[select.selectedIndex];
        nameInput.value = option.getAttribute('data-name');
        codeInput.value = jcode;
        nameInput.readOnly = true;
        codeInput.readOnly = true;
    } else {
        nameInput.value = '';
        codeInput.value = '';
        nameInput.readOnly = false;
        codeInput.readOnly = false;
    }
}

// Initial setup
window.onload = () => {
    if (initialGcode) {
        handleDioceseChange(initialGcode);
    } else {
        updateDistrictOptions('');
    }
};
</script>
