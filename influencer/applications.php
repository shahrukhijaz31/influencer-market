<?php
/**
 * Casters.fi - Influencer Applications Page
 * Shows all campaigns the influencer has applied to
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isInfluencer()) {
    redirect('login.html');
}

$profile = null;
$applications = [];

try {
    $pdo = getDBConnection();

    // Get influencer profile
    $stmt = $pdo->prepare("SELECT * FROM influencer_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();

    if (!$profile) {
        $_SESSION['error'] = 'Please complete your profile first';
        redirect('profile.php');
    }

    // Get all applications with campaign details
    $stmt = $pdo->prepare("
        SELECT
            ca.id as application_id,
            ca.status,
            ca.message,
            ca.applied_at,
            ca.responded_at,
            c.id as campaign_id,
            c.name as campaign_name,
            c.description,
            c.category,
            c.budget,
            c.image,
            c.timing_start,
            c.timing_end,
            c.what_is_offered,
            bp.company_name,
            u.first_name as brand_first_name,
            u.last_name as brand_last_name
        FROM campaign_applications ca
        JOIN campaigns c ON ca.campaign_id = c.id
        JOIN brand_profiles bp ON c.brand_id = bp.id
        JOIN users u ON bp.user_id = u.id
        WHERE ca.influencer_id = ?
        ORDER BY ca.applied_at DESC
    ");
    $stmt->execute([$profile['id']]);
    $applications = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dashboard-pro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .applications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .applications-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111;
            margin: 0;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #666;
            background: transparent;
            border: none;
            cursor: pointer;
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

        .applications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .application-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
        }

        .application-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .application-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            position: relative;
            overflow: hidden;
        }

        .application-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .application-status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        .application-status-badge.pending {
            background: rgba(245, 158, 11, 0.9);
            color: white;
        }

        .application-status-badge.accepted {
            background: rgba(16, 185, 129, 0.9);
            color: white;
        }

        .application-status-badge.rejected {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }

        .application-content {
            padding: 1.5rem;
        }

        .application-brand {
            font-size: 0.75rem;
            font-weight: 600;
            color: #e879f9;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .application-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .application-description {
            font-size: 0.875rem;
            color: #666;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .application-meta {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
            font-size: 0.8125rem;
            color: #666;
        }

        .application-meta-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .application-meta-item i {
            color: #e879f9;
        }

        .application-dates {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
            font-size: 0.8125rem;
        }

        .application-date {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .application-date-label {
            color: #999;
        }

        .application-date-value {
            color: #333;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 4rem;
            color: #e5e5e5;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.5rem;
        }

        .empty-state p {
            color: #666;
            margin: 0 0 1.5rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(232, 121, 249, 0.4);
        }

        @media (max-width: 768px) {
            .applications-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .applications-grid {
                grid-template-columns: 1fr;
            }

            .filter-tabs {
                width: 100%;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/influencer-sidebar.php'; ?>

        <main class="dashboard-main">
            <?php $pageTitle = 'My Applications'; include '../includes/influencer-topbar.php'; ?>

            <div class="dashboard-content">
                <div class="applications-header">
                    <h1>My Applications</h1>
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all">All</button>
                        <button class="filter-tab" data-filter="pending">Pending</button>
                        <button class="filter-tab" data-filter="accepted">Accepted</button>
                        <button class="filter-tab" data-filter="rejected">Rejected</button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Applications Yet</h3>
                    <p>You haven't applied to any campaigns yet. Start browsing campaigns to find opportunities!</p>
                    <a href="campaigns.php" class="btn-primary">
                        <i class="fas fa-search"></i> Browse Campaigns
                    </a>
                </div>
                <?php else: ?>
                <div class="applications-grid" id="applicationsGrid">
                    <?php foreach ($applications as $app): ?>
                    <div class="application-card" data-status="<?php echo $app['status']; ?>" onclick="window.location.href='campaign-detail.php?id=<?php echo $app['campaign_id']; ?>'">
                        <div class="application-image">
                            <?php if (!empty($app['image'])): ?>
                            <img src="<?php echo htmlspecialchars($app['image']); ?>" alt="<?php echo htmlspecialchars($app['campaign_name']); ?>">
                            <?php else: ?>
                            <i class="fas fa-bullhorn" style="font-size: 3rem; color: white; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>
                            <?php endif; ?>
                            <span class="application-status-badge <?php echo $app['status']; ?>">
                                <?php
                                if ($app['status'] === 'pending') echo 'Pending';
                                elseif ($app['status'] === 'accepted') echo 'Accepted';
                                else echo 'Rejected';
                                ?>
                            </span>
                        </div>
                        <div class="application-content">
                            <div class="application-brand">
                                <?php echo htmlspecialchars($app['company_name']); ?>
                            </div>
                            <h3 class="application-title">
                                <?php echo htmlspecialchars($app['campaign_name']); ?>
                            </h3>
                            <p class="application-description">
                                <?php echo htmlspecialchars($app['description'] ?: 'No description available'); ?>
                            </p>
                            <div class="application-meta">
                                <?php if (!empty($app['budget'])): ?>
                                <div class="application-meta-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <span><?php echo number_format($app['budget']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($app['category'])): ?>
                                <div class="application-meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo htmlspecialchars($app['category']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="application-dates">
                                <div class="application-date">
                                    <span class="application-date-label">Applied:</span>
                                    <span class="application-date-value"><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></span>
                                </div>
                                <?php if ($app['responded_at']): ?>
                                <div class="application-date">
                                    <span class="application-date-label">Responded:</span>
                                    <span class="application-date-value"><?php echo date('M d, Y', strtotime($app['responded_at'])); ?></span>
                                </div>
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

    <?php include '../includes/dashboard-scripts.php'; ?>

    <script>
        // Filter functionality
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                const cards = document.querySelectorAll('.application-card');

                cards.forEach(card => {
                    if (filter === 'all' || card.dataset.status === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
