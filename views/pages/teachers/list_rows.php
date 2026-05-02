<?php foreach ($teachers as $index => $t): 
    $viewNum = $totalCount - (($page - 1) * $pageSize) - $index;
    $birthYear = $t['birth_date'] ? (int)substr($t['birth_date'], 0, 4) : 0;
    $age = $birthYear ? (int)date('Y') - $birthYear : '-';
?>
<tr class="clickable-row" onclick="window.location.href='<?= $base ?>index.php?page=teacher_edit&id=<?= $t['id'] ?>'" style="border-bottom: 1px solid var(--glass-border);">
    <td style="padding: 1rem; font-family: monospace; color: var(--text-muted);" class="m-hide"><?= $viewNum ?></td>
    <td style="padding: 1rem;">
        <div style="font-weight: 600;"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['baptismal_name']) ?>)</div>
        <div style="font-size: 0.75rem; color: var(--text-muted);">
            <?= $age ?>세 | <?= $t['birth_date'] ?>
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
    <td style="padding: 1rem; text-align: center;">
        <div class="core-edu-badges">
            <?php 
                $coreMap = [
                    '기본교육(구입문과정)' => ['short' => '기본', 'color' => '#4F46E5'],
                    '구심화과정' => ['short' => '심화', 'color' => '#10B981'],
                    '양성교육(구전문화과정)' => ['short' => '양성', 'color' => '#F59E0B']
                ];
                foreach ($coreMap as $name => $info):
                    $isDone = in_array($name, $t['core_edu_list'] ?? []);
            ?>
                <span title="<?= $name ?>" style="font-size: 0.65rem; padding: 2px 4px; border-radius: 4px; border: 1px solid <?= $isDone ? $info['color'] : 'var(--glass-border)' ?>; color: <?= $isDone ? $info['color'] : 'var(--text-muted)' ?>; opacity: <?= $isDone ? 1 : 0.3 ?>; font-weight: 600;">
                    <?= $info['short'] ?>
                </span>
            <?php endforeach; ?>
        </div>
    </td>
    <td style="padding: 1rem; text-align: center;">
        <?php
            $statusMap = [
                'active' => ['text' => '재직', 'color' => '#10B981'],
                'furlough' => ['text' => '휴직', 'color' => '#F59E0B'],
                'retired' => ['text' => '퇴직', 'color' => '#EF4444']
            ];
            $s = $statusMap[$t['status']] ?? ['text' => $t['status'], 'color' => 'var(--text-muted)'];
        ?>
        <span style="font-size: 0.75rem; color: <?= $s['color'] ?>; background: <?= $s['color'] ?>15; padding: 4px 8px; border-radius: 12px; font-weight: 600; white-space: nowrap;">
            <?= $s['text'] ?>
        </span>
    </td>
    <td style="padding: 1rem;" class="m-hide">
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
    <td style="padding: 1rem; font-size: 0.85rem;" class="m-hide">
        <div><?= htmlspecialchars($t['mobile_phone']) ?></div>
    </td>
    <td style="padding: 1rem; text-align: center;" class="m-hide">
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
