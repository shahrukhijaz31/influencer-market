<?php
/**
 * Casters.fi - Application Detail Page
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isBrand()) {
    redirect('login.html');
}

$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$applicationId) {
    redirect('campaigns.php');
}

try {
    $pdo = getDBConnection();

    // Get brand profile
    $stmt = $pdo->prepare("SELECT id FROM brand_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $brandProfile = $stmt->fetch();

    // Get application with full details
    $stmt = $pdo->prepare("
        SELECT ca.*,
               c.id as campaign_id, c.name as campaign_name, c.budget, c.what_is_expected, c.what_is_offered,
               ip.id as influencer_profile_id, ip.instagram_handle, ip.tiktok_handle, ip.youtube_handle,
               ip.followers_count, ip.bio, ip.instagram_url, ip.tiktok_url, ip.youtube_url,
               ip.engagement_rate, ip.avg_likes, ip.avg_comments,
               u.id as user_id, u.first_name, u.last_name, u.email, u.profile_picture, u.phone
        FROM campaign_applications ca
        JOIN campaigns c ON ca.campaign_id = c.id
        JOIN influencer_profiles ip ON ca.influencer_id = ip.id
        JOIN users u ON ip.user_id = u.id
        WHERE ca.id = ? AND c.brand_id = ?
    ");
    $stmt->execute([$applicationId, $brandProfile['id']]);
    $application = $stmt->fetch();

    if (!$application) {
        $_SESSION['error'] = 'Application not found';
        redirect('campaigns.php');
    }

    $finlandTz = new DateTimeZone('Europe/Helsinki');
    $appliedDate = new DateTime($application['applied_at']);
    $appliedDate->setTimezone($finlandTz);

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dashboard-pro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            margin-bottom: 1.5rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .back-link:hover {
            color: #e879f9;
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111;
            margin: 0;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
        }

        .detail-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111;
            margin: 0 0 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: #e879f9;
        }

        /* Influencer Profile */
        .influencer-header {
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .influencer-avatar img,
        .influencer-avatar .avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-placeholder {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
        }

        .influencer-info h2 {
            font-size: 1.375rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.5rem;
        }

        .influencer-handles {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 0.75rem;
        }

        .handle-link {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .handle-link:hover {
            color: #e879f9;
        }

        .handle-link i {
            font-size: 1rem;
        }

        .handle-link .fa-instagram { color: #E4405F; }
        .handle-link .fa-tiktok { color: #000; }
        .handle-link .fa-youtube { color: #FF0000; }

        .influencer-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #999;
        }

        .influencer-bio {
            color: #555;
            font-size: 0.9375rem;
            line-height: 1.6;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }

        /* Application Message */
        .application-message {
            background: #fafafa;
            border-radius: 12px;
            padding: 1.25rem;
            font-size: 0.9375rem;
            color: #444;
            line-height: 1.7;
        }

        /* Sidebar */
        .sidebar-section {
            margin-bottom: 1.5rem;
        }

        .status-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
            background: #fafafa;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }

        .status-badge.accepted {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }

        .status-badge.rejected {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }

        .applied-date {
            font-size: 0.8125rem;
            color: #999;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.875rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-accept {
            background: #10b981;
            color: white;
        }

        .btn-accept:hover {
            background: #059669;
        }

        .btn-reject {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        .btn-reject:hover {
            background: #fee2e2;
        }

        .btn-message {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
        }

        .btn-message:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(232, 121, 249, 0.4);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        /* Campaign Info */
        .campaign-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.08) 0%, rgba(103, 232, 249, 0.08) 100%);
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .campaign-link:hover {
            transform: translateY(-2px);
        }

        .campaign-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .campaign-details h4 {
            font-weight: 600;
            color: #111;
            margin: 0 0 0.25rem;
            font-size: 0.9375rem;
        }

        .campaign-details p {
            color: #666;
            font-size: 0.8125rem;
            margin: 0;
        }

        /* Contact Info */
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-icon {
            width: 36px;
            height: 36px;
            background: #f5f5f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e879f9;
        }

        .contact-info {
            flex: 1;
        }

        .contact-label {
            font-size: 0.75rem;
            color: #999;
        }

        .contact-value {
            font-size: 0.875rem;
            color: #111;
        }

        .contact-value a {
            color: #e879f9;
            text-decoration: none;
        }

        @media (max-width: 992px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .influencer-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .influencer-handles {
                justify-content: center;
            }

            .influencer-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/brand-sidebar.php'; ?>

        <main class="dashboard-main">
            <?php $pageTitle = 'Application Detail'; include '../includes/brand-topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <a href="campaign-applications.php?id=<?php echo $application['campaign_id']; ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Applications
                    </a>
                    <div class="page-title">
                        <h1>Application Details</h1>
                    </div>
                </div>

                <div class="detail-grid">
                    <!-- Main Content -->
                    <div class="main-content">
                        <!-- Influencer Profile -->
                        <div class="detail-card">
                            <h3 class="card-title"><i class="fas fa-user"></i> Influencer Profile</h3>
                            <div class="influencer-header">
                                <div class="influencer-avatar">
                                    <?php if ($application['profile_picture']): ?>
                                    <img src="<?php echo htmlspecialchars($application['profile_picture']); ?>" alt="">
                                    <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <?php echo strtoupper(substr($application['first_name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="influencer-info">
                                    <h2><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h2>
                                    <div class="influencer-handles">
                                        <?php if ($application['instagram_handle']): ?>
                                        <a href="<?php echo htmlspecialchars($application['instagram_url'] ?: 'https://instagram.com/' . $application['instagram_handle']); ?>" target="_blank" class="handle-link">
                                            <i class="fab fa-instagram"></i> @<?php echo htmlspecialchars($application['instagram_handle']); ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($application['tiktok_handle']): ?>
                                        <a href="<?php echo htmlspecialchars($application['tiktok_url'] ?: 'https://tiktok.com/@' . $application['tiktok_handle']); ?>" target="_blank" class="handle-link">
                                            <i class="fab fa-tiktok"></i> @<?php echo htmlspecialchars($application['tiktok_handle']); ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($application['youtube_handle']): ?>
                                        <a href="<?php echo htmlspecialchars($application['youtube_url'] ?: 'https://youtube.com/@' . $application['youtube_handle']); ?>" target="_blank" class="handle-link">
                                            <i class="fab fa-youtube"></i> <?php echo htmlspecialchars($application['youtube_handle']); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="influencer-stats">
                                        <?php if ($application['followers_count']): ?>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo number_format($application['followers_count']); ?></div>
                                            <div class="stat-label">Followers</div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($application['engagement_rate']): ?>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo number_format($application['engagement_rate'], 2); ?>%</div>
                                            <div class="stat-label">Engagement</div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($application['avg_likes']): ?>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo number_format($application['avg_likes']); ?></div>
                                            <div class="stat-label">Avg. Likes</div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($application['bio']): ?>
                            <div class="influencer-bio">
                                <?php echo nl2br(htmlspecialchars($application['bio'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Application Message -->
                        <div class="detail-card">
                            <h3 class="card-title"><i class="fas fa-comment-alt"></i> Application Message</h3>
                            <div class="application-message">
                                <?php echo nl2br(htmlspecialchars($application['message'] ?: 'No message provided.')); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="sidebar">
                        <!-- Status & Actions -->
                        <div class="detail-card">
                            <div class="status-display">
                                <span class="status-badge <?php echo $application['status']; ?>">
                                    <?php
                                    $statusIcons = ['pending' => 'clock', 'accepted' => 'check-circle', 'rejected' => 'times-circle'];
                                    ?>
                                    <i class="fas fa-<?php echo $statusIcons[$application['status']] ?? 'circle'; ?>"></i>
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                                <span class="applied-date">Applied <?php echo $appliedDate->format('M d, Y \a\t H:i'); ?></span>
                            </div>

                            <div class="action-buttons">
                                <?php if ($application['status'] === 'pending'): ?>
                                <button class="btn btn-accept" onclick="updateStatus('accepted')">
                                    <i class="fas fa-check"></i> Accept Application
                                </button>
                                <button class="btn btn-reject" onclick="updateStatus('rejected')">
                                    <i class="fas fa-times"></i> Reject Application
                                </button>
                                <?php endif; ?>
                                <a href="messages.php?user=<?php echo $application['user_id']; ?>" class="btn btn-message">
                                    <i class="fas fa-comment"></i> Send Message
                                </a>
                            </div>
                        </div>

                        <!-- Campaign Info -->
                        <div class="detail-card">
                            <h3 class="card-title"><i class="fas fa-bullhorn"></i> Campaign</h3>
                            <a href="campaign-applications.php?id=<?php echo $application['campaign_id']; ?>" class="campaign-link">
                                <div class="campaign-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <div class="campaign-details">
                                    <h4><?php echo htmlspecialchars($application['campaign_name']); ?></h4>
                                    <p>Budget: €<?php echo number_format($application['budget']); ?></p>
                                </div>
                            </a>
                        </div>

                        <!-- Contact Info -->
                        <div class="detail-card">
                            <h3 class="card-title"><i class="fas fa-address-card"></i> Contact</h3>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-label">Email</div>
                                    <div class="contact-value">
                                        <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>">
                                            <?php echo htmlspecialchars($application['email']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php if ($application['phone']): ?>
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-info">
                                    <div class="contact-label">Phone</div>
                                    <div class="contact-value">
                                        <a href="tel:<?php echo htmlspecialchars($application['phone']); ?>">
                                            <?php echo htmlspecialchars($application['phone']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
    <script>
        function updateStatus(status) {
            if (!confirm(`Are you sure you want to ${status === 'accepted' ? 'accept' : 'reject'} this application?`)) {
                return;
            }

            fetch('api/update-application-status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    application_id: <?php echo $applicationId; ?>,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update status');
                }
            });
        }
    </script>
</body>
</html>
