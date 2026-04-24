        </main>
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
