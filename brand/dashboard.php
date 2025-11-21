<?php
/**
 * Casters.fi - Brand Dashboard
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isBrand()) {
    redirect('login.html');
}

// Get brand data
try {
    $pdo = getDBConnection();

    // Get profile
    $stmt = $pdo->prepare("
        SELECT u.*, bp.*
        FROM users u
        LEFT JOIN brand_profiles bp ON u.id = bp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();

    // Get campaign stats
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM campaigns WHERE brand_id = ?
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $totalCampaigns = $stmt->fetchColumn() ?: 0;

    // Get active campaigns
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active FROM campaigns WHERE brand_id = ? AND status = 'active'
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $activeCampaigns = $stmt->fetchColumn() ?: 0;

    // Get total applications received
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as applications
        FROM campaign_applications ca
        JOIN campaigns c ON ca.campaign_id = c.id
        WHERE c.brand_id = ?
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $totalApplications = $stmt->fetchColumn() ?: 0;

    // Get recent campaigns
    $stmt = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = c.id) as application_count
        FROM campaigns c
        WHERE c.brand_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $recentCampaigns = $stmt->fetchAll();

    // Get all campaigns with their applications
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.status, c.created_at, c.hero_image, c.image,
               (SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = c.id) as application_count,
               (SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = c.id AND status = 'pending') as pending_count
        FROM campaigns c
        WHERE c.brand_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $allCampaigns = $stmt->fetchAll();

    // Get recent applications across all campaigns
    $stmt = $pdo->prepare("
        SELECT ca.*, c.name as campaign_name, c.id as campaign_id,
               ip.id as influencer_profile_id,
               u.first_name, u.last_name, u.profile_picture, u.email,
               ip.instagram_handle, ip.tiktok_handle, ip.followers_count
        FROM campaign_applications ca
        JOIN campaigns c ON ca.campaign_id = c.id
        JOIN influencer_profiles ip ON ca.influencer_id = ip.id
        JOIN users u ON ip.user_id = u.id
        WHERE c.brand_id = ?
        ORDER BY ca.applied_at DESC
        LIMIT 10
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $recentApplications = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dashboard-pro.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/brand-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php $pageTitle = 'Dashboard'; include '../includes/brand-topbar.php'; ?>

            <div class="dashboard-content">
                <!-- Welcome Section -->
                <div class="welcome-section-pro">
                    <div class="welcome-content-pro">
                        <div class="welcome-badge">
                            <i class="fas fa-sparkles"></i>
                            <span>Dashboard Overview</span>
                        </div>
                        <h1 class="welcome-title-pro">Welcome back, <?php echo htmlspecialchars($profile['company_name'] ?? 'Brand'); ?></h1>
                        <p class="welcome-subtitle-pro">Monitor your campaign performance and manage influencer collaborations.</p>

                        <?php
                        $subLevel = $profile['subscription_level'] ?? 'level1';
                        $subClass = $subLevel === 'level2' ? 'premium' : 'basic';
                        ?>
                        <div class="header-actions">
                            <a href="create-campaign.php" class="btn-primary-pro">
                                <i class="fas fa-plus-circle"></i>
                                <span>Create Campaign</span>
                            </a>
                            <div class="subscription-badge-pro <?php echo $subClass; ?>">
                                <i class="fas fa-crown"></i>
                                <span><?php echo $subLevel === 'level2' ? 'Premium' : 'Basic'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid Professional -->
                <div class="stats-grid-pro">
                    <div class="stat-card-pro stat-card-rating">
                        <div class="stat-header-pro">
                            <div class="stat-icon-pro">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>Excellent</span>
                            </div>
                        </div>
                        <div class="stat-body-pro">
                            <div class="stat-value-pro"><?php echo number_format($profile['rating'] ?? 0, 1); ?></div>
                            <div class="stat-label-pro">Brand Rating</div>
                            <div class="stat-progress-bar">
                                <div class="stat-progress-fill rating-fill" style="width: <?php echo ($profile['rating'] ?? 0) * 20; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card-pro stat-card-campaigns">
                        <div class="stat-header-pro">
                            <div class="stat-icon-pro">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div class="stat-trend neutral">
                                <i class="fas fa-chart-line"></i>
                                <span>Total</span>
                            </div>
                        </div>
                        <div class="stat-body-pro">
                            <div class="stat-value-pro"><?php echo $totalCampaigns; ?></div>
                            <div class="stat-label-pro">Total Campaigns</div>
                            <div class="stat-meta-pro">All time campaigns created</div>
                        </div>
                    </div>

                    <div class="stat-card-pro stat-card-active">
                        <div class="stat-header-pro">
                            <div class="stat-icon-pro">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div class="stat-trend positive">
                                <i class="fas fa-check-circle"></i>
                                <span>Live</span>
                            </div>
                        </div>
                        <div class="stat-body-pro">
                            <div class="stat-value-pro"><?php echo $activeCampaigns; ?></div>
                            <div class="stat-label-pro">Active Campaigns</div>
                            <div class="stat-meta-pro">Currently running campaigns</div>
                        </div>
                    </div>

                    <div class="stat-card-pro stat-card-applications">
                        <div class="stat-header-pro">
                            <div class="stat-icon-pro">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-trend info">
                                <i class="fas fa-inbox"></i>
                                <span>Received</span>
                            </div>
                        </div>
                        <div class="stat-body-pro">
                            <div class="stat-value-pro"><?php echo $totalApplications; ?></div>
                            <div class="stat-label-pro">Applications</div>
                            <div class="stat-meta-pro">Influencer responses</div>
                        </div>
                    </div>
                </div>

                <div class="dashboard-grid-pro">
                    <div class="dashboard-main-col-pro">
                        <!-- Quick Actions Professional -->
                        <div class="dashboard-card-pro">
                            <div class="card-header-pro">
                                <div class="header-title-group">
                                    <h3 class="card-title-pro">Quick Actions</h3>
                                    <p class="card-description-pro">Manage your campaigns efficiently</p>
                                </div>
                            </div>
                            <div class="card-body-pro">
                                <div class="quick-actions-grid">
                                    <a href="campaigns.php" class="quick-action-card">
                                        <div class="action-icon-circle campaigns-color">
                                            <i class="fas fa-bullhorn"></i>
                                        </div>
                                        <div class="action-details">
                                            <h4 class="action-title-pro">My Campaigns</h4>
                                            <p class="action-desc-pro">View and manage all campaigns</p>
                                        </div>
                                        <div class="action-badge"><?php echo $totalCampaigns; ?></div>
                                    </a>

                                    <a href="profile.php" class="quick-action-card">
                                        <div class="action-icon-circle profile-color">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="action-details">
                                            <h4 class="action-title-pro">Brand Profile</h4>
                                            <p class="action-desc-pro">Update company information</p>
                                        </div>
                                        <i class="fas fa-chevron-right action-chevron"></i>
                                    </a>

                                    <?php if ($subLevel === 'level2'): ?>
                                    <a href="influencers.php" class="quick-action-card">
                                        <div class="action-icon-circle influencers-color">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="action-details">
                                            <h4 class="action-title-pro">Find Influencers</h4>
                                            <p class="action-desc-pro">Discover and connect</p>
                                        </div>
                                        <i class="fas fa-chevron-right action-chevron"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="subscription.php" class="quick-action-card premium-highlight">
                                        <div class="action-icon-circle premium-color">
                                            <i class="fas fa-crown"></i>
                                        </div>
                                        <div class="action-details">
                                            <h4 class="action-title-pro">Upgrade Plan</h4>
                                            <p class="action-desc-pro">Unlock premium features</p>
                                        </div>
                                        <i class="fas fa-chevron-right action-chevron"></i>
                                    </a>
                                    <?php endif; ?>

                                    <a href="messages.php" class="quick-action-card">
                                        <div class="action-icon-circle messages-color">
                                            <i class="fas fa-comments"></i>
                                        </div>
                                        <div class="action-details">
                                            <h4 class="action-title-pro">Messages</h4>
                                            <p class="action-desc-pro">Chat with influencers</p>
                                        </div>
                                        <i class="fas fa-chevron-right action-chevron"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Campaigns Professional -->
                        <div class="dashboard-card-pro campaigns-section">
                            <div class="card-header-pro">
                                <div class="header-title-group">
                                    <h3 class="card-title-pro">Recent Campaigns</h3>
                                    <p class="card-description-pro">Track your latest campaign activities</p>
                                </div>
                                <a href="campaigns.php" class="btn-text-pro">
                                    <span>View All</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="card-body-pro">
                                <?php if (empty($recentCampaigns)): ?>
                                <div class="empty-state-pro">
                                    <div class="empty-illustration">
                                        <i class="fas fa-rocket"></i>
                                    </div>
                                    <h3 class="empty-title-pro">Launch Your First Campaign</h3>
                                    <p class="empty-text-pro">Start connecting with influencers and grow your brand reach.</p>
                                    <a href="create-campaign.php" class="btn-primary-pro">
                                        <i class="fas fa-plus-circle"></i>
                                        <span>Create Campaign</span>
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="campaign-list-pro">
                                    <?php foreach ($recentCampaigns as $campaign): ?>
                                    <div class="campaign-item-pro">
                                        <div class="campaign-icon-pro">
                                            <i class="fas fa-bullhorn"></i>
                                        </div>
                                        <div class="campaign-details-pro">
                                            <h4 class="campaign-title-pro"><?php echo htmlspecialchars($campaign['name']); ?></h4>
                                            <div class="campaign-stats-pro">
                                                <span class="stat-item-pro">
                                                    <i class="fas fa-user-check"></i>
                                                    <?php echo $campaign['application_count']; ?> Applications
                                                </span>
                                            </div>
                                        </div>
                                        <div class="campaign-status-pro status-<?php echo $campaign['status']; ?>">
                                            <span class="status-dot"></span>
                                            <?php echo ucfirst($campaign['status']); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-sidebar-col-pro">
                        <!-- Performance Card Professional -->
                        <div class="dashboard-card-pro performance-card-pro">
                            <div class="card-header-pro">
                                <div class="header-title-group">
                                    <h3 class="card-title-pro">Performance Overview</h3>
                                </div>
                            </div>
                            <div class="card-body-pro">
                                <div class="performance-score">
                                    <div class="score-circle">
                                        <svg width="120" height="120" viewBox="0 0 120 120">
                                            <circle cx="60" cy="60" r="54" fill="none" stroke="#f3f4f6" stroke-width="8"/>
                                            <circle cx="60" cy="60" r="54" fill="none" stroke="url(#gradient)" stroke-width="8"
                                                    stroke-dasharray="<?php echo (($profile['rating'] ?? 0) / 5) * 339.292; ?> 339.292"
                                                    stroke-linecap="round" transform="rotate(-90 60 60)"/>
                                            <defs>
                                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                    <stop offset="0%" style="stop-color:#e879f9;stop-opacity:1" />
                                                    <stop offset="100%" style="stop-color:#67e8f9;stop-opacity:1" />
                                                </linearGradient>
                                            </defs>
                                        </svg>
                                        <div class="score-value">
                                            <div class="score-number"><?php echo number_format($profile['rating'] ?? 0, 1); ?></div>
                                            <div class="score-label">Rating</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="performance-stars">
                                    <?php
                                    $rating = $profile['rating'] ?? 0;
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star star-filled"></i>';
                                        } elseif ($i - 0.5 <= $rating) {
                                            echo '<i class="fas fa-star-half-alt star-filled"></i>';
                                        } else {
                                            echo '<i class="far fa-star star-empty"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p class="performance-text">Based on <?php echo $profile['total_campaigns'] ?? 0; ?> completed campaigns</p>
                            </div>
                        </div>

                        <!-- Subscription Card Professional -->
                        <div class="dashboard-card-pro subscription-card-pro <?php echo $subClass; ?>">
                            <div class="subscription-content-pro">
                                <div class="subscription-header-pro">
                                    <div class="subscription-icon-pro">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div class="subscription-level">
                                        <h4><?php echo $subLevel === 'level2' ? 'Premium' : 'Basic'; ?></h4>
                                        <p>Plan</p>
                                    </div>
                                </div>
                                <div class="subscription-features">
                                    <?php if ($subLevel === 'level2'): ?>
                                    <div class="feature-item active">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Unlimited Campaigns</span>
                                    </div>
                                    <div class="feature-item active">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Priority Support</span>
                                    </div>
                                    <div class="feature-item active">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Advanced Analytics</span>
                                    </div>
                                    <?php else: ?>
                                    <div class="feature-item">
                                        <i class="fas fa-times-circle"></i>
                                        <span>Limited to 5 campaigns</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="fas fa-times-circle"></i>
                                        <span>Basic support only</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($subLevel !== 'level2'): ?>
                                <a href="subscription.php" class="btn-upgrade-pro">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>Upgrade to Premium</span>
                                </a>
                                <?php else: ?>
                                <div class="subscription-status-active">
                                    <i class="fas fa-shield-check"></i>
                                    <span>Active Subscription</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaigns & Applications Section -->
                <div class="campaigns-applications-section">
                    <!-- All Campaigns -->
                    <div class="dashboard-card-pro">
                        <div class="card-header-pro">
                            <div class="header-title-group">
                                <h3 class="card-title-pro">All Campaigns</h3>
                                <p class="card-description-pro">Overview of all your campaigns and their applications</p>
                            </div>
                            <a href="create-campaign.php" class="btn-primary-pro btn-sm">
                                <i class="fas fa-plus"></i>
                                <span>New Campaign</span>
                            </a>
                        </div>
                        <div class="card-body-pro">
                            <?php if (empty($allCampaigns)): ?>
                            <div class="empty-state-pro">
                                <div class="empty-illustration">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h3 class="empty-title-pro">No Campaigns Yet</h3>
                                <p class="empty-text-pro">Create your first campaign to start receiving applications from influencers.</p>
                            </div>
                            <?php else: ?>
                            <div class="campaigns-table-wrapper">
                                <table class="campaigns-table">
                                    <thead>
                                        <tr>
                                            <th>Campaign</th>
                                            <th>Status</th>
                                            <th>Applications</th>
                                            <th>Pending</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allCampaigns as $campaign):
                                            $campaignImage = $campaign['hero_image'] ?? $campaign['image'] ?? '';
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="campaign-cell">
                                                    <?php if ($campaignImage): ?>
                                                    <img src="<?php echo htmlspecialchars($campaignImage); ?>" alt="" class="campaign-thumb">
                                                    <?php else: ?>
                                                    <div class="campaign-thumb-placeholder">
                                                        <i class="fas fa-bullhorn"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                    <span class="campaign-name"><?php echo htmlspecialchars($campaign['name']); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $campaign['status']; ?>">
                                                    <?php echo ucfirst($campaign['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="applications-count"><?php echo $campaign['application_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($campaign['pending_count'] > 0): ?>
                                                <span class="pending-badge"><?php echo $campaign['pending_count']; ?> new</span>
                                                <?php else: ?>
                                                <span class="no-pending">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="date-cell"><?php echo date('M d, Y', strtotime($campaign['created_at'])); ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="campaign-applications.php?id=<?php echo $campaign['id']; ?>" class="btn-action" title="View Applications">
                                                        <i class="fas fa-users"></i>
                                                    </a>
                                                    <a href="edit-campaign.php?id=<?php echo $campaign['id']; ?>" class="btn-action" title="Edit Campaign">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Applications -->
                    <div class="dashboard-card-pro">
                        <div class="card-header-pro">
                            <div class="header-title-group">
                                <h3 class="card-title-pro">Recent Applications</h3>
                                <p class="card-description-pro">Latest applications from influencers</p>
                            </div>
                        </div>
                        <div class="card-body-pro">
                            <?php if (empty($recentApplications)): ?>
                            <div class="empty-state-pro">
                                <div class="empty-illustration">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3 class="empty-title-pro">No Applications Yet</h3>
                                <p class="empty-text-pro">Once influencers apply to your campaigns, they will appear here.</p>
                            </div>
                            <?php else: ?>
                            <div class="applications-list">
                                <?php foreach ($recentApplications as $app):
                                    $finlandTz = new DateTimeZone('Europe/Helsinki');
                                    $appliedDate = new DateTime($app['applied_at']);
                                    $appliedDate->setTimezone($finlandTz);
                                ?>
                                <div class="application-item">
                                    <div class="applicant-avatar">
                                        <?php if ($app['profile_picture']): ?>
                                        <img src="<?php echo htmlspecialchars($app['profile_picture']); ?>" alt="">
                                        <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?php echo strtoupper(substr($app['first_name'], 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="applicant-info">
                                        <div class="applicant-name">
                                            <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                        </div>
                                        <div class="applicant-meta">
                                            <?php if ($app['instagram_handle']): ?>
                                            <span class="meta-item"><i class="fab fa-instagram"></i> @<?php echo htmlspecialchars($app['instagram_handle']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($app['followers_count']): ?>
                                            <span class="meta-item"><i class="fas fa-users"></i> <?php echo number_format($app['followers_count']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="application-campaign">
                                        <span class="campaign-label">Applied to</span>
                                        <span class="campaign-name-link"><?php echo htmlspecialchars($app['campaign_name']); ?></span>
                                    </div>
                                    <div class="application-status-time">
                                        <span class="status-badge status-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                        <span class="applied-time"><?php echo $appliedDate->format('M d, H:i'); ?></span>
                                    </div>
                                    <div class="application-actions">
                                        <a href="application-detail.php?id=<?php echo $app['id']; ?>" class="btn-view-app">
                                            View <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
    /* Campaigns & Applications Section Styles */
    .campaigns-applications-section {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .btn-primary-pro.btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    /* Campaigns Table */
    .campaigns-table-wrapper {
        overflow-x: auto;
    }

    .campaigns-table {
        width: 100%;
        border-collapse: collapse;
    }

    .campaigns-table th,
    .campaigns-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .campaigns-table th {
        font-weight: 600;
        color: #666;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #fafafa;
    }

    .campaigns-table tbody tr:hover {
        background: #fafafa;
    }

    .campaign-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .campaign-thumb {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
    }

    .campaign-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
    }

    .campaign-name {
        font-weight: 500;
        color: #111;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-badge.status-active {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .status-badge.status-pending {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }

    .status-badge.status-completed {
        background: rgba(107, 114, 128, 0.1);
        color: #4b5563;
    }

    .status-badge.status-draft {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }

    .status-badge.status-accepted {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .status-badge.status-rejected {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    .applications-count {
        font-weight: 600;
        color: #111;
    }

    .pending-badge {
        background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        color: white;
        padding: 0.25rem 0.625rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .no-pending {
        color: #999;
    }

    .date-cell {
        color: #666;
        font-size: 0.875rem;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f5f5f5;
        color: #666;
        transition: all 0.2s ease;
    }

    .btn-action:hover {
        background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        color: white;
    }

    /* Applications List */
    .applications-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .application-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #fafafa;
        border-radius: 12px;
        transition: all 0.2s ease;
    }

    .application-item:hover {
        background: #f0f0f0;
    }

    .applicant-avatar img,
    .applicant-avatar .avatar-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-placeholder {
        background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.125rem;
    }

    .applicant-info {
        flex: 1;
        min-width: 0;
    }

    .applicant-name {
        font-weight: 600;
        color: #111;
        margin-bottom: 0.25rem;
    }

    .applicant-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.8125rem;
        color: #666;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .meta-item i {
        color: #e879f9;
    }

    .application-campaign {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        min-width: 150px;
    }

    .campaign-label {
        font-size: 0.75rem;
        color: #999;
    }

    .campaign-name-link {
        font-weight: 500;
        color: #111;
        font-size: 0.875rem;
    }

    .application-status-time {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.25rem;
        min-width: 100px;
    }

    .applied-time {
        font-size: 0.75rem;
        color: #999;
    }

    .btn-view-app {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        background: white;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #111;
        transition: all 0.2s ease;
    }

    .btn-view-app:hover {
        background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        border-color: transparent;
        color: white;
    }

    @media (max-width: 768px) {
        .application-item {
            flex-wrap: wrap;
        }

        .application-campaign,
        .application-status-time {
            flex: 1;
            min-width: 100%;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }

        .campaigns-table th:nth-child(4),
        .campaigns-table td:nth-child(4),
        .campaigns-table th:nth-child(5),
        .campaigns-table td:nth-child(5) {
            display: none;
        }
    }
    </style>

<?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
