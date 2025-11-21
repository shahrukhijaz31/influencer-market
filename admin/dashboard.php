<?php
/**
 * Casters.fi - Admin Dashboard
 */

require_once '../includes/config.php';

// Check if logged in and is admin or manager
if (!isLoggedIn() || !isAdminOrManager()) {
    redirect('login.html');
}

// Get stats
try {
    $pdo = getDBConnection();

    // Total influencers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'influencer'");
    $totalInfluencers = $stmt->fetchColumn();

    // Total brands
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'brand'");
    $totalBrands = $stmt->fetchColumn();

    // Total campaigns
    $stmt = $pdo->query("SELECT COUNT(*) FROM campaigns");
    $totalCampaigns = $stmt->fetchColumn();

    // Recent registrations
    $stmt = $pdo->query("
        SELECT id, email, user_type, first_name, last_name, country, created_at
        FROM users
        WHERE user_type = 'influencer'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll();

    // Active campaigns
    $stmt = $pdo->query("
        SELECT c.*, bp.company_name
        FROM campaigns c
        LEFT JOIN brand_profiles bp ON c.brand_id = bp.id
        WHERE c.status = 'active'
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $activeCampaigns = $stmt->fetchAll();

    // Managers
    $stmt = $pdo->query("
        SELECT id, email, first_name, last_name, user_type, created_at
        FROM users
        WHERE user_type IN ('admin', 'manager')
        ORDER BY created_at DESC
    ");
    $managers = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php $pageTitle = 'Admin Dashboard'; include '../includes/admin-topbar.php'; ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $totalInfluencers ?? 0; ?></div>
                        <div class="stat-card-label">Total Influencers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $totalBrands ?? 0; ?></div>
                        <div class="stat-card-label">Active Brands</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $totalCampaigns ?? 0; ?></div>
                        <div class="stat-card-label">Total Campaigns</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo count($managers ?? []); ?></div>
                        <div class="stat-card-label">Managers</div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <!-- Main Content Column -->
                    <div>
                        <!-- Recent Registrations -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Influencer Registrations</h3>
                                <a href="influencers.php" class="btn btn-outline">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentUsers)): ?>
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
                                            <th>Country</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div class="sidebar-user-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['country'] ?? '-'); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="influencer-view.php?id=<?php echo $user['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 12px;">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Active Campaigns -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Active Campaigns</h3>
                                <a href="campaigns.php" class="btn btn-outline">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($activeCampaigns)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                    <h3>No active campaigns</h3>
                                    <p>Campaigns will appear here when created.</p>
                                </div>
                                <?php else: ?>
                                <ul class="campaign-list">
                                    <?php foreach ($activeCampaigns as $campaign): ?>
                                    <li class="campaign-item">
                                        <div class="campaign-image">
                                            <i class="fas fa-bullhorn"></i>
                                        </div>
                                        <div class="campaign-info">
                                            <div class="campaign-name"><?php echo htmlspecialchars($campaign['name']); ?></div>
                                            <div class="campaign-meta">
                                                <?php echo htmlspecialchars($campaign['company_name'] ?? 'Unknown Brand'); ?> &bull;
                                                <i class="fas fa-users"></i> <?php echo $campaign['influencers_needed']; ?> influencers
                                            </div>
                                        </div>
                                        <span class="campaign-status status-active">Active</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Managers -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Managers</h3>
                                <a href="managers.php?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Manager
                                </a>
                            </div>
                            <div class="card-body">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($managers as $manager): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div class="sidebar-user-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                                        <i class="fas fa-user-tie"></i>
                                                    </div>
                                                    <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($manager['email']); ?></td>
                                            <td><?php echo ucfirst($manager['user_type']); ?></td>
                                            <td>
                                                <a href="manager-edit.php?id=<?php echo $manager['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 12px;">Edit</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Column -->
                    <div>
                        <!-- Quick Actions -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="quick-actions">
                                    <a href="brands.php?action=add" class="quick-action">
                                        <i class="fas fa-building"></i>
                                        <span>Add Brand</span>
                                    </a>
                                    <a href="create-campaign.php" class="quick-action">
                                        <i class="fas fa-bullhorn"></i>
                                        <span>New Campaign</span>
                                    </a>
                                    <a href="managers.php?action=add" class="quick-action">
                                        <i class="fas fa-user-plus"></i>
                                        <span>Add Manager</span>
                                    </a>
                                    <a href="categories.php" class="quick-action">
                                        <i class="fas fa-tags"></i>
                                        <span>Categories</span>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h3 class="card-title">System Info</h3>
                            </div>
                            <div class="card-body">
                                <div style="font-size: var(--font-size-sm);">
                                    <p><strong>Logged in as:</strong><br><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                                    <p style="margin-top: 10px;"><strong>Session started:</strong><br><?php echo date('M j, Y g:i A', $_SESSION['logged_in_at']); ?></p>
                                    <p style="margin-top: 10px;"><strong>PHP Version:</strong><br><?php echo phpversion(); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

<?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
