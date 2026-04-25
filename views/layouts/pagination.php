<?php if ($pageCount > 1): ?>
    <?php if ($page > 1): ?>
        <button onclick="fetchData(1)" class="btn" style="padding: 0.5rem 0.75rem; background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--text-main);">&laquo;</button>
    <?php endif; ?>

    <?php 
        $startPage = max(1, $page - 3);
        $endPage = min($pageCount, $startPage + 6);
        if ($endPage - $startPage < 6) $startPage = max(1, $endPage - 6);
        
        for ($i = $startPage; $i <= $endPage; $i++): 
    ?>
        <button onclick="fetchData(<?= $i ?>)" 
           class="btn <?= (int)$i === (int)$page ? 'btn-primary' : '' ?>" 
           style="padding: 0.5rem 1rem; min-width: 40px; border: 1px solid var(--glass-border); background: <?= (int)$i === (int)$page ? 'var(--primary)' : 'var(--glass-bg)' ?>; color: <?= (int)$i === (int)$page ? '#fff' : 'var(--text-main)' ?>;">
            <?= $i ?>
        </button>
    <?php endfor; ?>

    <?php if ($page < $pageCount): ?>
        <button onclick="fetchData(<?= $pageCount ?>)" class="btn" style="padding: 0.5rem 0.75rem; background: var(--glass-bg); border: 1px solid var(--glass-border); color: var(--text-main);">&raquo;</button>
    <?php endif; ?>
<?php endif; ?>
