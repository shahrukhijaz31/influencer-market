<?php
/**
 * Casters.fi - Influencer Registration API
 */

require_once '../includes/config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// Get input
$firstName = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$dateOfBirth = isset($_POST['date_of_birth']) ? sanitize($_POST['date_of_birth']) : '';
$country = isset($_POST['country']) ? sanitize($_POST['country']) : '';
$creatorType = isset($_POST['creator_type']) ? sanitize($_POST['creator_type']) : '';
$referralCode = isset($_POST['referral_code']) ? sanitize($_POST['referral_code']) : '';
$hearAboutUs = isset($_POST['hear_about_us']) ? sanitize($_POST['hear_about_us']) : '';

// Validate required fields individually
if (empty($firstName)) {
    jsonResponse(['error' => 'First Name is required', 'field' => 'first_name'], 400);
}
if (empty($lastName)) {
    jsonResponse(['error' => 'Last Name is required', 'field' => 'last_name'], 400);
}
if (empty($email)) {
    jsonResponse(['error' => 'Email is required', 'field' => 'email'], 400);
}
if (empty($password)) {
    jsonResponse(['error' => 'Password is required', 'field' => 'password'], 400);
}
if (empty($dateOfBirth)) {
    jsonResponse(['error' => 'Date of Birth is required', 'field' => 'date_of_birth'], 400);
}
if (empty($country)) {
    jsonResponse(['error' => 'Country is required', 'field' => 'countryInput'], 400);
}
if (empty($creatorType)) {
    jsonResponse(['error' => 'Creator Type is required', 'field' => 'creator_type'], 400);
}
if (empty($hearAboutUs)) {
    jsonResponse(['error' => 'Please tell us how you heard about us', 'field' => 'hear_about_us'], 400);
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email format', 'field' => 'email'], 400);
}

// Validate password
if (strlen($password) < 8) {
    jsonResponse(['error' => 'Password must be at least 8 characters', 'field' => 'password'], 400);
}

if ($password !== $confirmPassword) {
    jsonResponse(['error' => 'Passwords do not match', 'field' => 'confirm_password'], 400);
}

// Validate creator type
if (!in_array($creatorType, ['influencer', 'content_creator', 'both'])) {
    jsonResponse(['error' => 'Invalid creator type', 'field' => 'creator_type'], 400);
}


try {
    $pdo = getDBConnection();

    // Check if email already exists (email must be unique across all user types)
    $stmt = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $type = ucfirst($existingUser['user_type']);
        jsonResponse(['error' => "Email address is already registered as {$type}. Each email can only have one account type.", 'field' => 'email'], 400);
    }

    // Start transaction
    $pdo->beginTransaction();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, user_type, first_name, last_name, date_of_birth, country, is_active, email_verified, created_at)
        VALUES (?, ?, 'influencer', ?, ?, ?, ?, TRUE, FALSE, NOW())
    ");
    $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $dateOfBirth, $country]);
    $userId = $pdo->lastInsertId();

    // Insert influencer profile
    $stmt = $pdo->prepare("
        INSERT INTO influencer_profiles (user_id, creator_type, referral_code, hear_about_us, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $creatorType, $referralCode, $hearAboutUs]);

    // Commit transaction
    $pdo->commit();

    // TODO: Send welcome email

    jsonResponse([
        'success' => true,
        'message' => 'Account created successfully',
        'redirect' => 'login.html'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>
