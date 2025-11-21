    <!-- Chat Widget CSS -->
    <link rel="stylesheet" href="../assets/css/chat.css">

    <!-- Scripts -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/chat.js"></script>
    <script>
        const sidebarToggle = document.querySelector('.mobile-sidebar-toggle');
        const sidebar = document.querySelector('.dashboard-sidebar');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
        }

        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    </script>
