<?php
/**
 * Casters.fi - Admin: Edit Brand
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.html');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();

        // Update user table
        $stmt = $pdo->prepare("
            UPDATE users SET
                email = ?,
                phone = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE id = ? AND user_type = 'brand'
        ");
        $stmt->execute([
            $_POST['email'],
            $_POST['contact_person_phone'],
            isset($_POST['is_active']) ? 1 : 0,
            $id
        ]);

        // Update password if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$password, $id]);
        }

        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM brand_profiles WHERE user_id = ?");
        $stmt->execute([$id]);
        $profileExists = $stmt->fetch();

        if ($profileExists) {
            // Update brand profile
            $stmt = $pdo->prepare("
                UPDATE brand_profiles SET
                    company_name = ?,
                    website_url = ?,
                    company_description = ?,
                    contact_person_name = ?,
                    contact_person_phone = ?,
                    contact_person_email = ?,
                    instagram_url = ?,
                    tiktok_url = ?,
                    facebook_url = ?,
                    needs = ?,
                    goals = ?,
                    hear_about_us = ?,
                    newsletter_subscribed = ?,
                    tax_number = ?,
                    subscription_level = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
        } else {
            // Create brand profile
            $stmt = $pdo->prepare("
                INSERT INTO brand_profiles (
                    company_name, website_url, company_description,
                    contact_person_name, contact_person_phone, contact_person_email,
                    instagram_url, tiktok_url, facebook_url,
                    needs, goals, hear_about_us, newsletter_subscribed, tax_number, subscription_level, user_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
        }

        $stmt->execute([
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
            $_POST['subscription_level'],
            $id
        ]);

        $success = "Brand updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating brand: " . $e->getMessage();
    }
}

// Get brand data
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, bp.*
        FROM users u
        LEFT JOIN brand_profiles bp ON u.id = bp.user_id
        WHERE u.id = ? AND u.user_type = 'brand'
    ");
    $stmt->execute([$id]);
    $brand = $stmt->fetch();

    if (!$brand) {
        redirect('admin/brands.php');
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Brand - Admin - Casters.fi</title>
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
                    <h1 class="topbar-title">Edit Brand</h1>
                </div>
                <div class="topbar-right">
                    <a href="brands.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if (isset($success)): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="dashboard-form">
                    <!-- Account Credentials -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Account Credentials</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($brand['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-input" name="password" minlength="8">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="is_active" <?php echo ($brand['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Account Active</span>
                                </label>
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
                                    <input type="text" class="form-input" name="company_name" value="<?php echo htmlspecialchars($brand['company_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Website URL</label>
                                    <input type="url" class="form-input" name="website_url" value="<?php echo htmlspecialchars($brand['website_url'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Description</label>
                                <textarea class="form-textarea" name="company_description" rows="4"><?php echo htmlspecialchars($brand['company_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tax Number</label>
                                <input type="text" class="form-input" name="tax_number" value="<?php echo htmlspecialchars($brand['tax_number'] ?? ''); ?>">
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
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-input" name="contact_person_name" value="<?php echo htmlspecialchars($brand['contact_person_name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-input" name="contact_person_phone" value="<?php echo htmlspecialchars($brand['contact_person_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-input" name="contact_person_email" value="<?php echo htmlspecialchars($brand['contact_person_email'] ?? ''); ?>">
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
                                    <input type="url" class="form-input" name="instagram_url" value="<?php echo htmlspecialchars($brand['instagram_url'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><i class="fab fa-tiktok"></i> TikTok</label>
                                    <input type="url" class="form-input" name="tiktok_url" value="<?php echo htmlspecialchars($brand['tiktok_url'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-facebook"></i> Facebook</label>
                                <input type="url" class="form-input" name="facebook_url" value="<?php echo htmlspecialchars($brand['facebook_url'] ?? ''); ?>">
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
                                <label class="form-label">Needs</label>
                                <textarea class="form-textarea" name="needs" rows="3"><?php echo htmlspecialchars($brand['needs'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Goals</label>
                                <textarea class="form-textarea" name="goals" rows="3"><?php echo htmlspecialchars($brand['goals'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">How did you hear about us?</label>
                                <select class="form-select" name="hear_about_us">
                                    <option value="">Select</option>
                                    <option value="social_media" <?php echo ($brand['hear_about_us'] ?? '') === 'social_media' ? 'selected' : ''; ?>>Social Media</option>
                                    <option value="google" <?php echo ($brand['hear_about_us'] ?? '') === 'google' ? 'selected' : ''; ?>>Google Search</option>
                                    <option value="referral" <?php echo ($brand['hear_about_us'] ?? '') === 'referral' ? 'selected' : ''; ?>>Referral</option>
                                    <option value="event" <?php echo ($brand['hear_about_us'] ?? '') === 'event' ? 'selected' : ''; ?>>Event/Conference</option>
                                    <option value="other" <?php echo ($brand['hear_about_us'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
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
                                    <option value="level1" <?php echo ($brand['subscription_level'] ?? '') === 'level1' ? 'selected' : ''; ?>>Level 1 - Essential</option>
                                    <option value="level2" <?php echo ($brand['subscription_level'] ?? '') === 'level2' ? 'selected' : ''; ?>>Level 2 - Professional</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="newsletter_subscribed" <?php echo ($brand['newsletter_subscribed'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Subscribe to newsletters, offers & tips</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
