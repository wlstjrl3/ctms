<?php
declare(strict_types=1);
$session = \App\Core\App::getInstance()->session();
$userRole = $session->getRole();
$userName = $session->get('user_name');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTMS Modern - 주일학교 교리교사 관리시스템</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: var(--bg-card);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            backdrop-filter: var(--glass-blur);
        }
        .main-content {
            padding: 2rem 3rem;
            overflow-y: auto;
        }
        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .nav-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(79, 70, 229, 0.1);
            color: var(--text-main);
        }
        .nav-item.active {
            color: var(--primary);
            background: rgba(79, 70, 229, 0.15);
        }
        .user-profile {
            margin-top: auto;
            padding: 1rem;
            border-radius: 12px;
            background: var(--glass-bg);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .role-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            background: var(--primary);
            color: white;
            align-self: flex-start;
            text-transform: uppercase;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="">
    <div class="layout">
        <aside class="sidebar">
            <div class="logo text-gradient" style="font-size: 1.5rem; font-weight: 800;">
                CTMS MODERN
            </div>

            <nav>
<?php 
$base = \App\Core\App::getInstance()->getBasePath(); 
$currentPage = $_GET['page'] ?? 'dashboard';
?>
                <ul class="nav-menu">
                    <li><a href="<?= $base ?>index.php?page=dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">🏠 대시보드</a></li>
                    <?php if ($userRole === 'bondang'): ?>
                        <li><a href="<?= $base ?>index.php?page=teacher_list" class="nav-item <?= $currentPage === 'teacher_list' ? 'active' : '' ?>">👤 본당교리교사</a></li>
                        <li><a href="<?= $base ?>index.php?page=statistics" class="nav-item <?= $currentPage === 'statistics' ? 'active' : '' ?>">📊 각종 통계</a></li>
                    <?php else: ?>
                        <li><a href="<?= $base ?>index.php?page=teacher_list" class="nav-item <?= $currentPage === 'teacher_list' ? 'active' : '' ?>">🏢 본당교리교사 관리</a></li>
                        <li><a href="<?= $base ?>index.php?page=statistics" class="nav-item <?= $currentPage === 'statistics' ? 'active' : '' ?>">📊 각종 통계</a></li>
                    <?php endif; ?>
                    <li><a href="<?= $base ?>index.php?page=edu_schedule" class="nav-item <?= $currentPage === 'edu_schedule' ? 'active' : '' ?>">📅 교육 일정</a></li>
                </ul>
            </nav>

            <div class="user-profile">
                <span class="role-badge"><?= htmlspecialchars($userRole) ?></span>
                <div style="font-weight: 600;"><?= htmlspecialchars($userName) ?>님</div>
                <button id="themeToggle" class="btn" style="padding: 0.4rem; font-size: 0.75rem; background: var(--glass-bg); color: var(--text-muted);">
                    🌓 테마 변경
                </button>
                <a href="<?= $base ?>index.php?action=logout" class="nav-item" style="padding: 0.5rem 0; margin-top: 0.5rem;">🚪 로그아웃</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar" style="margin-bottom: 2rem;">
                <h1 id="page-title" style="font-size: 1.5rem; font-weight: 700;">
                    <?php
                        $titles = [
                            'dashboard' => '대시보드',
                            'teacher_list' => '본당교리교사',
                            'teacher_create' => '교사 신규 등록',
                            'teacher_edit' => '교사 상세 정보',
                            'statistics' => '각종 통계',
                            'edu_schedule' => '교육 일정'
                        ];
                        echo $titles[$currentPage] ?? 'CTMS Modern';
                    ?>
                </h1>
                <div style="color: var(--text-muted); font-size: 0.875rem;">
                    <?= date('Y년 m월 d일') ?>
                </div>
            </div>
