<?php foreach ($users as $u): ?>
<?php 
    $roleLabel = '본당';
    $roleClass = '';
    if ($u['role'] === 'office') {
        $roleLabel = '사무처';
        $roleClass = 'background: rgba(79, 70, 229, 0.1); color: var(--primary); border: 1px solid rgba(79, 70, 229, 0.2);';
    } elseif ($u['role'] === 'diocese') {
        $roleLabel = '대리구';
        $roleClass = 'background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);';
    }
?>
<tr class="clickable-row" onclick="window.location.href='<?= $base ?>index.php?page=user_edit&idx=<?= $u['id'] ?>'" style="border-bottom: 1px solid var(--glass-border);">
    <td style="padding: 1rem;">
        <div style="font-weight: 600;"><?= htmlspecialchars($u['parish_name'] ?: '시스템') ?></div>
        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($u['name']) ?></div>
    </td>
    <td style="padding: 1rem; font-family: monospace;"><?= htmlspecialchars($u['login_id']) ?></td>
    <td style="padding: 1rem; font-weight: 700; color: var(--accent);"><?= htmlspecialchars($u['parish_code'] ?: '-') ?></td>
    <td style="padding: 1rem;">
        <span style="font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 4px; <?= $roleClass ?: 'background: var(--glass-bg); border: 1px solid var(--glass-border);' ?>">
            <?= $roleLabel ?>
        </span>
    </td>
    <td style="padding: 1rem; font-size: 0.85rem;">
        <div>T: <?= htmlspecialchars($u['phone'] ?? '-') ?></div>
        <div>F: <?= htmlspecialchars($u['fax'] ?? '-') ?></div>
    </td>
    <td style="padding: 1rem; font-size: 0.8rem; color: var(--text-muted);">
        <?= $u['last_login_at'] ?? '기록 없음' ?>
    </td>
</tr>
<?php endforeach; if(empty($users)): ?>
<tr><td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">등록된 계정이 없습니다.</td></tr>
<?php endif; ?>
