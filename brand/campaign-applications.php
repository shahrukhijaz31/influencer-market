<?php
/**
 * Casters.fi - Campaign Applications Page
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isBrand()) {
    redirect('login.html');
}

$campaignId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$campaignId) {
    redirect('campaigns.php');
}

$profile = null;
$campaign = null;
$applications = [];
$totalCount = $pendingCount = $acceptedCount = $rejectedCount = 0;

try {
    $pdo = getDBConnection();

    // Get brand profile
    $stmt = $pdo->prepare("SELECT * FROM brand_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $brandProfile = $stmt->fetch();
    $profile = $brandProfile;

    // Get campaign
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND brand_id = ?");
    $stmt->execute([$campaignId, $brandProfile['id']]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        $_SESSION['error'] = 'Campaign not found';
        redirect('campaigns.php');
    }

    // Get filter
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

    // Get applications
    $sql = "
        SELECT ca.*,
               ip.id as influencer_profile_id,
               ip.instagram_username as instagram_handle,
               ip.tiktok_username as tiktok_handle,
               ip.instagram_followers as followers_count,
               ip.bio,
               u.id as user_id, u.first_name, u.last_name, u.email, u.profile_picture
        FROM campaign_applications ca
        JOIN influencer_profiles ip ON ca.influencer_id = ip.id
        JOIN users u ON ip.user_id = u.id
        WHERE ca.campaign_id = ?
    ";
    $params = [$campaignId];

    if ($statusFilter) {
        $sql .= " AND ca.status = ?";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY ca.applied_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    // Get counts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = ?");
    $stmt->execute([$campaignId]);
    $totalCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = ? AND status = 'pending'");
    $stmt->execute([$campaignId]);
    $pendingCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = ? AND status = 'accepted'");
    $stmt->execute([$campaignId]);
    $acceptedCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = ? AND status = 'rejected'");
    $stmt->execute([$campaignId]);
    $rejectedCount = $stmt->fetchColumn();

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Applications - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dashboard-pro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            margin-bottom: 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .back-link:hover {
            color: #e879f9;
        }

        .page-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.5rem;
        }

        .campaign-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.15) 0%, rgba(103, 232, 249, 0.15) 100%);
            border-radius: 25px;
            font-size: 0.875rem;
            color: #e879f9;
            font-weight: 500;
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.25rem;
        }

        .stat-icon.total { background: #f3f4f6; color: #374151; }
        .stat-icon.pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-icon.accepted { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-icon.rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-value.total { color: #111; }
        .stat-value.pending { color: #f59e0b; }
        .stat-value.accepted { color: #10b981; }
        .stat-value.rejected { color: #ef4444; }

        .stat-label {
            color: #666;
            font-size: 0.8125rem;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            background: white;
            padding: 0.375rem;
            border-radius: 12px;
            width: fit-content;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .filter-tab {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            background: #f5f5f5;
            color: #111;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
        }

        /* Applications Grid */
        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .application-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,0.06);
            transition: all 0.3s;
        }

        .application-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .application-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.05) 0%, rgba(103, 232, 249, 0.05) 100%);
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .applicant-avatar {
            position: relative;
        }

        .applicant-avatar img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .avatar-placeholder {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.75rem;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .applicant-info {
            flex: 1;
        }

        .applicant-info h3 {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.5rem;
        }

        .applicant-handles {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .handle-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            background: white;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #666;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            text-decoration: none;
            transition: all 0.2s;
        }

        a.handle-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

        a.handle-badge:hover i.fa-instagram { color: #E4405F; }
        a.handle-badge:hover i.fa-tiktok { color: #000; }

        .handle-badge i.fa-instagram { color: #E4405F; }
        .handle-badge i.fa-tiktok { color: #000; }
        .handle-badge i.fa-users { color: #e879f9; }

        .application-body {
            padding: 1.5rem;
        }

        .application-message-preview {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.08) 0%, rgba(103, 232, 249, 0.08) 100%);
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.8125rem;
            color: #666;
        }

        .application-message-preview i.fa-quote-left {
            color: #e879f9;
        }

        .application-message-preview span {
            flex: 1;
        }

        .btn-read-more {
            background: white;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #e879f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.2s;
        }

        .btn-read-more:hover {
            background: #e879f9;
            color: white;
        }

        /* Message Modal */
        .message-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .message-modal.active {
            display: flex;
        }

        .message-modal-content {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            max-height: 80vh;
            overflow: hidden;
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message-modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-modal-header h3 {
            margin: 0;
            font-size: 1.125rem;
            color: #111;
        }

        .message-modal-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f5f5f5;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            transition: all 0.2s;
        }

        .message-modal-close:hover {
            background: #e879f9;
            color: white;
        }

        .message-modal-body {
            padding: 1.5rem;
            max-height: 50vh;
            overflow-y: auto;
            overflow-x: hidden;
            font-size: 0.9375rem;
            line-height: 1.7;
            color: #333;
            white-space: pre-wrap;
            word-wrap: break-word;
            word-break: break-word;
        }

        .application-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
        }

        .status-badge.accepted {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-badge.rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .applied-time {
            font-size: 0.75rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .application-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.8125rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
            text-decoration: none;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .btn-accept {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
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

        .btn-view {
            background: #f5f5f5;
            color: #666;
        }

        .btn-view:hover {
            background: #e5e5e5;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: white;
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 4rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            color: #111;
            font-size: 1.25rem;
            margin: 0 0 0.5rem;
        }

        .empty-state p {
            color: #666;
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .applications-grid {
                grid-template-columns: 1fr;
            }

            .application-actions {
                flex-wrap: wrap;
            }
        }

        /* Confirmation Modal */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .confirm-modal.active {
            display: flex;
        }

        .confirm-modal-content {
            background: white;
            border-radius: 20px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            padding: 2rem;
            animation: modalSlide 0.3s ease;
        }

        .confirm-modal-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
        }

        .confirm-modal-icon.accept {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .confirm-modal-icon.reject {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .confirm-modal h3 {
            font-size: 1.25rem;
            color: #111;
            margin: 0 0 0.5rem;
        }

        .confirm-modal p {
            color: #666;
            font-size: 0.9375rem;
            margin: 0 0 1.5rem;
            line-height: 1.5;
        }

        .confirm-modal-actions {
            display: flex;
            gap: 0.75rem;
        }

        .confirm-modal-actions button {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-modal-cancel {
            background: #f5f5f5;
            color: #666;
        }

        .btn-modal-cancel:hover {
            background: #e5e5e5;
        }

        .btn-modal-accept {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-modal-accept:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-modal-reject {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-modal-reject:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/brand-sidebar.php'; ?>

        <main class="dashboard-main">
            <?php $pageTitle = 'Applications'; include '../includes/brand-topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <a href="campaigns.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Campaigns
                    </a>
                    <div class="page-title-row">
                        <div class="page-title">
                            <h1>Campaign Applications</h1>
                            <span class="campaign-badge">
                                <i class="fas fa-bullhorn"></i>
                                <?php echo htmlspecialchars($campaign['name']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon total"><i class="fas fa-users"></i></div>
                        <div class="stat-value total"><?php echo $totalCount; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                        <div class="stat-value pending"><?php echo $pendingCount; ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon accepted"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-value accepted"><?php echo $acceptedCount; ?></div>
                        <div class="stat-label">Accepted</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rejected"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-value rejected"><?php echo $rejectedCount; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="?id=<?php echo $campaignId; ?>" class="filter-tab <?php echo !$statusFilter ? 'active' : ''; ?>">All</a>
                    <a href="?id=<?php echo $campaignId; ?>&status=pending" class="filter-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?id=<?php echo $campaignId; ?>&status=accepted" class="filter-tab <?php echo $statusFilter === 'accepted' ? 'active' : ''; ?>">Accepted</a>
                    <a href="?id=<?php echo $campaignId; ?>&status=rejected" class="filter-tab <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
                </div>

                <!-- Applications Grid -->
                <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No applications yet</h3>
                    <p>When influencers apply to your campaign, they will appear here.</p>
                </div>
                <?php else: ?>
                <div class="applications-grid">
                    <?php foreach ($applications as $app):
                        $finlandTz = new DateTimeZone('Europe/Helsinki');
                        $appliedDate = new DateTime($app['applied_at']);
                        $appliedDate->setTimezone($finlandTz);

                        // Get profile photo
                        $profilePhoto = null;
                        if (!empty($app['profile_picture'])) {
                            $profilePhoto = $app['profile_picture'];
                        } else {
                            // Check influencer_profiles for profile_photo
                            $stmtPhoto = $pdo->prepare("SELECT profile_photo FROM influencer_profiles WHERE id = ?");
                            $stmtPhoto->execute([$app['influencer_profile_id']]);
                            $photoResult = $stmtPhoto->fetch();
                            if ($photoResult && $photoResult['profile_photo']) {
                                $profilePhoto = '../uploads/profiles/' . $photoResult['profile_photo'];
                            }
                        }
                    ?>
                    <div class="application-card" data-id="<?php echo $app['id']; ?>">
                        <div class="application-header">
                            <div class="applicant-avatar">
                                <?php if ($profilePhoto): ?>
                                <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="<?php echo htmlspecialchars($app['first_name']); ?>">
                                <?php else: ?>
                                <div class="avatar-placeholder">
                                    <?php echo strtoupper(substr($app['first_name'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="applicant-info">
                                <h3><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h3>
                                <div class="applicant-handles">
                                    <?php if (!empty($app['instagram_handle']) && trim($app['instagram_handle']) !== ''): ?>
                                    <a href="https://instagram.com/<?php echo htmlspecialchars($app['instagram_handle']); ?>" target="_blank" class="handle-badge"><i class="fab fa-instagram"></i> @<?php echo htmlspecialchars($app['instagram_handle']); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['tiktok_handle']) && trim($app['tiktok_handle']) !== ''): ?>
                                    <a href="https://tiktok.com/@<?php echo htmlspecialchars($app['tiktok_handle']); ?>" target="_blank" class="handle-badge"><i class="fab fa-tiktok"></i> @<?php echo htmlspecialchars($app['tiktok_handle']); ?></a>
                                    <?php endif; ?>
                                    <?php if (!empty($app['followers_count']) && $app['followers_count'] > 0): ?>
                                    <span class="handle-badge"><i class="fas fa-users"></i> <?php echo number_format($app['followers_count']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="application-body">
                            <?php if ($app['message']): ?>
                            <div class="application-message-preview">
                                <i class="fas fa-quote-left"></i>
                                <span>Application message attached</span>
                                <button type="button" class="btn-read-more" onclick="showMessage(<?php echo $app['id']; ?>, '<?php echo htmlspecialchars(addslashes($app['first_name'] . ' ' . $app['last_name'])); ?>')">
                                    Read <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                            <div class="message-full" id="message-<?php echo $app['id']; ?>" style="display:none;"><?php echo htmlspecialchars($app['message']); ?></div>
                            <?php endif; ?>

                            <div class="application-meta">
                                <span class="status-badge <?php echo $app['status']; ?>">
                                    <?php
                                    $statusIcons = ['pending' => 'clock', 'accepted' => 'check-circle', 'rejected' => 'times-circle'];
                                    ?>
                                    <i class="fas fa-<?php echo $statusIcons[$app['status']] ?? 'circle'; ?>"></i>
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                                <div class="applied-time">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo $appliedDate->format('M d, Y H:i'); ?>
                                </div>
                            </div>

                            <div class="application-actions">
                                <?php if ($app['status'] === 'pending'): ?>
                                <button class="btn-action btn-accept" onclick="updateStatus(<?php echo $app['id']; ?>, 'accepted')">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button class="btn-action btn-reject" onclick="updateStatus(<?php echo $app['id']; ?>, 'rejected')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                                <?php else: ?>
                                <a href="messages.php?user=<?php echo $app['user_id']; ?>" class="btn-action btn-message">
                                    <i class="fas fa-comment"></i> Message
                                </a>
                                <a href="application-detail.php?id=<?php echo $app['id']; ?>" class="btn-action btn-view">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Message Modal -->
    <div class="message-modal" id="messageModal">
        <div class="message-modal-content">
            <div class="message-modal-header">
                <h3><i class="fas fa-envelope" style="color: #e879f9; margin-right: 0.5rem;"></i> <span id="modalTitle">Application Message</span></h3>
                <button class="message-modal-close" onclick="closeMessageModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="message-modal-body" id="modalBody"></div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="confirm-modal" id="confirmModal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-icon" id="confirmIcon">
                <i class="fas fa-times"></i>
            </div>
            <h3 id="confirmTitle">Reject Application?</h3>
            <p id="confirmMessage">Are you sure you want to reject this application? The influencer will be notified of your decision.</p>
            <div class="confirm-modal-actions">
                <button class="btn-modal-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn-modal-reject" id="confirmBtn" onclick="confirmAction()">Reject</button>
            </div>
        </div>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
    <script>
        let pendingAction = { id: null, status: null };

        function updateStatus(applicationId, status) {
            pendingAction = { id: applicationId, status: status };

            const modal = document.getElementById('confirmModal');
            const icon = document.getElementById('confirmIcon');
            const title = document.getElementById('confirmTitle');
            const message = document.getElementById('confirmMessage');
            const btn = document.getElementById('confirmBtn');

            if (status === 'accepted') {
                icon.className = 'confirm-modal-icon accept';
                icon.innerHTML = '<i class="fas fa-check"></i>';
                title.textContent = 'Accept Application?';
                message.textContent = 'Are you sure you want to accept this application? The influencer will be notified and can start working on your campaign.';
                btn.className = 'btn-modal-accept';
                btn.textContent = 'Accept';
            } else {
                icon.className = 'confirm-modal-icon reject';
                icon.innerHTML = '<i class="fas fa-times"></i>';
                title.textContent = 'Reject Application?';
                message.textContent = 'Are you sure you want to reject this application? The influencer will be notified of your decision.';
                btn.className = 'btn-modal-reject';
                btn.textContent = 'Reject';
            }

            modal.classList.add('active');
        }

        function confirmAction() {
            if (!pendingAction.id) return;

            const btn = document.getElementById('confirmBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch('api/update-application-status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    application_id: pendingAction.id,
                    status: pendingAction.status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update status');
                    btn.disabled = false;
                    btn.textContent = pendingAction.status === 'accepted' ? 'Accept' : 'Reject';
                }
            });
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
            pendingAction = { id: null, status: null };
        }

        function showMessage(appId, name) {
            const messageEl = document.getElementById('message-' + appId);
            if (messageEl) {
                document.getElementById('modalTitle').textContent = 'Message from ' + name;
                document.getElementById('modalBody').textContent = messageEl.textContent;
                document.getElementById('messageModal').classList.add('active');
            }
        }

        function closeMessageModal() {
            document.getElementById('messageModal').classList.remove('active');
        }

        document.getElementById('messageModal').addEventListener('click', function(e) {
            if (e.target === this) closeMessageModal();
        });

        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeConfirmModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMessageModal();
                closeConfirmModal();
            }
        });
    </script>
</body>
</html>
