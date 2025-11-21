<?php
/**
 * Casters.fi - Admin: Add Brand
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.html');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists.");
        }

        // Create user
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, user_type, first_name, last_name, phone, is_active, email_verified)
            VALUES (?, ?, 'brand', ?, ?, ?, 1, 1)
        ");
        $stmt->execute([
            $_POST['email'],
            $password,
            $_POST['contact_person_name'],
            '',
            $_POST['contact_person_phone']
        ]);
        $userId = $pdo->lastInsertId();

        // Create brand profile
        $stmt = $pdo->prepare("
            INSERT INTO brand_profiles (
                user_id, company_name, website_url, company_description,
                contact_person_name, contact_person_phone, contact_person_email,
                instagram_url, tiktok_url, facebook_url,
                needs, goals, hear_about_us, newsletter_subscribed, tax_number, subscription_level
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $_POST['company_name'],
            $_POST['website_url'],
            $_POST['company_description'],
            $_POST['contact_person_name'],
            $_POST['contact_person_phone'],
            $_POST['contact_person_email'],
            $_POST['instagram_url'],
            $_POST['tiktok_url'],
            $_POST['facebook_url'],
            $_POST['needs'],
            $_POST['goals'],
            $_POST['hear_about_us'],
            isset($_POST['newsletter_subscribed']) ? 1 : 0,
            $_POST['tax_number'],
            $_POST['subscription_level']
        ]);

        header("Location: brands.php?success=1");
        exit;
    } catch (Exception $e) {
        $error = "Error creating brand: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Brand - Admin - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="sidebar-header">
                <a href="../index.html">
                    <img src="../assets/images/logo.png" alt="Casters.fi" class="sidebar-logo">
                </a>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">Users</p>
                </div>
                <a href="influencers.php" class="sidebar-link">
                    <i class="fas fa-users"></i>
                    <span>Influencers</span>
                </a>
                <a href="brands.php" class="sidebar-link active">
                    <i class="fas fa-building"></i>
                    <span>Brands</span>
                </a>
                <a href="managers.php" class="sidebar-link">
                    <i class="fas fa-user-tie"></i>
                    <span>Managers</span>
                </a>
                <div class="sidebar-section">
                    <p class="sidebar-section-title">System</p>
                </div>
                <a href="../api/logout.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div class="topbar-left">
                    <button class="mobile-sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="topbar-title">Add Brand</h1>
                </div>
                <div class="topbar-right">
                    <a href="brands.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (isset($error)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="dashboard-form">
                    <!-- Account Credentials -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Account Credentials (Step 1)</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-input" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-input" name="password" required minlength="8">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Company Information -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Company Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Company Name *</label>
                                    <input type="text" class="form-input" name="company_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Website URL</label>
                                    <input type="url" class="form-input" name="website_url">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Description</label>
                                <textarea class="form-textarea" name="company_description" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Number</label>
                                <input type="text" class="form-input" name="tax_number">
                            </div>
                        </div>
                    </div>

                    <!-- Contact Person -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Contact Person</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-input" name="contact_person_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-input" name="contact_person_phone">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-input" name="contact_person_email">
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Social Media Channels</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><i class="fab fa-instagram"></i> Instagram</label>
                                    <input type="url" class="form-input" name="instagram_url">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fab fa-tiktok"></i> TikTok</label>
                                    <input type="url" class="form-input" name="tiktok_url">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                                <input type="url" class="form-input" name="facebook_url">
                            </div>
                        </div>
                    </div>

                    <!-- Needs & Goals -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Needs & Goals</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Needs (Influencer marketing, UGC marketing, etc.)</label>
                                <textarea class="form-textarea" name="needs" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">What are your goals?</label>
                                <textarea class="form-textarea" name="goals" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">How did you hear about us?</label>
                                <select class="form-select" name="hear_about_us">
                                    <option value="">Select</option>
                                    <option value="social_media">Social Media</option>
                                    <option value="google">Google Search</option>
                                    <option value="referral">Referral</option>
                                    <option value="event">Event/Conference</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Subscription Level</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Subscription Level</label>
                                <select class="form-select" name="subscription_level">
                                    <option value="level1">Level 1 - Essential</option>
                                    <option value="level2">Level 2 - Professional</option>
                                </select>
                                <span class="form-hint">Level 2 includes: Browse influencer database, Direct messaging, Private campaign offers</span>
                            </div>
                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="newsletter_subscribed">
                                    <span>Subscribe to newsletters, offers & tips</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus"></i> Create Brand
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
