<!-- Admin Sidebar -->
<aside class="dashboard-sidebar">
    <div class="sidebar-header">
        <a href="../index.html">
            <img src="../assets/images/logo.png" alt="Casters.fi" class="sidebar-logo">
        </a>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-section">
            <p class="sidebar-section-title">Users</p>
        </div>
        <a href="influencers.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'influencers.php' || basename($_SERVER['PHP_SELF']) == 'influencer-edit.php' || basename($_SERVER['PHP_SELF']) == 'influencer-view.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Influencers</span>
        </a>
        <a href="brands.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' || basename($_SERVER['PHP_SELF']) == 'brand-add.php' || basename($_SERVER['PHP_SELF']) == 'brand-edit.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i>
            <span>Brands</span>
        </a>
        <a href="managers.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'managers.php' || basename($_SERVER['PHP_SELF']) == 'manager-edit.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span>Managers</span>
        </a>

        <div class="sidebar-section">
            <p class="sidebar-section-title">Content</p>
        </div>
        <a href="campaigns.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'campaigns.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Campaigns</span>
        </a>
        <a href="create-campaign.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'create-campaign.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Create Campaign</span>
        </a>
        <a href="categories.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>

        <div class="sidebar-section">
            <p class="sidebar-section-title">System</p>
        </div>
        <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="../api/logout.php" class="sidebar-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span class="sidebar-user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            </div>
        </div>
    </div>
</aside>
