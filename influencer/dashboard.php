<?php
/**
 * Casters.fi - Influencer Dashboard
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isInfluencer()) {
    redirect('login.html');
}

// Get influencer data
try {
    $pdo = getDBConnection();

    // Get profile
    $stmt = $pdo->prepare("
        SELECT u.*, ip.*
        FROM users u
        LEFT JOIN influencer_profiles ip ON u.id = ip.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();

    // Get stats
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM campaign_applications WHERE influencer_id = ?
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $totalApplications = $stmt->fetchColumn() ?: 0;

    // Get applications in process (pending/under_review)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM campaign_applications
        WHERE influencer_id = ? AND status IN ('pending', 'under_review')
    ");
    $stmt->execute([$profile['id'] ?? 0]);
    $applicationsInProcess = $stmt->fetchColumn() ?: 0;

    // Get total campaigns in platform
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM campaigns WHERE status = 'active'");
    $stmt->execute();
    $totalCampaignsInPlatform = $stmt->fetchColumn() ?: 0;

    // Check profile completion
    $profileComplete = true;
    $missingItems = [];

    // Check for social media accounts
    $hasSocialMedia = !empty($profile['instagram_username']) ||
                      !empty($profile['tiktok_username']) ||
                      !empty($profile['youtube_username']) ||
                      !empty($profile['facebook_url']);
    if (!$hasSocialMedia) {
        $profileComplete = false;
        $missingItems[] = 'Social media accounts';
    }

    // Check for bio
    if (empty($profile['bio'])) {
        $profileComplete = false;
        $missingItems[] = 'Bio';
    }

    // Check for categories
    if (empty($profile['categories'])) {
        $profileComplete = false;
        $missingItems[] = 'Categories';
    }

    // Check for profile photo
    if (empty($profile['profile_photo'])) {
        $profileComplete = false;
        $missingItems[] = 'Profile photo';
    }

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
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern Influencer Dashboard Design */
        .dashboard-content {
            padding: 1.5rem;
        }

        /* Welcome Header with Gradient */
        .welcome-header {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .welcome-header-content {
            position: relative;
            z-index: 2;
        }

        .welcome-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }

        .welcome-header p {
            font-size: 1rem;
            margin: 0;
            opacity: 0.95;
        }

        /* Stats Grid - Modern Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
            border-color: rgba(232, 121, 249, 0.3);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card-header {
            margin-bottom: 0.75rem;
        }

        .stat-card-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 1.25rem;
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat-card-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Search Campaign Card */
        .search-campaign-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        }

        .search-campaign-card .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .search-campaign-card .card-title i {
            width: 32px;
            height: 32px;
            background: var(--primary-gradient);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
        }

        .search-form {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .search-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.9375rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(232, 121, 249, 0.1);
        }

        .search-btn {
            padding: 0.875rem 2rem;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(232, 121, 249, 0.3);
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(232, 121, 249, 0.4);
        }

        .filter-chips {
            display: flex;
            gap: 0.625rem;
            flex-wrap: wrap;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.125rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 25px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .filter-chip:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(232, 121, 249, 0.25);
        }

        .filter-chip i {
            font-size: 0.75rem;
        }

        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .quick-action-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .quick-action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: transparent;
        }

        .quick-action-card:hover::before {
            opacity: 1;
        }

        .quick-action-card:hover .quick-action-icon,
        .quick-action-card:hover .quick-action-label {
            color: white;
        }

        .quick-action-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 1.5rem;
            margin: 0 auto 0.875rem;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .quick-action-label {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-primary);
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        /* Rating Card */
        .rating-card {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            text-align: center;
        }

        .rating-card h3 {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            color: var(--text-primary);
        }

        .rating-value {
            font-size: 3rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 0.75rem;
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 0.375rem;
            margin-bottom: 1rem;
        }

        .rating-stars i {
            color: #e879f9;
            font-size: 1.25rem;
        }

        .rating-description {
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }

        /* Profile Incomplete Banner */
        .profile-incomplete-banner {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(249, 115, 22, 0.1) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .banner-icon {
            width: 44px;
            height: 44px;
            background: rgba(239, 68, 68, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ef4444;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .banner-content {
            flex: 1;
        }

        .banner-content strong {
            display: block;
            font-size: 0.9375rem;
            color: #dc2626;
            margin-bottom: 0.25rem;
        }

        .banner-content p {
            font-size: 0.8125rem;
            color: #7f1d1d;
            margin: 0;
        }

        .banner-content .btn {
            padding: 0.625rem 1.5rem;
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;
            }

            .welcome-header {
                padding: 1.5rem;
            }

            .welcome-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/influencer-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php $pageTitle = 'Dashboard'; include '../includes/influencer-topbar.php'; ?>

            <div class="dashboard-content">
                <!-- Welcome Header -->
                <div class="welcome-header">
                    <div class="welcome-header-content">
                        <h1>Welcome back, <?php echo htmlspecialchars($profile['first_name'] ?? 'User'); ?>! 👋</h1>
                        <p>Browse available campaigns and collaborate with amazing brands</p>
                    </div>
                </div>

                <?php if (!$profileComplete): ?>
                <!-- Profile Incomplete Banner -->
                <div class="profile-incomplete-banner">
                    <div class="banner-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="banner-content">
                        <strong>Complete Your Profile</strong>
                        <p>Your profile is missing: <?php echo implode(', ', $missingItems); ?>. Complete your profile to be visible to brands and apply for campaigns.</p>
                    </div>
                    <a href="profile.php" class="btn btn-primary btn-sm">Complete Profile</a>
                </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo number_format($profile['rating'] ?? 0, 1); ?></div>
                        <div class="stat-card-label">Your Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $totalCampaignsInPlatform; ?></div>
                        <div class="stat-card-label">Available Campaigns</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $totalApplications; ?></div>
                        <div class="stat-card-label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <div class="stat-card-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-card-value"><?php echo $applicationsInProcess; ?></div>
                        <div class="stat-card-label">In Process</div>
                    </div>
                </div>

                <!-- Search Campaigns Section -->
                <div class="search-campaign-card">
                    <h3 class="card-title">
                        <i class="fas fa-search"></i>
                        Search for Campaigns
                    </h3>
                    <form action="campaigns.php" method="GET" class="search-form">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search campaigns by title, category, or brand...">
                        </div>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                    <div class="filter-chips">
                        <a href="campaigns.php" class="filter-chip">
                            <i class="fas fa-bullhorn"></i> All Campaigns
                        </a>
                        <a href="campaigns.php?filter=new" class="filter-chip">
                            <i class="fas fa-sparkles"></i> New
                        </a>
                        <a href="campaigns.php?filter=urgent" class="filter-chip">
                            <i class="fas fa-clock"></i> Urgent
                        </a>
                        <a href="campaigns.php?filter=high_budget" class="filter-chip">
                            <i class="fas fa-euro-sign"></i> High Budget
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions-grid">
                    <a href="campaigns.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <div class="quick-action-label">Find Campaigns</div>
                    </a>
                    <a href="profile.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div class="quick-action-label">Edit Profile</div>
                    </a>
                    <a href="applications.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="quick-action-label">My Applications</div>
                    </a>
                    <a href="settings.php" class="quick-action-card">
                        <div class="quick-action-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="quick-action-label">Settings</div>
                    </a>
                </div>

                <!-- Your Rating -->
                <div class="rating-card">
                    <h3>Your Rating</h3>
                    <div class="rating-value"><?php echo number_format($profile['rating'] ?? 0, 1); ?></div>
                    <div class="rating-stars">
                        <?php
                        $rating = $profile['rating'] ?? 0;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="fas fa-star"></i>';
                            } elseif ($i - 0.5 <= $rating) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p class="rating-description">
                        Based on <?php echo $profile['total_campaigns'] ?? 0; ?> completed campaigns
                    </p>
                </div>
            </div>
        </main>
    </div>

<?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
