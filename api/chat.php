<?php
/**
 * Casters.fi - Chat API
 */

require_once '../includes/config.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'conversations':
        getConversations();
        break;
    case 'messages':
        getMessages();
        break;
    case 'send':
        sendMessage();
        break;
    case 'users':
        getUsers();
        break;
    case 'mark_read':
        markAsRead();
        break;
    case 'unread_count':
        getUnreadCount();
        break;
    case 'edit':
        editMessage();
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

function getConversations() {
    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT
            CASE
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END as other_user_id,
            u.first_name,
            u.last_name,
            u.user_type,
            (SELECT message FROM messages
             WHERE (sender_id = ? AND receiver_id = other_user_id)
                OR (sender_id = other_user_id AND receiver_id = ?)
             ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages
             WHERE (sender_id = ? AND receiver_id = other_user_id)
                OR (sender_id = other_user_id AND receiver_id = ?)
             ORDER BY created_at DESC LIMIT 1) as last_message_time,
            (SELECT COUNT(*) FROM messages
             WHERE sender_id = other_user_id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
        WHERE m.sender_id = ? OR m.receiver_id = ?
        GROUP BY other_user_id
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
    $conversations = $stmt->fetchAll();

    jsonResponse(['conversations' => $conversations]);
}

function getMessages() {
    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];
    $otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if (!$otherUserId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }

    // Get messages
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, m.message, m.attachment, m.attachment_name, m.is_read, m.is_edited, m.edited_at, m.created_at,
               CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as direction
        FROM messages m
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
        LIMIT 100
    ");
    $stmt->execute([$userId, $userId, $otherUserId, $otherUserId, $userId]);
    $messages = $stmt->fetchAll();

    // Get other user info
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, user_type FROM users WHERE id = ?");
    $stmt->execute([$otherUserId]);
    $otherUser = $stmt->fetch();

    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages SET is_read = 1
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$otherUserId, $userId]);

    jsonResponse([
        'messages' => $messages,
        'user' => $otherUser
    ]);
}

function sendMessage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];

    $receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $attachment = null;
    $attachmentName = null;

    // Handle file upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
                         'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'text/plain'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        $fileType = $_FILES['attachment']['type'];
        $fileSize = $_FILES['attachment']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            jsonResponse(['error' => 'Invalid file type. Allowed: images, PDF, DOC, DOCX, TXT'], 400);
        }

        if ($fileSize > $maxSize) {
            jsonResponse(['error' => 'File too large. Maximum size is 10MB'], 400);
        }

        // Create upload directory if not exists
        $uploadDir = '../uploads/chat/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('chat_') . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)) {
            $attachment = '/casters/uploads/chat/' . $filename;
            $attachmentName = $_FILES['attachment']['name'];
        } else {
            jsonResponse(['error' => 'Failed to upload file'], 500);
        }
    }

    // Require message or attachment
    if (!$receiverId || (!$message && !$attachment)) {
        jsonResponse(['error' => 'Receiver and message or attachment required'], 400);
    }

    // Check if user can message (brands need Level 2)
    if (isBrand()) {
        $stmt = $pdo->prepare("
            SELECT subscription_level FROM brand_profiles WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $brand = $stmt->fetch();

        if (!$brand || $brand['subscription_level'] !== 'level2') {
            jsonResponse(['error' => 'Level 2 subscription required for messaging'], 403);
        }
    }

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, attachment, attachment_name, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $receiverId, $message, $attachment, $attachmentName]);

    $messageId = $pdo->lastInsertId();

    // Get the inserted message
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $newMessage = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'message' => $newMessage
    ]);
}

function getUsers() {
    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

    // Brands can only message influencers, influencers can message brands
    if ($userType === 'brand') {
        $targetType = 'influencer';
    } else if ($userType === 'influencer') {
        $targetType = 'brand';
    } else {
        // Admins can message anyone
        $targetType = '%';
    }

    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.user_type,
               COALESCE(bp.company_name, CONCAT(u.first_name, ' ', u.last_name)) as display_name
        FROM users u
        LEFT JOIN brand_profiles bp ON u.id = bp.user_id
        WHERE u.id != ?
          AND u.user_type LIKE ?
          AND u.is_active = 1
          AND (u.first_name LIKE ? OR u.last_name LIKE ? OR bp.company_name LIKE ?)
        ORDER BY u.first_name ASC
        LIMIT 20
    ");
    $stmt->execute([$userId, $targetType, $search, $search, $search]);
    $users = $stmt->fetchAll();

    jsonResponse(['users' => $users]);
}

function markAsRead() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];
    $senderId = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : 0;

    if (!$senderId) {
        jsonResponse(['error' => 'Sender ID required'], 400);
    }

    $stmt = $pdo->prepare("
        UPDATE messages SET is_read = 1
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$senderId, $userId]);

    jsonResponse(['success' => true]);
}

function getUnreadCount() {
    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();

    jsonResponse(['unread_count' => (int)$count]);
}

function editMessage() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'Method not allowed'], 405);
    }

    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];

    $messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
    $newMessage = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (!$messageId || !$newMessage) {
        jsonResponse(['error' => 'Message ID and new message required'], 400);
    }

    // Check if user owns this message
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->execute([$messageId, $userId]);
    $message = $stmt->fetch();

    if (!$message) {
        jsonResponse(['error' => 'Message not found or not authorized'], 404);
    }

    // Update the message
    $stmt = $pdo->prepare("
        UPDATE messages
        SET message = ?, is_edited = 1, edited_at = NOW()
        WHERE id = ? AND sender_id = ?
    ");
    $stmt->execute([$newMessage, $messageId, $userId]);

    // Get updated message
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $updatedMessage = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'message' => $updatedMessage
    ]);
}
?>
