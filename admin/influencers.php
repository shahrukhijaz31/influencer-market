<?php
/**
 * Casters.fi - Admin: Influencers List
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.html');
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'influencer'");
        $stmt->execute([$_GET['delete']]);
        $success = "Influencer deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting influencer: " . $e->getMessage();
    }
}

// Get all influencers
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT u.*, ip.creator_type, ip.instagram_followers, ip.tiktok_followers,
               ip.youtube_followers, ip.rating, ip.total_campaigns
        FROM users u
        LEFT JOIN influencer_profiles ip ON u.id = ip.user_id
        WHERE u.user_type = 'influencer'
        ORDER BY u.created_at DESC
    ");
    $influencers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Influencers - Admin - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <a href="../index.html">
                    <img src="../assets/images/logo.png" alt="Casters.fi" class="sidebar-logo">
                </a>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <div class="sidebar-section">
                    <p class="sidebar-section-title">Users</p>
                </div>
                <a href="influencers.php" class="sidebar-link active">
                    <i class="fas fa-users"></i>
                    <span>Influencers</span>
                </a>
                <a href="brands.php" class="sidebar-link">
                    <i class="fas fa-building"></i>
                    <span>Brands</span>
                </a>
                <a href="managers.php" class="sidebar-link">
                    <i class="fas fa-user-tie"></i>
                    <span>Managers</span>
                </a>

                <div class="sidebar-section">
                    <p class="sidebar-section-title">Content</p>
                </div>
                <a href="campaigns.php" class="sidebar-link">
                    <i class="fas fa-bullhorn"></i>
                    <span>Campaigns</span>
                </a>

                <div class="sidebar-section">
                    <p class="sidebar-section-title">System</p>
                </div>
                <a href="../api/logout.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button class="mobile-sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="topbar-title">Influencers</h1>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (isset($success)): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-error" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">All Influencers (<?php echo count($influencers); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($influencers)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>No influencers yet</h3>
                            <p>Influencers will appear here when they register.</p>
                        </div>
                        <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Followers</th>
                                    <th>Rating</th>
                                    <th>Campaigns</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($influencers as $inf): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="sidebar-user-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <?php echo htmlspecialchars($inf['first_name'] . ' ' . $inf['last_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($inf['email']); ?></td>
                                    <td>
                                        <span class="category-tag">
                                            <?php echo ucfirst(str_replace('_', ' ', $inf['creator_type'] ?? 'influencer')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $total = ($inf['instagram_followers'] ?? 0) + ($inf['tiktok_followers'] ?? 0) + ($inf['youtube_followers'] ?? 0);
                                        echo number_format($total);
                                        ?>
                                    </td>
                                    <td>
                                        <div class="rating-display">
                                            <i class="fas fa-star" style="color: var(--warning);"></i>
                                            <span><?php echo number_format($inf['rating'] ?? 0, 1); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $inf['total_campaigns'] ?? 0; ?></td>
                                    <td>
                                        <?php if ($inf['is_active']): ?>
                                        <span class="campaign-status status-active">Active</span>
                                        <?php else: ?>
                                        <span class="campaign-status status-completed">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="influencer-edit.php?id=<?php echo $inf['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 12px;">Edit</a>
                                        <a href="influencers.php?delete=<?php echo $inf['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 12px; color: #ef4444;" onclick="return confirm('Are you sure you want to delete this influencer?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
