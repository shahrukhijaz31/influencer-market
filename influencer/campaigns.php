<?php
/**
 * Casters.fi - Influencer Campaigns Page
 * Displays all available campaigns for influencers to browse and apply
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isInfluencer()) {
    redirect('login.html');
}

// Initialize variables
$campaigns = [];
$totalCampaigns = 0;
$categories = [];

// Get influencer profile
try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id FROM influencer_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $influencer = $stmt->fetch();

    // Get search and filter parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $timeline = isset($_GET['timeline']) ? $_GET['timeline'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

    // Build query
    $sql = "
        SELECT c.*,
               bp.company_name,
               bp.instagram_url,
               bp.tiktok_url,
               u.profile_picture,
               (SELECT COUNT(*) FROM campaign_applications ca
                WHERE ca.campaign_id = c.id) as application_count,
               (SELECT status FROM campaign_applications ca
                WHERE ca.campaign_id = c.id AND ca.influencer_id = ?) as my_application_status
        FROM campaigns c
        JOIN brand_profiles bp ON c.brand_id = bp.id
        JOIN users u ON bp.user_id = u.id
        WHERE c.status = 'active' AND c.is_public = TRUE
    ";

    $params = [$influencer['id'] ?? 0];

    // Add search filter
    if (!empty($search)) {
        $sql .= " AND (c.name LIKE ? OR c.description LIKE ? OR c.category LIKE ? OR bp.company_name LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Add category filter
    if (!empty($category)) {
        $sql .= " AND c.category = ?";
        $params[] = $category;
    }

    // Add timeline filters
    if ($timeline === 'current') {
        $sql .= " AND c.timing_start <= CURDATE() AND c.timing_end >= CURDATE()";
    } elseif ($timeline === 'upcoming') {
        $sql .= " AND c.timing_start > CURDATE()";
    } elseif ($timeline === 'past') {
        $sql .= " AND c.timing_end < CURDATE()";
    }

    // Add special filters
    if ($filter === 'new') {
        $sql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter === 'urgent') {
        $sql .= " AND c.timing_end <= DATE_ADD(NOW(), INTERVAL 14 DAY)";
    } elseif ($filter === 'high_budget') {
        $sql .= " AND c.budget >= 1000";
    }

    // Add sorting
    switch ($sort) {
        case 'oldest':
            $sql .= " ORDER BY c.created_at ASC";
            break;
        case 'budget_high':
            $sql .= " ORDER BY c.budget DESC, c.created_at DESC";
            break;
        case 'budget_low':
            $sql .= " ORDER BY c.budget ASC, c.created_at DESC";
            break;
        case 'ending_soon':
            $sql .= " ORDER BY c.timing_end ASC, c.created_at DESC";
            break;
        case 'most_positions':
            $sql .= " ORDER BY c.influencers_needed DESC, c.created_at DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY c.created_at DESC";
            break;
    }

    // Get total count before limiting - build separate count query
    $countSql = "
        SELECT COUNT(DISTINCT c.id) as total
        FROM campaigns c
        JOIN brand_profiles bp ON c.brand_id = bp.id
        JOIN users u ON bp.user_id = u.id
        WHERE c.status = 'active' AND c.is_public = TRUE
    ";

    // Add same filters to count query (but skip the first influencer_id param)
    $countParams = [];

    if (!empty($search)) {
        $countSql .= " AND (c.name LIKE ? OR c.description LIKE ? OR c.category LIKE ? OR bp.company_name LIKE ?)";
        $searchParam = "%$search%";
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
    }

    if (!empty($category)) {
        $countSql .= " AND c.category = ?";
        $countParams[] = $category;
    }

    if ($timeline === 'current') {
        $countSql .= " AND c.timing_start <= CURDATE() AND c.timing_end >= CURDATE()";
    } elseif ($timeline === 'upcoming') {
        $countSql .= " AND c.timing_start > CURDATE()";
    } elseif ($timeline === 'past') {
        $countSql .= " AND c.timing_end < CURDATE()";
    }

    if ($filter === 'new') {
        $countSql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filter === 'urgent') {
        $countSql .= " AND c.timing_end <= DATE_ADD(NOW(), INTERVAL 14 DAY)";
    } elseif ($filter === 'high_budget') {
        $countSql .= " AND c.budget >= 1000";
    }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalCampaigns = $countStmt->fetch()['total'];

    // Add limit for initial load (only 20 campaigns)
    $sql .= " LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();

    // Get all categories for filter
    $stmt = $pdo->query("SELECT DISTINCT category FROM campaigns WHERE status = 'active' AND category IS NOT NULL ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = $e->getMessage();
    error_log("Campaign fetch error: " . $error);
    // Keep empty arrays from initialization
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Campaigns - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-content {
            padding: 1.5rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 0.5rem 0;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Search and Filter Section */
        .search-filter-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .search-form {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #f5f5f5;
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

        /* Filter Sections Container */
        .filters-container {
            display: grid;
            gap: 1.5rem;
        }

        /* Filter Chips */
        .filter-section {
            margin-bottom: 0;
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #8e8e8e;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .filter-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #f0f0f0;
            margin-left: 1rem;
        }

        .filter-chips {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            background: white;
            border: 1.5px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #262626;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .filter-chip:hover {
            border-color: #e879f9;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(232, 121, 249, 0.15);
        }

        .filter-chip.active {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 3px 10px rgba(232, 121, 249, 0.3);
        }

        .filter-chip i {
            font-size: 0.75rem;
        }

        /* Results Header */
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0 0.25rem;
        }

        .results-count {
            font-size: 0.9375rem;
            color: var(--text-secondary);
        }

        .results-count strong {
            color: var(--text-primary);
            font-weight: 700;
        }

        /* Sort Dropdown */
        .sort-dropdown {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .sort-label {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .sort-select {
            padding: 0.5rem 2rem 0.5rem 0.875rem;
            border: 1.5px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #262626;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23262626' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.625rem center;
        }

        .sort-select:hover {
            border-color: #e879f9;
            box-shadow: 0 2px 8px rgba(232, 121, 249, 0.15);
        }

        .sort-select:focus {
            outline: none;
            border-color: #e879f9;
            box-shadow: 0 0 0 3px rgba(232, 121, 249, 0.1);
        }

        /* Campaign Grid */
        .campaigns-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        /* Campaign Card */
        .campaign-card {
            background: white;
            border-radius: 0;
            border: none;
            overflow: hidden;
            transition: all 0.25s ease;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .campaign-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .campaign-card-image {
            position: relative;
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            background-color: #f5f5f5;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        .campaign-card-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.15) 100%);
            z-index: 1;
        }

        .campaign-card-badge {
            position: absolute;
            top: 0.625rem;
            left: 0.625rem;
            padding: 0.375rem 0.625rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border-radius: 16px;
            font-size: 0.625rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
            z-index: 2;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .campaign-card-applied {
            position: absolute;
            top: 0.625rem;
            right: 0.625rem;
            padding: 0.375rem 0.625rem;
            background: rgba(16, 185, 129, 0.95);
            backdrop-filter: blur(10px);
            color: white;
            border-radius: 16px;
            font-size: 0.625rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .campaign-card-date {
            position: absolute;
            bottom: 0.625rem;
            left: 0.625rem;
            padding: 0.375rem 0.625rem;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(10px);
            color: white;
            border-radius: 6px;
            font-size: 0.625rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            z-index: 2;
        }

        .campaign-card-date i {
            font-size: 0.625rem;
        }

        .campaign-card-favorite {
            position: absolute;
            bottom: 0.625rem;
            right: 0.625rem;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            z-index: 2;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }

        .campaign-card-favorite:hover {
            transform: scale(1.1);
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .campaign-card-favorite i {
            font-size: 0.875rem;
            color: #262626;
            transition: all 0.3s ease;
        }

        .campaign-card-favorite.active i {
            color: #e879f9;
        }

        .campaign-card-favorite.active {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.15) 0%, rgba(103, 232, 249, 0.15) 100%);
            border-color: rgba(232, 121, 249, 0.3);
        }

        .campaign-card-content {
            padding: 0.75rem 0.625rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }


        .campaign-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #262626;
            margin: 0 0 0.375rem 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            letter-spacing: -0.01em;
        }

        .campaign-card-brand-by {
            font-size: 0.625rem;
            font-weight: 600;
            color: #999;
            letter-spacing: 0.5px;
            margin-bottom: 0.625rem;
        }

        .campaign-card-description {
            font-size: 0.8125rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .campaign-card-meta {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            padding: 0.625rem 0.75rem;
            background: #fafafa;
            border-radius: 6px;
        }

        .meta-item-small {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.6875rem;
            color: #666;
        }

        .meta-item-small i {
            color: #e879f9;
            font-size: 0.75rem;
        }

        .campaign-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.75rem;
            border-top: 1px solid #f0f0f0;
            margin-top: auto;
        }

        .campaign-card-compensation {
            font-size: 0.875rem;
            font-weight: 700;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .campaign-card-cta {
            padding: 0.5rem 0.875rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border-radius: 6px;
            font-size: 0.6875rem;
            font-weight: 600;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .campaign-card:hover .campaign-card-cta {
            transform: translateX(3px);
            box-shadow: 0 2px 8px rgba(232, 121, 249, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--primary-dark);
            font-size: 2rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.75rem 0;
            color: var(--text-primary);
        }

        /* No Results State */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }

        .no-results i {
            font-size: 4rem;
            color: #e879f9;
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .no-results p {
            font-size: 1rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .empty-state .btn {
            padding: 0.875rem 2rem;
        }

        /* Load More Button */
        .load-more-container {
            display: flex;
            justify-content: center;
            margin-top: 2.5rem;
            margin-bottom: 2rem;
        }

        #load-more-btn {
            padding: 0.875rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(232, 121, 249, 0.3);
        }

        #load-more-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(232, 121, 249, 0.4);
        }

        #load-more-btn i {
            margin-left: 0.5rem;
            transition: transform 0.3s ease;
        }

        #load-more-btn:hover i {
            transform: translateY(2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .campaigns-grid {
                grid-template-columns: 1fr;
            }

            .search-form {
                flex-direction: column;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/influencer-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php $pageTitle = 'Browse Campaigns'; include '../includes/influencer-topbar.php'; ?>

            <div class="dashboard-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>Browse Campaigns</h1>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-filter-section">
                    <form action="" method="GET" class="search-form">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" class="search-input"
                                   placeholder="Search campaigns by title, category, or brand..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>

                    <div class="filters-container">
                        <!-- Quick Filters -->
                        <div class="filter-section">
                            <span class="filter-label">Quick Filters</span>
                            <div class="filter-chips">
                                <a href="campaigns.php" class="filter-chip <?php echo empty($filter) && empty($category) && empty($timeline) ? 'active' : ''; ?>">
                                    <i class="fas fa-bullhorn"></i> All Campaigns
                                </a>
                                <a href="javascript:void(0)" onclick="filterFavorites()" class="filter-chip" id="favorites-filter">
                                    <i class="fas fa-heart"></i> Favorites
                                </a>
                                <a href="campaigns.php?filter=new" class="filter-chip <?php echo $filter === 'new' ? 'active' : ''; ?>">
                                    <i class="fas fa-sparkles"></i> New
                                </a>
                                <a href="campaigns.php?filter=urgent" class="filter-chip <?php echo $filter === 'urgent' ? 'active' : ''; ?>">
                                    <i class="fas fa-clock"></i> Urgent
                                </a>
                                <a href="campaigns.php?filter=high_budget" class="filter-chip <?php echo $filter === 'high_budget' ? 'active' : ''; ?>">
                                    <i class="fas fa-euro-sign"></i> High Budget
                                </a>
                            </div>
                        </div>

                        <!-- Timeline Filters -->
                        <div class="filter-section">
                            <span class="filter-label">Timeline</span>
                            <div class="filter-chips">
                                <a href="campaigns.php?timeline=current" class="filter-chip <?php echo $timeline === 'current' ? 'active' : ''; ?>">
                                    <i class="fas fa-play-circle"></i> Current
                                </a>
                                <a href="campaigns.php?timeline=upcoming" class="filter-chip <?php echo $timeline === 'upcoming' ? 'active' : ''; ?>">
                                    <i class="fas fa-calendar-plus"></i> Upcoming
                                </a>
                                <a href="campaigns.php?timeline=past" class="filter-chip <?php echo $timeline === 'past' ? 'active' : ''; ?>">
                                    <i class="fas fa-history"></i> Past
                                </a>
                            </div>
                        </div>

                        <!-- Category Filters -->
                        <?php if (!empty($categories)): ?>
                        <div class="filter-section">
                            <span class="filter-label">Categories</span>
                            <div class="filter-chips">
                                <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                                    <a href="campaigns.php?category=<?php echo urlencode($cat); ?>"
                                       class="filter-chip <?php echo $category === $cat ? 'active' : ''; ?>">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Results Header -->
                <div class="results-header">
                    <div class="results-count">
                        <strong id="results-count"><?php echo count($campaigns); ?></strong> of <strong><?php echo $totalCampaigns; ?></strong> <?php echo $totalCampaigns === 1 ? 'campaign' : 'campaigns'; ?>
                    </div>
                    <div class="sort-dropdown">
                        <span class="sort-label"><i class="fas fa-sort"></i> Sort by:</span>
                        <select class="sort-select" onchange="window.location.href=this.value">
                            <option value="campaigns.php?sort=newest<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($timeline) ? '&timeline=' . $timeline : ''; ?>" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="campaigns.php?sort=oldest<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($timeline) ? '&timeline=' . $timeline : ''; ?>" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="campaigns.php?sort=budget_high<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($timeline) ? '&timeline=' . $timeline : ''; ?>" <?php echo $sort === 'budget_high' ? 'selected' : ''; ?>>Budget: High to Low</option>
                            <option value="campaigns.php?sort=budget_low<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($timeline) ? '&timeline=' . $timeline : ''; ?>" <?php echo $sort === 'budget_low' ? 'selected' : ''; ?>>Budget: Low to High</option>
                            <option value="campaigns.php?sort=ending_soon<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($timeline) ? '&timeline=' . $timeline : ''; ?>" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                            <option value="campaigns.php?sort=most_positions<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($filter) ? '&filter=' . $filter : ''; ?><?php echo !empty($timeline) ? '&timeline=' . $timeline : ''; ?>" <?php echo $sort === 'most_positions' ? 'selected' : ''; ?>>Most Positions</option>
                        </select>
                    </div>
                </div>

                <!-- Debug Error Display -->
                <?php if (isset($error)): ?>
                <div style="background: #fee; border: 1px solid #f00; padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Campaigns Grid -->
                <?php if (!empty($campaigns)): ?>
                <div class="campaigns-grid" id="campaigns-grid">
                    <?php foreach ($campaigns as $campaign):
                        $galleryImages = !empty($campaign['gallery_images']) ? explode(',', $campaign['gallery_images']) : [];
                        $firstImage = $campaign['hero_image'] ?? $campaign['image'] ?? (!empty($galleryImages) ? trim($galleryImages[0]) : '');
                    ?>
                    <a href="campaign-detail.php?id=<?php echo $campaign['id']; ?>" class="campaign-card" data-campaign-id="<?php echo $campaign['id']; ?>">
                        <div class="campaign-card-image" style="background-image: url('<?php echo htmlspecialchars($firstImage); ?>');">
                            <?php if ($campaign['category']): ?>
                                <div class="campaign-card-badge">
                                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($campaign['category']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($campaign['my_application_status']): ?>
                                <div class="campaign-card-applied">
                                    <i class="fas fa-check-circle"></i> Applied
                                </div>
                            <?php endif; ?>
                            <div class="campaign-card-date">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M d', strtotime($campaign['timing_start'])); ?> - <?php echo date('M d', strtotime($campaign['timing_end'])); ?>
                            </div>
                            <div class="campaign-card-favorite" onclick="toggleFavorite(event, <?php echo $campaign['id']; ?>)">
                                <i class="far fa-heart"></i>
                            </div>
                        </div>
                        <div class="campaign-card-content">
                            <h3 class="campaign-card-title"><?php echo htmlspecialchars($campaign['name']); ?></h3>

                            <div class="campaign-card-brand-by">
                                BY: <?php echo strtoupper(htmlspecialchars($campaign['company_name'])); ?>
                            </div>

                            <div class="campaign-card-meta">
                                <div class="meta-item-small">
                                    <i class="fas fa-users"></i>
                                    <?php echo $campaign['influencers_needed']; ?> Positions Available
                                </div>
                            </div>

                            <div class="campaign-card-footer">
                                <?php if ($campaign['budget']): ?>
                                    <div class="campaign-card-compensation">
                                        €<?php echo number_format($campaign['budget'], 0); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="campaign-card-compensation">
                                        Compensation Available
                                    </div>
                                <?php endif; ?>
                                <div class="campaign-card-cta">
                                    View Details <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalCampaigns > 20): ?>
                <div class="load-more-container">
                    <button id="load-more-btn" class="btn btn-secondary" data-offset="20" data-total="<?php echo $totalCampaigns; ?>">
                        Load More Campaigns <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No Campaigns Found</h3>
                    <p>We couldn't find any campaigns matching your search. Try different keywords or filters.</p>
                    <a href="campaigns.php" class="btn btn-primary">
                        <i class="fas fa-redo"></i> View All Campaigns
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
    <script>
        // Favorite functionality
        function toggleFavorite(event, campaignId) {
            event.preventDefault();
            event.stopPropagation();

            const btn = event.currentTarget;
            const icon = btn.querySelector('i');

            // Toggle active state
            btn.classList.toggle('active');

            // Toggle icon between regular and solid heart
            if (btn.classList.contains('active')) {
                icon.classList.remove('far');
                icon.classList.add('fas');

                // Save to localStorage
                let favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');
                if (!favorites.includes(campaignId)) {
                    favorites.push(campaignId);
                    localStorage.setItem('favoriteCampaigns', JSON.stringify(favorites));
                }
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');

                // Remove from localStorage
                let favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');
                favorites = favorites.filter(id => id !== campaignId);
                localStorage.setItem('favoriteCampaigns', JSON.stringify(favorites));
            }
        }

        // Check favorited campaigns on page load
        (function() {
            const favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');

            document.querySelectorAll('.campaign-card-favorite').forEach(btn => {
                const onclick = btn.getAttribute('onclick');
                const campaignId = parseInt(onclick.match(/\d+/)[0]);

                if (favorites.includes(campaignId)) {
                    const icon = btn.querySelector('i');
                    btn.classList.add('active');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                }
            });
        })();

        // Filter favorites functionality
        let showingFavorites = false;

        function filterFavorites() {
            const favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');
            const campaignCards = document.querySelectorAll('.campaign-card');
            const filterBtn = document.getElementById('favorites-filter');
            const resultsCount = document.getElementById('results-count');
            const noResults = document.querySelector('.no-results');
            const loadMoreBtn = document.getElementById('load-more-btn');

            showingFavorites = !showingFavorites;

            if (showingFavorites) {
                // Show only favorites
                filterBtn.classList.add('active');
                let visibleCount = 0;

                campaignCards.forEach(card => {
                    const favoriteBtn = card.querySelector('.campaign-card-favorite');
                    const onclick = favoriteBtn.getAttribute('onclick');
                    const campaignId = parseInt(onclick.match(/\d+/)[0]);

                    if (favorites.includes(campaignId)) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                resultsCount.textContent = visibleCount;

                // Hide load more button when filtering favorites
                if (loadMoreBtn) {
                    loadMoreBtn.parentElement.style.display = 'none';
                }

                // Show/hide no results message
                if (visibleCount === 0) {
                    if (!noResults) {
                        const grid = document.querySelector('.campaigns-grid');
                        const msg = document.createElement('div');
                        msg.className = 'no-results';
                        msg.innerHTML = '<i class="fas fa-heart-broken"></i><p>No favorite campaigns yet. Click the heart icon on campaigns to add them to your favorites!</p>';
                        grid.parentElement.insertBefore(msg, grid);
                    }
                    document.querySelector('.campaigns-grid').style.display = 'none';
                } else {
                    if (noResults) noResults.style.display = 'none';
                    document.querySelector('.campaigns-grid').style.display = 'grid';
                }
            } else {
                // Show all campaigns
                filterBtn.classList.remove('active');

                campaignCards.forEach(card => {
                    card.style.display = '';
                });

                resultsCount.textContent = campaignCards.length;
                if (noResults) noResults.style.display = 'none';
                document.querySelector('.campaigns-grid').style.display = 'grid';

                // Show load more button if applicable
                if (loadMoreBtn) {
                    const totalCampaigns = parseInt(loadMoreBtn.getAttribute('data-total'));
                    if (campaignCards.length < totalCampaigns) {
                        loadMoreBtn.parentElement.style.display = 'flex';
                    }
                }
            }
        }

        // Load More functionality with AJAX
        const loadMoreBtn = document.getElementById('load-more-btn');

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const currentOffset = parseInt(loadMoreBtn.getAttribute('data-offset'));
                const totalCampaigns = parseInt(loadMoreBtn.getAttribute('data-total'));
                const grid = document.getElementById('campaigns-grid');

                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const params = new URLSearchParams();
                params.append('offset', currentOffset);
                params.append('limit', 20);

                // Add filters
                if (urlParams.has('search')) params.append('search', urlParams.get('search'));
                if (urlParams.has('filter')) params.append('filter', urlParams.get('filter'));
                if (urlParams.has('category')) params.append('category', urlParams.get('category'));
                if (urlParams.has('timeline')) params.append('timeline', urlParams.get('timeline'));
                if (urlParams.has('sort')) params.append('sort', urlParams.get('sort'));

                // Disable button and show loading
                loadMoreBtn.disabled = true;
                const originalText = loadMoreBtn.innerHTML;
                loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

                // Fetch more campaigns
                fetch('ajax-load-campaigns.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.campaigns.length > 0) {
                            // Get favorites from localStorage
                            const favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');

                            // Add campaigns to grid
                            data.campaigns.forEach(campaign => {
                                const isFavorite = favorites.includes(campaign.id);
                                const campaignCard = createCampaignCard(campaign, isFavorite);
                                grid.insertAdjacentHTML('beforeend', campaignCard);
                            });

                            // Update offset
                            const newOffset = currentOffset + 20;
                            loadMoreBtn.setAttribute('data-offset', newOffset);

                            // Hide button if no more campaigns
                            if (!data.hasMore || newOffset >= totalCampaigns) {
                                loadMoreBtn.parentElement.style.display = 'none';
                            }

                            // Update results count
                            const resultsCount = document.getElementById('results-count');
                            if (resultsCount) {
                                const displayedCount = document.querySelectorAll('.campaign-card').length;
                                resultsCount.textContent = displayedCount;
                            }
                        } else {
                            loadMoreBtn.parentElement.style.display = 'none';
                        }

                        // Re-enable button
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.innerHTML = originalText;
                    })
                    .catch(error => {
                        console.error('Error loading campaigns:', error);
                        loadMoreBtn.disabled = false;
                        loadMoreBtn.innerHTML = originalText;
                        alert('Failed to load more campaigns. Please try again.');
                    });
            });
        }

        // Function to create campaign card HTML
        function createCampaignCard(campaign, isFavorite) {
            const favoriteClass = isFavorite ? 'active' : '';
            const favoriteIcon = isFavorite ? 'fas' : 'far';
            const appliedBadge = campaign.my_application_status ?
                '<div class="campaign-card-applied"><i class="fas fa-check-circle"></i> Applied</div>' : '';

            return `
                <a href="campaign-detail.php?id=${campaign.id}" class="campaign-card" data-campaign-id="${campaign.id}">
                    <div class="campaign-card-image" style="background-image: url('${campaign.image}');">
                        ${campaign.category ? `<div class="campaign-card-badge"><i class="fas fa-tag"></i> ${campaign.category}</div>` : ''}
                        ${appliedBadge}
                        <div class="campaign-card-date">
                            <i class="fas fa-calendar"></i>
                            ${campaign.timing_start} - ${campaign.timing_end}
                        </div>
                        <div class="campaign-card-favorite ${favoriteClass}" onclick="toggleFavorite(event, ${campaign.id})">
                            <i class="${favoriteIcon} fa-heart"></i>
                        </div>
                    </div>
                    <div class="campaign-card-content">
                        <h3 class="campaign-card-title">${campaign.name}</h3>
                        <div class="campaign-card-brand-by">
                            BY: ${campaign.company_name.toUpperCase()}
                        </div>
                        <div class="campaign-card-meta">
                            <div class="meta-item-small">
                                <i class="fas fa-users"></i>
                                ${campaign.influencers_needed} Positions Available
                            </div>
                        </div>
                        <div class="campaign-card-footer">
                            ${campaign.budget ?
                                `<div class="campaign-card-compensation">€${Math.round(campaign.budget).toLocaleString()}</div>` :
                                `<div class="campaign-card-compensation">Compensation Available</div>`
                            }
                            <div class="campaign-card-cta">
                                View Details <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </a>
            `;
        }
    </script>

</body>
</html>
