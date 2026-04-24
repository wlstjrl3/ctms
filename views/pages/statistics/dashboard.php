<?php
/** @var array $academyStats */
/** @var array $positionStats */
/** @var array $kidsMassStats */
/** @var array $youthMassStats */
/** @var array $hymnalStats */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="top-bar">
    <h1 id="page-title">각종 통계</h1>
    <div style="color: var(--text-muted); font-size: 0.875rem;">본당 및 교사 현황 분석</div>
</div>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
    
    <!-- Teacher Academy Distribution -->
    <div class="glass-card" style="padding: 1.5rem;">
        <h3 style="margin-bottom: 1.5rem;">부서별 교사 현황</h3>
        <div style="height: 300px;">
            <canvas id="academyChart"></canvas>
        </div>
    </div>

    <!-- Teacher Position Distribution -->
    <div class="glass-card" style="padding: 1.5rem;">
        <h3 style="margin-bottom: 1.5rem;">직책별 교사 현황</h3>
        <div style="height: 300px;">
            <canvas id="positionChart"></canvas>
        </div>
    </div>

    <!-- Kids Mass Time Distribution -->
    <div class="glass-card" style="padding: 1.5rem;">
        <h3 style="margin-bottom: 1.5rem;">어린이 미사 시간 통계</h3>
        <div style="height: 300px;">
            <canvas id="kidsMassChart"></canvas>
        </div>
    </div>

    <!-- Hymnal Source Distribution -->
    <div class="glass-card" style="padding: 1.5rem;">
        <h3 style="margin-bottom: 1.5rem;">어린이 성가책 출처 통계</h3>
        <div style="height: 300px;">
            <canvas id="hymnalChart"></canvas>
        </div>
    </div>

</div>

<script>
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#94a3b8', font: { size: 12 } }
            }
        }
    };

    // Academy Chart
    new Chart(document.getElementById('academyChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map(fn($item) => [
                '1' => '초등부', '2' => '중고등부', '3' => '대건', '4' => '장애아', '5' => '초·중고'
            ][$item['academy']] ?? '기타', $academyStats)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($academyStats, 'count')) ?>,
                backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#8b5cf6'],
                borderWidth: 0
            }]
        },
        options: chartOptions
    });

    // Position Chart
    new Chart(document.getElementById('positionChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(fn($item) => [
                '2' => '교감', '3' => '교무', '4' => '총무', '5' => '평교사', '6' => '분과장', '7' => '휴직'
            ][$item['type_num']] ?? '기타', $positionStats)) ?>,
            datasets: [{
                label: '명수',
                data: <?= json_encode(array_column($positionStats, 'count')) ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.5)',
                borderColor: '#6366f1',
                borderWidth: 1
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
            }
        }
    });

    // Kids Mass Chart
    new Chart(document.getElementById('kidsMassChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_map(fn($item) => ($item['mng_day'] == '7' ? '토요일 ' : '일요일 ') . $item['mng_hour'] . '시', $kidsMassStats)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($kidsMassStats, 'count')) ?>,
                backgroundColor: ['#f43f5e', '#3b82f6', '#8b5cf6', '#06b6d4', '#10b981'],
                borderWidth: 0
            }]
        },
        options: chartOptions
    });

    // Hymnal Chart
    new Chart(document.getElementById('hymnalChart'), {
        type: 'polarArea',
        data: {
            labels: <?= json_encode(array_map(fn($item) => [
                '1' => '수원교구', '2' => '기타'
            ][$item['sbook_source']] ?? '미입력', $hymnalStats)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($hymnalStats, 'count')) ?>,
                backgroundColor: ['#6366f1', '#f59e0b', '#94a3b8'],
                borderWidth: 0
            }]
        },
        options: chartOptions
    });
</script>
