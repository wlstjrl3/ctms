<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - CTMS</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 2rem;
            display: block;
        }
        .error-msg {
            color: var(--danger);
            background: rgba(239, 68, 68, 0.1);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card glass-card">
            <span class="logo text-gradient">CTMS</span>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg">아이디 또는 비밀번호가 일치하지 않습니다.</div>
            <?php endif; ?>

            <form action="<?= \App\Core\App::getInstance()->getBasePath() ?>index.php?action=login" method="POST">
                <div class="form-group" style="text-align: left;">
                    <label for="userId">아이디</label>
                    <input type="text" id="userId" name="userId" required placeholder="ID를 입력하세요">
                </div>
                
                <div class="form-group" style="text-align: left;">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required placeholder="비밀번호를 입력하세요">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                    로그인
                </button>
            </form>

            <div style="margin-top: 2rem; display: flex; align-items: center; justify-content: center; gap: 1rem;">
                <button id="themeToggle" class="btn" style="background: var(--glass-bg); color: var(--text-main); font-size: 0.875rem;">
                    🌓 테마 변경
                </button>
            </div>

            <div style="margin-top: 1rem; color: var(--text-muted); font-size: 0.875rem;">
                &copy; 2026 CTMS Project
            </div>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        // Load saved theme
        if (localStorage.getItem('theme') === 'light') {
            body.classList.add('light-theme');
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('light-theme');
            const theme = body.classList.contains('light-theme') ? 'light' : 'dark';
            localStorage.setItem('theme', theme);
        });
    </script>
</body>
</html>
