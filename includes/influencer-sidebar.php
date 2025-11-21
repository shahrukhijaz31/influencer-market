<!-- Influencer Sidebar -->
<?php
// Get profile photo for sidebar
$sidebarProfilePhoto = '';
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT profile_photo FROM influencer_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $sidebarProfile = $stmt->fetch();
        $sidebarProfilePhoto = $sidebarProfile['profile_photo'] ?? '';
    } catch (PDOException $e) {
        // Ignore error
    }
}
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-header">
        <a href="../index.html">
            <img src="../assets/images/logo.png" alt="Casters.fi" class="sidebar-logo">
        </a>
    </div>

    <!-- User Profile Section -->
    <div class="sidebar-profile">
        <div class="sidebar-profile-avatar">
            <?php if (!empty($sidebarProfilePhoto)): ?>
                <img src="../uploads/profiles/<?php echo htmlspecialchars($sidebarProfilePhoto); ?>" alt="Profile">
            <?php else: ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </div>
        <div class="sidebar-profile-info">
            <span class="sidebar-profile-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <span class="sidebar-profile-role">Influencer</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="profile.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
        <a href="campaigns.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'campaigns.php' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Browse Campaigns</span>
        </a>
        <a href="applications.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane"></i>
            <span>My Applications</span>
        </a>

        <div class="sidebar-section">
            <p class="sidebar-section-title">Account</p>
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
                <?php if (!empty($sidebarProfilePhoto)): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($sidebarProfilePhoto); ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <span class="sidebar-user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            </div>
        </div>
    </div>
</aside>
