<?php
/**
 * Casters.fi - Brand Profile
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isBrand()) {
    redirect('login.html');
}

// Get brand data
try {
    $pdo = getDBConnection();

    // Get complete profile
    $stmt = $pdo->prepare("
        SELECT u.*, bp.*,
               u.id as user_id,
               bp.id as brand_profile_id
        FROM users u
        LEFT JOIN brand_profiles bp ON u.id = bp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $_SESSION['error'] = 'Profile not found';
        redirect('dashboard.php');
    }

    // Get campaign statistics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_campaigns,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_campaigns,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_campaigns
        FROM campaigns
        WHERE brand_id = ?
    ");
    $stmt->execute([$profile['user_id']]);
    $campaignStats = $stmt->fetch();

    // Get total applications received
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_applications
        FROM campaign_applications ca
        JOIN campaigns c ON ca.campaign_id = c.id
        WHERE c.brand_id = ?
    ");
    $stmt->execute([$profile['user_id']]);
    $applicationsStats = $stmt->fetch();

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand Profile - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/profile-modern.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/brand-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php $pageTitle = 'Brand Profile'; include '../includes/brand-topbar.php'; ?>

            <div class="dashboard-content">
                <!-- Modern Profile Header -->
                <div class="profile-header-modern">
                    <div class="profile-cover-gradient"></div>

                    <div class="profile-main-card">
                        <div class="profile-header-content">
                            <div class="profile-avatar-modern">
                                <?php if ($profile['profile_picture']): ?>
                                    <img src="../<?php echo htmlspecialchars($profile['profile_picture']); ?>" alt="Profile">
                                <?php else: ?>
                                    <div class="profile-avatar-placeholder">
                                        <i class="fas fa-building"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="profile-info-modern">
                                <div class="profile-title-row">
                                    <h1 class="profile-company-title"><?php echo htmlspecialchars($profile['company_name']); ?></h1>
                                    <span class="subscription-badge-modern <?php echo $profile['subscription_level'] === 'level2' ? 'premium' : 'basic'; ?>">
                                        <i class="fas fa-crown"></i>
                                        <?php echo $profile['subscription_level'] === 'level2' ? 'Premium' : 'Basic'; ?>
                                    </span>
                                </div>

                                <div class="profile-meta-row">
                                    <div class="meta-item-modern">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($profile['email']); ?>
                                    </div>
                                    <div class="meta-item-modern">
                                        <i class="fas fa-calendar"></i>
                                        Joined <?php echo date('M Y', strtotime($profile['created_at'])); ?>
                                    </div>
                                    <div class="profile-rating-modern">
                                        <div class="rating-number"><?php echo number_format($profile['rating'] ?? 0, 1); ?></div>
                                        <div class="rating-stars-modern">
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
                                    </div>
                                </div>

                                <div class="profile-actions-modern">
                                    <button class="btn-primary-modern" onclick="openEditModal()">
                                        <i class="fas fa-edit"></i>
                                        Edit Profile
                                    </button>
                                    <a href="campaigns.php" class="btn-secondary-modern">
                                        <i class="fas fa-bullhorn"></i>
                                        View Campaigns
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="stats-section-modern">
                    <div class="stat-card-modern">
                        <div class="stat-icon-modern">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="stat-number-modern"><?php echo $campaignStats['total_campaigns'] ?? 0; ?></div>
                        <div class="stat-label-modern">Total Campaigns</div>
                    </div>
                    <div class="stat-card-modern">
                        <div class="stat-icon-modern">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="stat-number-modern"><?php echo $campaignStats['active_campaigns'] ?? 0; ?></div>
                        <div class="stat-label-modern">Active Campaigns</div>
                    </div>
                    <div class="stat-card-modern">
                        <div class="stat-icon-modern">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number-modern"><?php echo $campaignStats['completed_campaigns'] ?? 0; ?></div>
                        <div class="stat-label-modern">Completed</div>
                    </div>
                    <div class="stat-card-modern">
                        <div class="stat-icon-modern">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number-modern"><?php echo $applicationsStats['total_applications'] ?? 0; ?></div>
                        <div class="stat-label-modern">Applications</div>
                    </div>
                </div>

                <!-- Company Information -->
                <div class="content-section-modern">
                    <div class="section-header-modern">
                        <div class="section-icon-modern">
                            <i class="fas fa-building"></i>
                        </div>
                        <h2 class="section-title-modern">Company Information</h2>
                    </div>
                    <div class="info-grid-modern">
                        <div class="info-item-modern">
                            <div class="info-label-modern"><i class="fas fa-building"></i> Company Name</div>
                            <div class="info-value-modern"><?php echo htmlspecialchars($profile['company_name'] ?? 'Not provided'); ?></div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-label-modern"><i class="fas fa-globe"></i> Website</div>
                            <?php if ($profile['website_url']): ?>
                                <div class="info-value-modern">
                                    <a href="<?php echo htmlspecialchars($profile['website_url']); ?>" target="_blank" class="info-link-modern">
                                        <?php echo htmlspecialchars($profile['website_url']); ?>
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="info-value-modern empty">Not provided</div>
                            <?php endif; ?>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-label-modern"><i class="fas fa-user-tie"></i> Contact Person</div>
                            <div class="info-value-modern"><?php echo htmlspecialchars($profile['contact_person_name'] ?? 'Not provided'); ?></div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-label-modern"><i class="fas fa-phone"></i> Contact Phone</div>
                            <div class="info-value-modern"><?php echo htmlspecialchars($profile['contact_person_phone'] ?? 'Not provided'); ?></div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-label-modern"><i class="fas fa-envelope"></i> Contact Email</div>
                            <div class="info-value-modern"><?php echo htmlspecialchars($profile['contact_person_email'] ?? $profile['email']); ?></div>
                        </div>

                        <div class="info-item-modern">
                            <div class="info-label-modern"><i class="fas fa-map-marker-alt"></i> Country</div>
                            <div class="info-value-modern"><?php echo htmlspecialchars($profile['country'] ?? 'Not provided'); ?></div>
                        </div>

                        <?php if ($profile['tax_number']): ?>
                        <div class="info-item-modern">
                            <div class="info-label-modern"><i class="fas fa-file-invoice"></i> Tax Number</div>
                            <div class="info-value-modern"><?php echo htmlspecialchars($profile['tax_number']); ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($profile['company_description']): ?>
                        <div class="info-item-modern full-width">
                            <div class="info-label-modern"><i class="fas fa-align-left"></i> Company Description</div>
                            <div class="info-value-modern"><?php echo nl2br(htmlspecialchars($profile['company_description'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Goals & Needs -->
                <?php if ($profile['goals'] || $profile['needs']): ?>
                <div class="content-section-modern">
                    <div class="section-header-modern">
                        <div class="section-icon-modern">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h2 class="section-title-modern">Business Goals & Needs</h2>
                    </div>
                    <div class="text-grid-modern">
                        <?php if ($profile['goals']): ?>
                        <div class="text-box-modern">
                            <div class="text-box-title-modern">
                                <i class="fas fa-target"></i>
                                Goals
                            </div>
                            <div class="text-box-content-modern">
                                <?php echo nl2br(htmlspecialchars($profile['goals'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($profile['needs']): ?>
                        <div class="text-box-modern">
                            <div class="text-box-title-modern">
                                <i class="fas fa-tasks"></i>
                                Needs
                            </div>
                            <div class="text-box-content-modern">
                                <?php echo nl2br(htmlspecialchars($profile['needs'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Social Media -->
                <?php if ($profile['instagram_url'] || $profile['tiktok_url'] || $profile['facebook_url']): ?>
                <div class="content-section-modern">
                    <div class="section-header-modern">
                        <div class="section-icon-modern">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <h2 class="section-title-modern">Social Media</h2>
                    </div>
                    <div class="social-grid-modern">
                        <?php if ($profile['instagram_url']): ?>
                        <a href="<?php echo htmlspecialchars($profile['instagram_url']); ?>" target="_blank" class="social-card-modern instagram">
                            <div class="social-icon-large">
                                <i class="fab fa-instagram"></i>
                            </div>
                            <div class="social-platform-name">Instagram</div>
                        </a>
                        <?php endif; ?>

                        <?php if ($profile['tiktok_url']): ?>
                        <a href="<?php echo htmlspecialchars($profile['tiktok_url']); ?>" target="_blank" class="social-card-modern tiktok">
                            <div class="social-icon-large">
                                <i class="fab fa-tiktok"></i>
                            </div>
                            <div class="social-platform-name">TikTok</div>
                        </a>
                        <?php endif; ?>

                        <?php if ($profile['facebook_url']): ?>
                        <a href="<?php echo htmlspecialchars($profile['facebook_url']); ?>" target="_blank" class="social-card-modern facebook">
                            <div class="social-icon-large">
                                <i class="fab fa-facebook"></i>
                            </div>
                            <div class="social-platform-name">Facebook</div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

<?php include '../includes/dashboard-scripts.php'; ?>

<script>
function openEditModal() {
    // TODO: Implement edit modal
    alert('Edit profile functionality coming soon!');
}
</script>

</body>
</html>
