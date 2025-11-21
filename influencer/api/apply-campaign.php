<?php
/**
 * API endpoint to submit campaign application
 */

require_once '../../includes/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in and is an influencer
if (!isLoggedIn() || !isInfluencer()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $campaignId = isset($input['campaign_id']) ? intval($input['campaign_id']) : 0;
    $description = isset($input['description']) ? trim($input['description']) : '';

    // Validate inputs
    if ($campaignId <= 0) {
        throw new Exception('Invalid campaign ID');
    }

    if (strlen($description) < 50) {
        throw new Exception('Application message must be at least 50 characters');
    }

    $pdo = getDBConnection();

    // Get influencer profile ID
    $stmt = $pdo->prepare("SELECT id FROM influencer_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $influencer = $stmt->fetch();

    if (!$influencer) {
        throw new Exception('Influencer profile not found');
    }

    $influencerId = $influencer['id'];

    // Check if campaign exists and is active
    $stmt = $pdo->prepare("
        SELECT id, name, brand_id
        FROM campaigns
        WHERE id = ? AND status = 'active' AND is_public = TRUE
    ");
    $stmt->execute([$campaignId]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        throw new Exception('Campaign not found or not available');
    }

    // Check if already applied
    $stmt = $pdo->prepare("
        SELECT id
        FROM campaign_applications
        WHERE campaign_id = ? AND influencer_id = ?
    ");
    $stmt->execute([$campaignId, $influencerId]);

    if ($stmt->fetch()) {
        throw new Exception('You have already applied to this campaign');
    }

    // Insert application
    $stmt = $pdo->prepare("
        INSERT INTO campaign_applications
        (campaign_id, influencer_id, message, status)
        VALUES (?, ?, ?, 'pending')
    ");

    $stmt->execute([$campaignId, $influencerId, $description]);

    // Get the application ID
    $applicationId = $pdo->lastInsertId();

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'application_id' => $applicationId
    ]);

} catch (PDOException $e) {
    error_log("Campaign application error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
