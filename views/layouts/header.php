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
    <title>CTMS - 주일학교 교리교사 통합 관리 시스템</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            transition: var(--transition);
        }
        .sidebar {
            background: var(--bg-card);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            backdrop-filter: var(--glass-blur);
            z-index: 100;
            transition: transform 0.3s ease;
        }
        .main-content {
            padding: 2rem 3rem;
            overflow-y: auto;
            position: relative;
        }

        /* Responsive Mobile Styles */
        @media (max-width: 1024px) {
            .layout {
                grid-template-columns: 1fr;
            }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 260px;
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                padding: 1.5rem;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(4px);
                z-index: 90;
            }
            .sidebar-overlay.active {
                display: block;
            }
            .mobile-toggle {
                display: flex !important;
            }
        }

        .mobile-toggle {
            display: none;
            width: 40px;
            height: 40px;
            background: var(--glass-bg);
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid var(--glass-border);
            margin-right: 1rem;
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
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="layout">
        <aside class="sidebar">
            <?php $base = \App\Core\App::getInstance()->getBasePath(); ?>
            <a href="<?= $base ?>index.php" style="text-decoration: none;">
                <div class="logo text-gradient" style="font-size: 1.5rem; font-weight: 800; cursor: pointer; letter-spacing: -0.5px;">
                    CTMS
                </div>
            </a>

            <div class="user-profile" style="margin-top: 0;">
                <?php
                    $roleLabel = '본당';
                    if ($userRole === 'casuwon') $roleLabel = '교구';
                    elseif ($userRole === 'diocese') $roleLabel = '대리구';
                ?>
                <span class="role-badge"><?= $roleLabel ?></span>
                <div style="font-weight: 600;"><?= htmlspecialchars($userName) ?>님</div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.75rem;">
                    <a href="<?= $base ?>index.php?page=manual" class="btn" style="padding: 0.5rem; font-size: 0.8rem; background: rgba(79, 70, 229, 0.1); color: var(--primary); border: 1px solid rgba(79, 70, 229, 0.2); text-decoration: none; text-align: center; border-radius: 8px; font-weight: 600;">
                        📖 이용 매뉴얼
                    </a>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button id="themeToggle" class="btn" style="padding: 0.4rem; font-size: 0.75rem; background: var(--glass-bg); color: var(--text-muted); flex: 1;">
                            🌓 테마
                        </button>
                        <a href="<?= $base ?>index.php?action=logout" class="btn" style="padding: 0.4rem; font-size: 0.75rem; background: rgba(244, 63, 94, 0.1); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.2); text-decoration: none; text-align: center; flex: 1;">
                            🚪 로그아웃
                        </a>
                    </div>
                </div>
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
                        <li><a href="<?= $base ?>index.php?page=user_list" class="nav-item <?= $currentPage === 'user_list' ? 'active' : '' ?>">🔑 본당 계정 관리</a></li>
                        <li><a href="<?= $base ?>index.php?page=parish_list" class="nav-item <?= strpos($currentPage, 'parish') === 0 ? 'active' : '' ?>">⛪ 본당 코드 관리</a></li>
                        <li><a href="<?= $base ?>index.php?page=education_list" class="nav-item <?= $currentPage === 'education_list' ? 'active' : '' ?>">📚 교육 과정 관리</a></li>
                        <li><a href="<?= $base ?>index.php?page=statistics" class="nav-item <?= $currentPage === 'statistics' ? 'active' : '' ?>">📊 각종 통계</a></li>
                    <?php endif; ?>
                    <li><a href="<?= $base ?>index.php?page=edu_schedule" class="nav-item <?= $currentPage === 'edu_schedule' ? 'active' : '' ?>">📅 교육 일정</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display: flex; align-items: center;">
                    <div class="mobile-toggle" id="mobileToggle">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </div>
                    <h1 id="page-title" style="font-size: 1.5rem; font-weight: 700;">
                    <?php
                        $titles = [
                            'dashboard' => '대시보드',
                            'teacher_list' => '본당교리교사',
                            'teacher_create' => '교사 신규 등록',
                            'teacher_edit' => '교사 상세 정보',
                            'statistics' => '각종 통계',
                            'edu_schedule' => '교육 일정',
                            'user_list' => '본당 계정 관리',
                            'user_create' => '본당 계정 등록',
                            'user_edit' => '본당 계정 수정',
                            'education_list' => '교육 과정 관리'
                        ];
                        echo $titles[$currentPage] ?? 'CTMS';
                    ?>
                </h1>
                </div>
                <div style="color: var(--text-muted); font-size: 0.875rem;" class="desktop-only">
                    <?= date('Y년 m월 d일') ?>
                </div>
            </div>

<script>
    // Theme Toggle
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            document.body.classList.toggle('light-theme');
            const isLight = document.body.classList.contains('light-theme');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
        });
    }

    // Restore Theme
    if (localStorage.getItem('theme') === 'light') {
        document.body.classList.add('light-theme');
    }

    // Mobile Sidebar Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.add('active');
            overlay.classList.add('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }

    // Close sidebar on link click
    document.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    });
</script>
