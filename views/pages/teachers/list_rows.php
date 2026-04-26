<?php foreach ($teachers as $index => $t): 
    $viewNum = $totalCount - (($page - 1) * $pageSize) - $index;
    $birthYear = $t['birth_date'] ? (int)substr($t['birth_date'], 0, 4) : 0;
    $age = $birthYear ? (int)date('Y') - $birthYear : '-';
?>
<tr class="clickable-row" onclick="window.location.href='<?= $base ?>index.php?page=teacher_edit&login_id=<?= urlencode($t['login_id']) ?>'" style="border-bottom: 1px solid var(--glass-border);">
    <td style="padding: 1rem; font-family: monospace; color: var(--text-muted);"><?= $viewNum ?></td>
    <td style="padding: 1rem;">
        <div style="font-weight: 600;"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['baptismal_name']) ?>)</div>
        <div style="font-size: 0.75rem; color: var(--text-muted);">
            <?= htmlspecialchars($t['login_id']) ?> | <?= $age ?>세 | <?= $t['birth_date'] ?>
        </div>
    </td>
    <td style="padding: 1rem;">
        <div style="font-weight: 500; color: var(--text-main);"><?= htmlspecialchars($t['parish_name'] ?? '-') ?></div>
    </td>
    <td style="padding: 1rem;">
        <?php
            $academyMap = [
                'elementary' => '초등', 
                'middle_high' => '중고', 
                'daegun' => '대건', 
                'disabled' => '장애', 
                'integrated' => '통합'
            ];
            $academyName = $academyMap[$t['department']] ?? '미지정';
        ?>
        <span style="font-size: 0.85rem; font-weight: 500; color: var(--accent);"><?= $academyName ?></span>
        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($t['position'] ?: '교사') ?></div>
    </td>
    <td style="padding: 1rem;">
        <?php if (!empty($t['cs_year'])): ?>
            <div style="font-size: 0.85rem; color: var(--success); font-weight: 600;">
                <?php
                    $start = new DateTime($t['cs_year'] . '-' . $t['cs_month'] . '-01');
                    $diff = $start->diff(new DateTime());
                    echo "{$diff->y}년 {$diff->m}개월";
                ?>
            </div>
            <div style="font-size: 0.7rem; color: var(--text-muted);">기준: <?= $t['cs_year'] ?>/<?= $t['cs_month'] ?></div>
        <?php else: ?>
            <span style="color: var(--text-muted);">-</span>
        <?php endif; ?>
    </td>
    <td style="padding: 1rem; font-size: 0.85rem;">
        <div><?= htmlspecialchars($t['mobile_phone']) ?></div>
        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($t['home_phone']) ?></div>
    </td>
    <td style="padding: 1rem; text-align: center;">
        <div style="display: flex; gap: 0.25rem; justify-content: center;">
            <?php foreach ($t['awards'] ?? [] as $award): ?>
                <span title="<?= $award['tml_year'] ?>년 <?= $award['tml'] ?>년상" style="cursor: help;">🏅</span>
            <?php endforeach; if(empty($t['awards'])) echo '-'; ?>
        </div>
    </td>
</tr>
<?php endforeach; if(empty($teachers)): ?>
<tr><td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-muted);">검색 결과가 없습니다.</td></tr>
<?php endif; ?>
