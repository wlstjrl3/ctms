<?php
/** @var array $teachers */
/** @var int $page */
/** @var int $pageCount */
/** @var int $totalCount */
/** @var array $filters */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<div class="top-bar">
    <h1 id="page-title">본당교사 관리</h1>
    <div style="display: flex; gap: 1rem; align-items: center;">
        <span style="color: var(--text-muted); font-size: 0.875rem;">전체 <?= $totalCount ?>명</span>
        <button class="btn btn-primary" onclick="window.location.href='<?= $base ?>index.php?page=teacher_create'" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
            ➕ 교사 등록
        </button>
    </div>
</div>

<div class="glass-card" style="margin-bottom: 2rem; padding: 1.5rem;">
    <form action="<?= $base ?>index.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        <input type="hidden" name="page" value="teacher_list">
        
        <div class="form-group" style="margin-bottom: 0;">
            <label>소속 구분</label>
            <select name="academy" class="input-modern" style="background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); padding: 0.5rem; border-radius: 8px;">
                <option value="all" <?= $filters['academy'] === 'all' ? 'selected' : '' ?>>전체보기</option>
                <option value="1" <?= $filters['academy'] === '1' ? 'selected' : '' ?>>초등부</option>
                <option value="2" <?= $filters['academy'] === '2' ? 'selected' : '' ?>>중고등부</option>
                <option value="125" <?= $filters['academy'] === '125' ? 'selected' : '' ?>>초·중고등부</option>
                <option value="3" <?= $filters['academy'] === '3' ? 'selected' : '' ?>>대건</option>
                <option value="4" <?= $filters['academy'] === '4' ? 'selected' : '' ?>>장애아</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; flex-grow: 1;">
            <label>검색어</label>
            <div style="display: flex; gap: 0.5rem;">
                <select name="search_category" style="background: var(--bg-dark); color: var(--text-main); border: 1px solid var(--glass-border); padding: 0.5rem; border-radius: 8px; width: 100px;">
                    <option value="name" <?= $filters['category'] === 'name' ? 'selected' : '' ?>>이름</option>
                    <option value="bname" <?= $filters['category'] === 'bname' ? 'selected' : '' ?>>세례명</option>
                    <option value="login_id" <?= $filters['category'] === 'login_id' ? 'selected' : '' ?>>아이디</option>
                </select>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="검색어를 입력하세요..." style="flex-grow: 1;">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">검색</button>
    </form>
</div>

<div class="glass-card">
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                    <th style="padding: 1rem; width: 60px;">번호</th>
                    <th style="padding: 1rem;">이름 (세례명)</th>
                    <th style="padding: 1rem;">소속 / 직책</th>
                    <th style="padding: 1rem;">근속 기간</th>
                    <th style="padding: 1rem;">연락처</th>
                    <th style="padding: 1rem; text-align: center;">수상</th>
                    <th style="padding: 1rem; text-align: center;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $index => $t): 
                    $viewNum = $totalCount - (($page - 1) * 20) - $index;
                    $age = date('Y') - substr($t['jumin_f'], 0, 2) - (substr($t['jumin_f'], 0, 2) > 20 ? 1900 : 2000);
                ?>
                <tr style="border-bottom: 1px solid var(--glass-border); transition: var(--transition);" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 1rem; font-family: monospace; color: var(--text-muted);"><?= $viewNum ?></td>
                    <td style="padding: 1rem;">
                        <div style="font-weight: 600;"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['bname']) ?>)</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                            <?= htmlspecialchars($t['login_id']) ?> | <?= $age ?>세 | <?= $t['bday'] ?>
                        </div>
                    </td>
                    <td style="padding: 1rem;">
                        <?php
                            $academyMap = ['1' => '초등', '2' => '중고', '3' => '대건', '4' => '장애', '5' => '통합'];
                            $academyName = $academyMap[$t['academy']] ?? '미지정';
                        ?>
                        <span style="font-size: 0.85rem; font-weight: 500; color: var(--accent);"><?= $academyName ?></span>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($t['type_etc'] ?: '교사') ?></div>
                    </td>
                    <td style="padding: 1rem;">
                        <?php if ($t['cs_year']): ?>
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
                        <div><?= htmlspecialchars($t['phone2']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($t['phone1']) ?></div>
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        <div style="display: flex; gap: 0.25rem; justify-content: center;">
                            <?php foreach ($t['awards'] as $award): ?>
                                <span title="<?= $award['tml_year'] ?>년 <?= $award['tml'] ?>년상" style="cursor: help;">🏅</span>
                            <?php endforeach; if(empty($t['awards'])) echo '-'; ?>
                        </div>
                    </td>
                    <td style="padding: 1rem; text-align: center;">
                        <button class="btn" onclick="window.location.href='<?= $base ?>index.php?page=teacher_edit&login_id=<?= urlencode($t['login_id']) ?>'" style="padding: 0.4rem; background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--text-main); font-size: 0.75rem;">
                            수정
                        </button>
                    </td>
                </tr>
                <?php endforeach; if(empty($teachers)): ?>
                <tr><td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-muted);">검색 결과가 없습니다.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pageCount > 1): ?>
    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; padding: 1rem;">
        <?php 
            $startPage = max(1, $page - 5);
            $endPage = min($pageCount, $page + 5);
            
            if ($startPage > 1) echo '<span style="padding: 0.5rem; color: var(--text-muted);">...</span>';
            
            for ($i = $startPage; $i <= $endPage; $i++): 
        ?>
            <a href="<?= $base ?>index.php?page=teacher_list&p=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&search_category=<?= $filters['category'] ?>&academy=<?= $filters['academy'] ?>" 
               class="btn <?= $i === $page ? 'btn-primary' : '' ?>" 
               style="padding: 0.5rem 1rem; min-width: 40px; border: 1px solid var(--glass-border); background: <?= $i === $page ? 'var(--primary)' : 'var(--glass-bg)' ?>; color: var(--text-main);">
                <?= $i ?>
            </a>
        <?php endfor; 
            if ($endPage < $pageCount) echo '<span style="padding: 0.5rem; color: var(--text-muted);">...</span>';
        ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .input-modern {
        outline: none;
        transition: var(--transition);
    }
    .input-modern:focus {
        border-color: var(--primary);
    }
</style>
