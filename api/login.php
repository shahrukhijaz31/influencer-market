<?php
/**
 * Casters.fi - Login API
 */

require_once '../includes/config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get input
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$userType = isset($_POST['user_type']) ? sanitize($_POST['user_type']) : '';

// Validate input
if (empty($email) || empty($password)) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

try {
    $pdo = getDBConnection();

    // Find user by email and type
    // If admin is selected, also check for manager type
    if ($userType === 'admin') {
        $stmt = $pdo->prepare("
            SELECT id, email, password, user_type, first_name, last_name, is_active, email_verified
            FROM users
            WHERE email = ? AND user_type IN ('admin', 'manager')
        ");
        $stmt->execute([$email]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, email, password, user_type, first_name, last_name, is_active, email_verified
            FROM users
            WHERE email = ? AND user_type = ?
        ");
        $stmt->execute([$email, $userType]);
    }
    $user = $stmt->fetch();

    // Check if user exists
    if (!$user) {
        jsonResponse(['error' => 'Invalid email or password'], 401);
    }

    // Check if account is active
    if (!$user['is_active']) {
        jsonResponse(['error' => 'Your account has been deactivated'], 403);
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        jsonResponse(['error' => 'Invalid email or password'], 401);
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['logged_in_at'] = time();

    // Determine redirect URL
    $redirectUrl = '';
    switch ($user['user_type']) {
        case 'admin':
        case 'manager':
            $redirectUrl = 'admin/dashboard.php';
            break;
        case 'brand':
            $redirectUrl = 'brand/dashboard.php';
            break;
        case 'influencer':
            $redirectUrl = 'influencer/dashboard.php';
            break;
    }

    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirectUrl,
        'user' => [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'type' => $user['user_type']
        ]
    ]);

} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>
