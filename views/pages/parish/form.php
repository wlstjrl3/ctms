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
        <input type="hidden" name="mode"  value="<?= $mode ?>">
        <input type="hidden" name="idx"   value="<?= $parish['id'] ?? '' ?>">
        
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <!-- Diocese (대리구) - 표시 전용 -->
            <div class="form-group">
                <label>대리구</label>
                <select id="dioceseSelect" onchange="handleDioceseChange(this.value)" style="width: 100%;">
                    <option value="">전체 대리구</option>
                    <?php foreach ($dioceses as $d): ?>
                        <option value="<?= $d['GCODE'] ?>" data-name="<?= htmlspecialchars($d['GYOGU']) ?>"
                            <?= ($parish['diocese_code'] ?? '') == $d['GCODE'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['GYOGU']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- District (지구) - 여기서 ORG_CD가 jcode로 전송됨 -->
            <div class="form-group">
                <label>지구 <span style="color: var(--danger);">*</span></label>
                <select id="districtSelect" name="jcode" required style="width: 100%;">
                    <option value="">지구 선택...</option>
                </select>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.4rem;">
                    ORG_INFO 지구 코드: <strong id="jcode-display"><?= $parish['district_code'] ?? '' ?></strong>
                </div>
            </div>

            <!-- Parish (본당) -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>본당명 <span style="color: var(--danger);">*</span></label>
                    <div style="position: relative;">
                        <input type="text" name="bondang" value="<?= htmlspecialchars($parish['parish_name'] ?? '') ?>" required placeholder="예: 평화">
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">성당</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>본당코드 (BCODE)</label>
                    <input type="text" name="bcode" value="<?= htmlspecialchars($parish['parish_code'] ?? '') ?>" maxlength="6" placeholder="A01">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">기존 시스템 호환용</div>
                </div>
            </div>

            <!-- Phone -->
            <div class="form-group">
                <label>전화번호</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($parish['phone'] ?? '') ?>" placeholder="031-000-0000">
            </div>

            <!-- ORG_CD display (read-only) -->
            <?php if (!empty($parish['org_cd'])): ?>
            <div class="glass-card" style="padding: 1rem; background: rgba(79,70,229,0.05);">
                <div style="font-size: 0.8rem; color: var(--text-muted);">ORG_INFO 연동 정보</div>
                <div style="font-family: monospace; font-size: 0.9rem; margin-top: 0.5rem;">
                    ORG_CD: <strong style="color: var(--primary);"><?= $parish['org_cd'] ?></strong>
                    &nbsp;|&nbsp; 지구: <?= htmlspecialchars($parish['district_name'] ?? '-') ?>
                    &nbsp;|&nbsp; 대리구: <?= htmlspecialchars($parish['diocese_name'] ?? '-') ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </form>
</div>

<script>
// allDistricts now uses ORG_CD as id/JCODE
const allDistricts   = <?= json_encode($allDistricts) ?>;
const initialGcode   = '<?= $parish['diocese_code'] ?? '' ?>';
const initialJcode   = '<?= $parish['district_code'] ?? '' ?>';

function handleDioceseChange(gcode) {
    updateDistrictOptions(gcode);
}

function updateDistrictOptions(gcode) {
    const select = document.getElementById('districtSelect');
    select.innerHTML = '<option value="">지구 선택...</option>';

    const filtered = allDistricts.filter(d => !gcode || String(d.vicariate_id) === String(gcode));
    filtered.forEach(d => {
        const opt     = document.createElement('option');
        opt.value     = d.JCODE;   // ORG_CD of the district (e.g. 13090001)
        opt.text      = `${d.JIGU} (${d.JCODE})`;
        opt.setAttribute('data-name', d.JIGU);
        if (String(d.JCODE) === String(initialJcode)) opt.selected = true;
        select.appendChild(opt);
    });

    updateJcodeDisplay();
}

function updateJcodeDisplay() {
    const val = document.getElementById('districtSelect').value;
    document.getElementById('jcode-display').textContent = val || '-';
}

document.getElementById('districtSelect').addEventListener('change', updateJcodeDisplay);

window.onload = () => {
    if (initialGcode) {
        handleDioceseChange(initialGcode);
    } else {
        updateDistrictOptions('');
    }
};
</script>
