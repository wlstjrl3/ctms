<?php
/** @var array $data */
$session = \App\Core\App::getInstance()->session();
$userRole = $session->getRole();
?>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 2rem;">
    
    <!-- 이달의 교육 일정 -->
    <section class="glass-card">
        <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            📅 이달의 교육 일정
        </h2>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                        <th style="padding: 1rem;">일정</th>
                        <th style="padding: 1rem; width: 60px; text-align: center;">대상</th>
                        <th style="padding: 1rem; width: 100px; text-align: center;">상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['schedules'] as $row): ?>
                    <tr style="border-bottom: 1px solid var(--glass-border);">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['edu_subject']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= substr($row['edu_sdate'], 0, 16) ?> ~ <?= substr($row['edu_edate'], 0, 16) ?>
                            </div>
                        </td>
                        <td style="padding: 1rem; text-align: center; font-size: 0.8rem;">
                            <?= $this->teacherService->getGradeName($row['edu_level']) ?>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php 
                                echo \App\Utils\DateHelper::getStatusInRange(
                                    $row['edu_sdate'], 
                                    $row['edu_edate'], 
                                    date('Y-m-d H:i:s'), 
                                    '교육'
                                );
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($data['schedules'])): ?>
                    <tr><td colspan="3" style="padding: 2rem; text-align: center; color: var(--text-muted);">데이터가 없습니다.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- 이달의 접수 일정 -->
    <section class="glass-card">
        <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            📝 이달의 접수 일정
        </h2>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                        <th style="padding: 1rem;">일정</th>
                        <th style="padding: 1rem; width: 100px; text-align: center;">상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['applications'] as $row): ?>
                    <tr style="border-bottom: 1px solid var(--glass-border);">
                        <td style="padding: 1rem;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['edu_subject']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= substr($row['edu_to_sdate'], 0, 16) ?> ~ <?= substr($row['edu_to_edate'], 0, 16) ?>
                            </div>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php 
                                echo \App\Utils\DateHelper::getStatusInRange(
                                    $row['edu_to_sdate'], 
                                    $row['edu_to_edate'], 
                                    date('Y-m-d H:i:s'), 
                                    '접수'
                                );
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; if(empty($data['applications'])): ?>
                    <tr><td colspan="2" style="padding: 2rem; text-align: center; color: var(--text-muted);">데이터가 없습니다.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if (isset($data['recentLogins'])): ?>
    <!-- 최근 접속 사용자 (Admin) -->
    <section class="glass-card" style="grid-column: 1 / -1;">
        <h2 style="margin-bottom: 1.5rem;">👥 최근 접속 사용자</h2>
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--glass-border); text-align: left; color: var(--text-muted); font-size: 0.8rem;">
                        <th style="padding: 1rem; width: 60px;">번호</th>
                        <th style="padding: 1rem;">관리본당</th>
                        <th style="padding: 1rem;">사용자</th>
                        <th style="padding: 1rem;">접속시간</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($data['recentLogins'] as $row): ?>
                    <tr style="border-bottom: 1px solid var(--glass-border);">
                        <td style="padding: 0.75rem 1rem; font-family: monospace;"><?= $i++ ?></td>
                        <td style="padding: 0.75rem 1rem;">
                            <?= htmlspecialchars($row['bondang_name'] ?? '전체') ?> (<?= htmlspecialchars($row['bcode']) ?>)
                        </td>
                        <td style="padding: 0.75rem 1rem;">
                            <?= htmlspecialchars($row['login_name']) ?> (<?= htmlspecialchars($row['login_id']) ?>)
                        </td>
                        <td style="padding: 0.75rem 1rem; color: var(--accent); font-size: 0.8rem;">
                            <?= $row['login_date'] ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

</div>
