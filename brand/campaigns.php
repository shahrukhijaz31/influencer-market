<?php
/**
 * Casters.fi - Brand All Campaigns Page
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isBrand()) {
    redirect('login.html');
}

$profile = null;
$campaigns = [];
$totalCampaigns = 0;
$activeCampaigns = 0;
$draftCampaigns = 0;

try {
    $pdo = getDBConnection();

    // Get brand profile
    $stmt = $pdo->prepare("SELECT bp.* FROM brand_profiles bp WHERE bp.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $brandProfile = $stmt->fetch();
    $profile = $brandProfile; // For sidebar

    // Get filter parameters
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Build query
    $sql = "
        SELECT c.*,
               (SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = c.id) as application_count,
               (SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = c.id AND status = 'pending') as pending_count,
               (SELECT COUNT(*) FROM campaign_applications WHERE campaign_id = c.id AND status = 'accepted') as accepted_count
        FROM campaigns c
        WHERE c.brand_id = ?
    ";
    $params = [$brandProfile['id']];

    if ($status) {
        $sql .= " AND c.status = ?";
        $params[] = $status;
    }

    if ($search) {
        $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY c.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();

    // Get stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE brand_id = ?");
    $stmt->execute([$brandProfile['id']]);
    $totalCampaigns = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE brand_id = ? AND status = 'active'");
    $stmt->execute([$brandProfile['id']]);
    $activeCampaigns = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE brand_id = ? AND status = 'draft'");
    $stmt->execute([$brandProfile['id']]);
    $draftCampaigns = $stmt->fetchColumn();

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Campaigns - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dashboard-pro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.25rem;
        }

        .page-title p {
            color: #666;
            font-size: 0.9375rem;
            margin: 0;
        }

        .btn-create {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(232, 121, 249, 0.4);
        }

        /* Stats Cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card-label {
            color: #666;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Filters */
        .filters-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            font-size: 0.9375rem;
            background: white;
        }

        .search-box input:focus {
            outline: none;
            border-color: #e879f9;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.25rem;
            border-radius: 10px;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #666;
            text-decoration: none;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            background: #f5f5f5;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
        }

        /* Campaigns Grid */
        .campaigns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .campaign-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.3s;
        }

        .campaign-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .campaign-card .campaign-image {
            height: 180px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            position: relative;
            overflow: hidden;
            width: 100%;
            border-radius: 0;
            margin: 0;
            display: block;
        }

        .campaign-card .campaign-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .campaign-card .campaign-image .campaign-image-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.75rem 1rem;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
            z-index: 2;
        }

        .campaign-card .campaign-image-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
        }

        .campaign-card .campaign-budget {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: white !important;
            font-weight: 600;
            font-size: 0.8125rem;
            background: rgba(232, 121, 249, 0.9);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
        }

        .campaign-card .campaign-budget i {
            font-size: 0.75rem;
            color: white !important;
        }

        .campaign-card .campaign-date {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: white !important;
            font-size: 0.75rem;
        }

        .campaign-card .campaign-date i {
            color: rgba(255,255,255,0.8) !important;
            font-size: 0.7rem;
        }

        .campaign-card .campaign-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 3;
        }

        .campaign-card .campaign-status.active {
            background: rgba(16, 185, 129, 0.9);
            color: white;
        }

        .campaign-card .campaign-status.draft {
            background: rgba(107, 114, 128, 0.9);
            color: white;
        }

        .campaign-card .campaign-status.completed {
            background: rgba(59, 130, 246, 0.9);
            color: white;
        }

        .campaign-card .campaign-status.paused {
            background: rgba(245, 158, 11, 0.9);
            color: white;
        }

        .campaign-content {
            padding: 1.25rem;
        }

        .campaign-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111;
            margin: 0 0 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .campaign-stats {
            display: flex;
            gap: 1rem;
        }

        .campaign-stat {
            flex: 1;
            text-align: center;
        }

        .campaign-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111;
        }

        .campaign-stat-label {
            font-size: 0.75rem;
            color: #999;
        }

        .campaign-stat.pending .campaign-stat-value {
            color: #f59e0b;
        }

        .campaign-actions {
            display: flex;
            gap: 0.5rem;
            padding: 1rem 1.25rem;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
        }

        .btn-campaign {
            flex: 1;
            padding: 0.625rem;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.375rem;
        }

        .btn-campaign.primary {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
        }

        .btn-campaign.secondary {
            background: white;
            color: #666;
            border: 1px solid #e5e5e5;
        }

        .btn-campaign.danger {
            background: white;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        .btn-campaign:hover {
            transform: translateY(-1px);
        }

        .btn-campaign.danger:hover {
            background: #fef2f2;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
        }

        .empty-state i {
            font-size: 4rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            color: #111;
            margin: 0 0 0.5rem;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        /* Delete Modal */
        .delete-modal {
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
        }

        .delete-modal.active {
            display: flex;
        }

        .delete-modal-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .delete-modal-icon {
            width: 60px;
            height: 60px;
            background: #fef2f2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: #ef4444;
            font-size: 1.5rem;
        }

        .delete-modal h3 {
            margin: 0 0 0.5rem;
            color: #111;
        }

        .delete-modal p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .delete-modal-actions {
            display: flex;
            gap: 1rem;
        }

        .delete-modal-actions button {
            flex: 1;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .btn-cancel {
            background: #f5f5f5;
            color: #666;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: 1fr;
            }

            .campaigns-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/brand-sidebar.php'; ?>

        <main class="dashboard-main">
            <?php $pageTitle = 'My Campaigns'; include '../includes/brand-topbar.php'; ?>

            <div class="dashboard-content">
                <div class="page-header">
                    <div class="page-title">
                        <h1>My Campaigns</h1>
                        <p>Manage and monitor all your influencer campaigns</p>
                    </div>
                    <a href="create-campaign.php" class="btn-create">
                        <i class="fas fa-plus"></i>
                        Create Campaign
                    </a>
                </div>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $totalCampaigns; ?></div>
                        <div class="stat-card-label">Total Campaigns</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $activeCampaigns; ?></div>
                        <div class="stat-card-label">Active Campaigns</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-value"><?php echo $draftCampaigns; ?></div>
                        <div class="stat-card-label">Draft Campaigns</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search campaigns..." id="searchCampaigns" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-tabs">
                        <a href="campaigns.php" class="filter-tab <?php echo !$status ? 'active' : ''; ?>">All</a>
                        <a href="campaigns.php?status=active" class="filter-tab <?php echo $status === 'active' ? 'active' : ''; ?>">Active</a>
                        <a href="campaigns.php?status=draft" class="filter-tab <?php echo $status === 'draft' ? 'active' : ''; ?>">Draft</a>
                        <a href="campaigns.php?status=completed" class="filter-tab <?php echo $status === 'completed' ? 'active' : ''; ?>">Completed</a>
                    </div>
                </div>

                <!-- Campaigns Grid -->
                <?php if (empty($campaigns)): ?>
                <div class="empty-state">
                    <i class="fas fa-bullhorn"></i>
                    <h3>No campaigns found</h3>
                    <p>Create your first campaign to start connecting with influencers</p>
                    <a href="create-campaign.php" class="btn-create">
                        <i class="fas fa-plus"></i>
                        Create Campaign
                    </a>
                </div>
                <?php else: ?>
                <div class="campaigns-grid">
                    <?php foreach ($campaigns as $campaign):
                        $campaignImage = $campaign['hero_image'] ?? $campaign['image'] ?? '';
                        $finlandTz = new DateTimeZone('Europe/Helsinki');
                        $createdDate = new DateTime($campaign['created_at']);
                        $createdDate->setTimezone($finlandTz);
                    ?>
                    <div class="campaign-card" data-id="<?php echo $campaign['id']; ?>">
                        <div class="campaign-image">
                            <?php if ($campaignImage): ?>
                            <img src="<?php echo htmlspecialchars($campaignImage); ?>" alt="">
                            <?php endif; ?>
                            <span class="campaign-status <?php echo $campaign['status']; ?>">
                                <?php echo ucfirst($campaign['status']); ?>
                            </span>
                            <div class="campaign-image-overlay">
                                <div class="campaign-image-meta">
                                    <div class="campaign-budget">
                                        <i class="fas fa-euro-sign"></i>
                                        <?php echo number_format($campaign['budget']); ?>
                                    </div>
                                    <div class="campaign-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo $createdDate->format('M d, Y'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="campaign-content">
                            <h3 class="campaign-title"><?php echo htmlspecialchars($campaign['name']); ?></h3>
                            <div class="campaign-stats">
                                <div class="campaign-stat">
                                    <div class="campaign-stat-value"><?php echo $campaign['application_count']; ?></div>
                                    <div class="campaign-stat-label">Applications</div>
                                </div>
                                <div class="campaign-stat pending">
                                    <div class="campaign-stat-value"><?php echo $campaign['pending_count']; ?></div>
                                    <div class="campaign-stat-label">Pending</div>
                                </div>
                                <div class="campaign-stat">
                                    <div class="campaign-stat-value"><?php echo $campaign['accepted_count']; ?></div>
                                    <div class="campaign-stat-label">Accepted</div>
                                </div>
                            </div>
                        </div>
                        <div class="campaign-actions">
                            <a href="campaign-applications.php?id=<?php echo $campaign['id']; ?>" class="btn-campaign primary">
                                <i class="fas fa-users"></i> Applications
                            </a>
                            <a href="edit-campaign.php?id=<?php echo $campaign['id']; ?>" class="btn-campaign secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn-campaign danger" onclick="confirmDelete(<?php echo $campaign['id']; ?>, '<?php echo htmlspecialchars(addslashes($campaign['name'])); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-modal-content">
            <div class="delete-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Delete Campaign?</h3>
            <p>Are you sure you want to delete "<span id="deleteCampaignName"></span>"? This action cannot be undone.</p>
            <div class="delete-modal-actions">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-delete" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
    <script>
        let deleteId = null;

        function confirmDelete(id, name) {
            deleteId = id;
            document.getElementById('deleteCampaignName').textContent = name;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (!deleteId) return;

            fetch('api/delete-campaign.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({campaign_id: deleteId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector(`.campaign-card[data-id="${deleteId}"]`).remove();
                    closeDeleteModal();
                } else {
                    alert(data.message || 'Failed to delete campaign');
                }
            });
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        // Search
        let searchTimeout;
        document.getElementById('searchCampaigns').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const url = new URL(window.location);
                if (this.value) {
                    url.searchParams.set('search', this.value);
                } else {
                    url.searchParams.delete('search');
                }
                window.location = url;
            }, 500);
        });
    </script>
</body>
</html>
