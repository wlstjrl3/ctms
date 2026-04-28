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
        <button class="btn btn-primary" onclick="document.getElementById('userForm').submit()">저장하기</button>
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
                <input type="number" name="org_cd" value="<?= htmlspecialchars($user['org_cd'] ?? '') ?>" required placeholder="예: 13110004">
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.3rem;">ORG_INFO.ORG_CD (1311로 시작하는 본당코드)</div>
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
                <select name="role" class="form-select">
                    <?php $currentRole = $user['role'] ?? 'bondang'; ?>
                    <option value="bondang" <?= $currentRole === 'bondang' ? 'selected' : '' ?>>본당 (일반)</option>
                    <option value="diocese" <?= $currentRole === 'diocese' ? 'selected' : '' ?>>대리구 관리자</option>
                    <option value="casuwon" <?= $currentRole === 'casuwon' ? 'selected' : '' ?>>교구 관리자</option>
                </select>
            </div>
            <div class="form-group">
                <label>전화번호</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="02-123-4567">
            </div>
            <div class="form-group">
                <label>팩스번호</label>
                <input type="text" name="fax" value="<?= htmlspecialchars($user['fax'] ?? '') ?>" placeholder="02-123-4568">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>이메일</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
        </div>
    </form>
</div>
