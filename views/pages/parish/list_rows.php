<?php if (empty($parishes)): ?>
<tr><td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">등록된 본당 정보가 없습니다.</td></tr>
<?php endif; ?>
<?php foreach ($parishes as $idx => $p): 
    $isInactive = ($p['USE_YN'] ?? 'Y') === 'N';
?>
<tr class="clickable-row" onclick="window.location.href='<?= $base ?>index.php?page=parish_edit&idx=<?= $p['id'] ?>'" style="border-bottom: 1px solid var(--glass-border); <?= $isInactive ? 'opacity: 0.5; filter: grayscale(0.5);' : '' ?>">
    <td style="padding: 1rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
        <?= ($page - 1) * $pageSize + $idx + 1 ?>
    </td>
    <td style="padding: 1rem;">
        <span style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($p['diocese_name'] ?? '') ?></span>
    </td>
    <td style="padding: 1rem;">
        <span style="font-size: 0.85rem;"><?= htmlspecialchars($p['district_name'] ?? '') ?></span>
    </td>
    <td style="padding: 1rem; font-weight: 600;">
        <?= htmlspecialchars($p['parish_name']) ?>성당
        <?php if($isInactive): ?>
            <span style="font-size: 0.7rem; font-weight: normal; color: #f43f5e; background: rgba(244, 63, 94, 0.1); padding: 2px 6px; border-radius: 4px; margin-left: 5px;">미사용</span>
        <?php endif; ?>
    </td>
    <td style="padding: 1rem; font-family: monospace; font-size: 0.8rem; color: var(--accent);">
        <?= $p['parish_code'] ?? '-' ?>
    </td>
    <td style="padding: 1rem; font-family: monospace; font-size: 0.75rem; color: var(--text-muted);">
        <?= $p['org_cd'] ?? '-' ?>
    </td>
</tr>
<?php endforeach; ?>
