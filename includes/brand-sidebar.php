<?php
$subLevel = $profile['subscription_level'] ?? 'level1';
?>
<!-- Brand Sidebar -->
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
        <a href="profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-building"></i>
            <span>Company Profile</span>
        </a>
        <a href="campaigns.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'campaigns.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span>My Campaigns</span>
        </a>
        <a href="create-campaign.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'create-campaign.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Create Campaign</span>
        </a>
        <?php if ($subLevel === 'level2'): ?>
        <a href="influencers.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'influencers.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Browse Influencers</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-section">
            <p class="sidebar-section-title">Account</p>
        </div>
        <a href="subscription.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'subscription.php' ? 'active' : ''; ?>">
            <i class="fas fa-crown"></i>
            <span>Subscription</span>
        </a>
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
                <i class="fas fa-building"></i>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($profile['company_name'] ?? $_SESSION['user_name']); ?></span>
                <span class="sidebar-user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            </div>
        </div>
    </div>
</aside>
