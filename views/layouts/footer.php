        </main>
    </div>


    <script>
        // Global Modal Backdrop Click Handler
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        });
    </script>
</body>
</html>
