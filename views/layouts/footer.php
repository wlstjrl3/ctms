        </main>
    </div>


    <script>
        // Global Smart Modal Backdrop Click Handler
        let globalMouseDownOnOverlay = false;
        window.addEventListener('mousedown', function(event) {
            globalMouseDownOnOverlay = event.target.classList.contains('modal-overlay');
        });
        window.addEventListener('mouseup', function(event) {
            if (event.target.classList.contains('modal-overlay') && globalMouseDownOnOverlay) {
                event.target.style.display = 'none';
            }
            globalMouseDownOnOverlay = false;
        });
    </script>
</body>
</html>
