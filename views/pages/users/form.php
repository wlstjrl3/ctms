<?php
/** @var array $user */
/** @var string $mode */
/** @var string $title */
$base = \App\Core\App::getInstance()->getBasePath();
?>

<div class="top-bar">
    <h1 id="page-title"><?= $title ?></h1>
    <div style="display: flex; gap: 1rem;">
        <button class="btn" onclick="history.back()" style="background: var(--glass-bg); color: var(--text-main);">취소</button>
        <?php if ($mode === 'edit'): ?>
            <button class="btn" onclick="if(confirm('정말 이 계정을 삭제하시겠습니까?')) window.location.href='<?= $base ?>index.php?action=user_delete&idx=<?= $user['id'] ?>'" style="background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.2); color: #f43f5e;">계정 삭제</button>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="validateUserForm()">저장하기</button>
    </div>
</div>

<div class="glass-card" style="max-width: 800px; margin: 2rem auto; padding: 2.5rem;">
    <form id="userForm" action="<?= $base ?>index.php?action=save_user" method="POST">
        <input type="hidden" name="mode" value="<?= $mode ?>">
        <input type="hidden" name="idx" value="<?= $user['id'] ?? '' ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="form-group">
                <label>본당명</label>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required placeholder="예: 평화본당">
            </div>
            <div class="form-group">
                <label>본당 ORG_CD</label>
                <input type="text" name="org_cd" id="org_cd_input" value="<?= htmlspecialchars($user['org_cd'] ?? '') ?>" required placeholder="예: 13110004" maxlength="8">
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">ORG_INFO.ORG_CD (1311로 시작하는 8자리 본당코드)</div>
            </div>
            <div class="form-group">
                <label>아이디</label>
                <input type="text" name="login_id" value="<?= htmlspecialchars($user['login_id'] ?? '') ?>" required <?= $mode === 'edit' ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label>비밀번호</label>
                <input type="password" name="password" value="" <?= $mode === 'create' ? 'required' : '' ?> placeholder="<?= $mode === 'edit' ? '변경시에만 입력하세요' : '' ?>">
            </div>
            <div class="form-group">
                <label>권한 설정</label>
                <select name="role" id="role_select" class="form-select">
                    <?php $currentRole = $user['role'] ?? 'bondang'; ?>
                    <option value="bondang" <?= $currentRole === 'bondang' ? 'selected' : '' ?>>본당 (일반)</option>
                    <option value="diocese" <?= $currentRole === 'diocese' ? 'selected' : '' ?>>대리구 관리자</option>
                    <option value="casuwon" <?= $currentRole === 'casuwon' ? 'selected' : '' ?>>교구 관리자</option>
                </select>
            </div>
            <div class="form-group">
                <label>내선번호 (ORG_IN_TEL)</label>
                <input type="text" name="org_in_tel" value="<?= htmlspecialchars($user['org_in_tel'] ?? '') ?>" placeholder="예: 1001">
            </div>
            <div class="form-group">
                <label>국선번호 (ORG_OUT_TEL)</label>
                <input type="text" name="org_out_tel" value="<?= htmlspecialchars($user['org_out_tel'] ?? '') ?>" placeholder="예: 031-123-4567">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>이메일</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
        </div>
    </form>
</div>

<script>
function validateUserForm() {
    const orgCd = document.getElementById('org_cd_input').value.trim();
    const role = document.getElementById('role_select').value;
    const name = document.getElementsByName('name')[0].value.trim();
    const loginId = document.getElementsByName('login_id')[0].value.trim();

    // Basic required checks
    if (!name || !loginId || !orgCd) {
        alert('필수 입력 사항을 모두 채워주세요.');
        return;
    }

    // ORG_CD validation (Only for bondang role)
    if (role === 'bondang') {
        const regex = /^1311\d{4}$/;
        if (!regex.test(orgCd)) {
            alert('본당 계정의 ORG_CD는 1311로 시작하는 8자리 숫자여야 합니다.\n예: 13110004');
            document.getElementById('org_cd_input').focus();
            return;
        }
    } else {
        // For diocese or casuwon, just check if it's 8 digits starting with 13
        const regex = /^13\d{6}$/;
        if (!regex.test(orgCd)) {
            alert('ORG_CD 형식이 올바르지 않습니다. (13으로 시작하는 8자리 숫자)');
            document.getElementById('org_cd_input').focus();
            return;
        }
    }

    document.getElementById('userForm').submit();
}
</script>
