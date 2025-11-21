<!-- Admin Top Bar -->
<header class="dashboard-topbar">
    <div class="topbar-left">
        <button class="mobile-sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="topbar-title"><?php echo $pageTitle ?? 'Admin Dashboard'; ?></h1>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search...">
        </div>
        <div class="topbar-actions">
            <div class="topbar-icon">
                <i class="fas fa-bell"></i>
            </div>
        </div>
    </div>
</header>
