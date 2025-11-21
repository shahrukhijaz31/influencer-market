<?php
/**
 * API endpoint to send a message (Influencer)
 */

require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isInfluencer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $receiverId = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (!$receiverId || !$message) {
        throw new Exception('Invalid input');
    }

    $pdo = getDBConnection();

    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $receiverId, $message]);

    echo json_encode([
        'success' => true,
        'message_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
