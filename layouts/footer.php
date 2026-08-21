        </div> <!-- End Scrollable Content -->
        <!-- Flash Messages Container for JS (if needed) -->
    </main>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');
            const menuTexts = document.querySelectorAll('.menu-text');
            const logoContainer = document.querySelector('.sidebar-logo');

            if(toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', () => {
                    if (sidebar.classList.contains('w-64')) {
                        // Minimize
                        sidebar.classList.remove('w-64');
                        sidebar.classList.add('w-20'); // 5rem width
                        logoContainer.classList.remove('px-6');
                        logoContainer.classList.add('justify-center');
                        menuTexts.forEach(el => el.style.display = 'none');
                    } else {
                        // Expand
                        sidebar.classList.remove('w-20');
                        sidebar.classList.add('w-64');
                        logoContainer.classList.remove('justify-center');
                        logoContainer.classList.add('px-6');
                        // Use setTimeout to wait for width transition before showing text to prevent wrapping artifacts
                        setTimeout(() => {
                            menuTexts.forEach(el => el.style.display = '');
                        }, 150);
                    }
                });
            }
        });
    </script>
</body>
</html>
