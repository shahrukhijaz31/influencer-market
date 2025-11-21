<?php
/**
 * AJAX endpoint for loading campaigns with pagination
 */

require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isInfluencer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Get influencer profile
    $stmt = $pdo->prepare("SELECT id FROM influencer_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $influencer = $stmt->fetch();

    // Get pagination parameters
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

    // Get filter parameters
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

    // Add pagination
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();

    // Get total count for the same filters
    $countSql = "
        SELECT COUNT(*) as total
        FROM campaigns c
        JOIN brand_profiles bp ON c.brand_id = bp.id
        WHERE c.status = 'active' AND c.is_public = TRUE
    ";

    $countParams = [];

    // Add same filters to count query
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
    $totalCount = $countStmt->fetch()['total'];

    // Format campaigns data
    $campaignsData = [];
    foreach ($campaigns as $campaign) {
        $galleryImages = !empty($campaign['gallery_images']) ? explode(',', $campaign['gallery_images']) : [];
        $firstImage = $campaign['hero_image'] ?? $campaign['image'] ?? (!empty($galleryImages) ? trim($galleryImages[0]) : '');

        $campaignsData[] = [
            'id' => $campaign['id'],
            'name' => htmlspecialchars($campaign['name']),
            'company_name' => htmlspecialchars($campaign['company_name']),
            'category' => htmlspecialchars($campaign['category']),
            'image' => htmlspecialchars($firstImage),
            'budget' => $campaign['budget'],
            'influencers_needed' => $campaign['influencers_needed'],
            'timing_start' => date('M d', strtotime($campaign['timing_start'])),
            'timing_end' => date('M d', strtotime($campaign['timing_end'])),
            'my_application_status' => $campaign['my_application_status']
        ];
    }

    echo json_encode([
        'success' => true,
        'campaigns' => $campaignsData,
        'total' => $totalCount,
        'offset' => $offset,
        'limit' => $limit,
        'hasMore' => ($offset + $limit) < $totalCount
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
