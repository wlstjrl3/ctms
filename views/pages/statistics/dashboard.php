<?php
/** @var array $academyStats */
/** @var array $positionStats */

$base = \App\Core\App::getInstance()->getBasePath();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="top-bar">
    <h1 id="page-title">각종 통계</h1>
    <div style="color: var(--text-muted); font-size: 0.875rem;">재직 중인 교사 현황 분석</div>
</div>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2.5rem; margin-top: 1.5rem;">
    
    <!-- Teacher Academy Distribution -->
    <div class="glass-card" style="padding: 2rem;">
        <h3 style="margin-bottom: 2rem; color: var(--primary);">부서별 교사 현황 (재직)</h3>
        <div style="height: 350px;">
            <canvas id="academyChart"></canvas>
        </div>
    </div>

    <!-- Teacher Position Distribution -->
    <div class="glass-card" style="padding: 2rem;">
        <h3 style="margin-bottom: 2rem; color: var(--primary);">직책별 교사 현황 (재직)</h3>
        <div style="height: 350px;">
            <canvas id="positionChart"></canvas>
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
                labels: { 
                    color: '#94a3b8', 
                    font: { size: 13, weight: '600' },
                    padding: 20
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.9)',
                padding: 12,
                titleFont: { size: 14 },
                bodyFont: { size: 13 }
            }
        }
    };

    // Academy Chart
    <?php
    $deptLabels = [
        'elementary' => '초등부',
        'middle_high' => '중고등부',
        'daegun' => '대건',
        'disabled' => '장애아',
        'integrated' => '통합/기타'
    ];
    ?>

    new Chart(document.getElementById('academyChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map(fn($item) => $deptLabels[$item['academy']] ?? $item['academy'], $academyStats)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($academyStats, 'count')) ?>,
                backgroundColor: [
                    'rgba(99, 102, 241, 0.8)', 
                    'rgba(236, 72, 153, 0.8)', 
                    'rgba(245, 158, 11, 0.8)', 
                    'rgba(16, 185, 129, 0.8)', 
                    'rgba(139, 92, 246, 0.8)'
                ],
                hoverOffset: 15,
                borderWidth: 0
            }]
        },
        options: {
            ...chartOptions,
            cutout: '65%'
        }
    });

    // Position Chart
    new Chart(document.getElementById('positionChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($positionStats, 'position')) ?>,
            datasets: [{
                label: '교사 수',
                data: <?= json_encode(array_column($positionStats, 'count')) ?>,
                backgroundColor: 'rgba(99, 102, 241, 0.6)',
                borderColor: '#6366f1',
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: '#6366f1'
            }]
        },
        options: {
            ...chartOptions,
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { color: '#94a3b8', stepSize: 1 }, 
                    grid: { color: 'rgba(255,255,255,0.05)' } 
                },
                x: { 
                    ticks: { color: '#94a3b8', font: { weight: '600' } }, 
                    grid: { display: false } 
                }
            }
        }
    });
</script>
