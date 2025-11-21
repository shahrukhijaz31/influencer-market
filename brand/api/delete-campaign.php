<?php
/**
 * API endpoint to delete a campaign
 */

require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isBrand()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $campaignId = isset($input['campaign_id']) ? intval($input['campaign_id']) : 0;

    if (!$campaignId) {
        throw new Exception('Invalid campaign ID');
    }

    $pdo = getDBConnection();

    // Get brand profile
    $stmt = $pdo->prepare("SELECT id FROM brand_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $brandProfile = $stmt->fetch();

    // Check if campaign belongs to this brand
    $stmt = $pdo->prepare("SELECT id FROM campaigns WHERE id = ? AND brand_id = ?");
    $stmt->execute([$campaignId, $brandProfile['id']]);

    if (!$stmt->fetch()) {
        throw new Exception('Campaign not found or unauthorized');
    }

    // Delete campaign applications first
    $stmt = $pdo->prepare("DELETE FROM campaign_applications WHERE campaign_id = ?");
    $stmt->execute([$campaignId]);

    // Delete campaign
    $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ?");
    $stmt->execute([$campaignId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
