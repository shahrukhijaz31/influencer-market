<?php
/**
 * Casters.fi - Admin: Edit Influencer
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.html');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get categories
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT name FROM categories WHERE is_active = 1 ORDER BY name");
    $allCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allCategories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();

        // Update user table
        $stmt = $pdo->prepare("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                date_of_birth = ?,
                country = ?,
                bio = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE id = ? AND user_type = 'influencer'
        ");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['date_of_birth'],
            $_POST['country'],
            $_POST['bio'],
            isset($_POST['is_active']) ? 1 : 0,
            $id
        ]);

        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM influencer_profiles WHERE user_id = ?");
        $stmt->execute([$id]);
        $profileExists = $stmt->fetch();

        if ($profileExists) {
            // Update influencer profile
            $stmt = $pdo->prepare("
                UPDATE influencer_profiles SET
                    creator_type = ?,
                    instagram_url = ?,
                    tiktok_url = ?,
                    youtube_url = ?,
                    facebook_url = ?,
                    instagram_followers = ?,
                    tiktok_followers = ?,
                    youtube_followers = ?,
                    cities_available = ?,
                    price_instagram_post = ?,
                    price_instagram_story = ?,
                    price_instagram_reel = ?,
                    price_tiktok_post = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $_POST['creator_type'],
                $_POST['instagram_url'],
                $_POST['tiktok_url'],
                $_POST['youtube_url'],
                $_POST['facebook_url'],
                $_POST['instagram_followers'],
                $_POST['tiktok_followers'],
                $_POST['youtube_followers'],
                $_POST['cities_available'],
                $_POST['price_instagram_post'] ?: null,
                $_POST['price_instagram_story'] ?: null,
                $_POST['price_instagram_reel'] ?: null,
                $_POST['price_tiktok_post'] ?: null,
                $id
            ]);
            $profileId = $profileExists['id'];
        } else {
            // Create influencer profile
            $stmt = $pdo->prepare("
                INSERT INTO influencer_profiles (
                    user_id, creator_type, instagram_url, tiktok_url, youtube_url, facebook_url,
                    instagram_followers, tiktok_followers, youtube_followers, cities_available,
                    price_instagram_post, price_instagram_story, price_instagram_reel, price_tiktok_post
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $_POST['creator_type'],
                $_POST['instagram_url'],
                $_POST['tiktok_url'],
                $_POST['youtube_url'],
                $_POST['facebook_url'],
                $_POST['instagram_followers'],
                $_POST['tiktok_followers'],
                $_POST['youtube_followers'],
                $_POST['cities_available'],
                $_POST['price_instagram_post'] ?: null,
                $_POST['price_instagram_story'] ?: null,
                $_POST['price_instagram_reel'] ?: null,
                $_POST['price_tiktok_post'] ?: null
            ]);
            $profileId = $pdo->lastInsertId();
        }

        // Update categories
        $stmt = $pdo->prepare("DELETE FROM influencer_categories WHERE influencer_id = ?");
        $stmt->execute([$profileId]);

        if (!empty($_POST['categories'])) {
            $stmt = $pdo->prepare("INSERT INTO influencer_categories (influencer_id, category) VALUES (?, ?)");
            foreach ($_POST['categories'] as $category) {
                $stmt->execute([$profileId, $category]);
            }
        }

        $success = "Influencer updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating influencer: " . $e->getMessage();
    }
}

// Get influencer data
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, ip.*
        FROM users u
        LEFT JOIN influencer_profiles ip ON u.id = ip.user_id
        WHERE u.id = ? AND u.user_type = 'influencer'
    ");
    $stmt->execute([$id]);
    $influencer = $stmt->fetch();

    if (!$influencer) {
        redirect('admin/influencers.php');
    }

    // Get selected categories
    if ($influencer['id']) {
        $stmt = $pdo->prepare("SELECT category FROM influencer_categories WHERE influencer_id = ?");
        $stmt->execute([$influencer['id']]);
        $selectedCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $selectedCategories = [];
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
    <title>Edit Influencer - Admin - Casters.fi</title>
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
                <a href="influencers.php" class="sidebar-link active">
                    <i class="fas fa-users"></i>
                    <span>Influencers</span>
                </a>
                <a href="brands.php" class="sidebar-link">
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
                    <h1 class="topbar-title">Edit Influencer</h1>
                </div>
                <div class="topbar-right">
                    <a href="influencers.php" class="btn btn-outline">
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
                    <!-- Basic Information -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Basic Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-input" name="first_name" value="<?php echo htmlspecialchars($influencer['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-input" name="last_name" value="<?php echo htmlspecialchars($influencer['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($influencer['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-input" name="phone" value="<?php echo htmlspecialchars($influencer['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-input" name="date_of_birth" value="<?php echo $influencer['date_of_birth'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Country</label>
                                    <select class="form-select" name="country">
                                        <option value="">Select country</option>
                                        <option value="FI" <?php echo ($influencer['country'] ?? '') === 'FI' ? 'selected' : ''; ?>>Finland</option>
                                        <option value="SE" <?php echo ($influencer['country'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sweden</option>
                                        <option value="NO" <?php echo ($influencer['country'] ?? '') === 'NO' ? 'selected' : ''; ?>>Norway</option>
                                        <option value="DK" <?php echo ($influencer['country'] ?? '') === 'DK' ? 'selected' : ''; ?>>Denmark</option>
                                        <option value="EE" <?php echo ($influencer['country'] ?? '') === 'EE' ? 'selected' : ''; ?>>Estonia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bio</label>
                                <textarea class="form-textarea" name="bio" rows="4"><?php echo htmlspecialchars($influencer['bio'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Creator Type</label>
                                <select class="form-select" name="creator_type">
                                    <option value="influencer" <?php echo ($influencer['creator_type'] ?? '') === 'influencer' ? 'selected' : ''; ?>>Influencer</option>
                                    <option value="content_creator" <?php echo ($influencer['creator_type'] ?? '') === 'content_creator' ? 'selected' : ''; ?>>Content Creator</option>
                                    <option value="both" <?php echo ($influencer['creator_type'] ?? '') === 'both' ? 'selected' : ''; ?>>Both</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="is_active" <?php echo ($influencer['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Account Active</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Social Media</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><i class="fab fa-instagram"></i> Instagram URL</label>
                                    <input type="url" class="form-input" name="instagram_url" value="<?php echo htmlspecialchars($influencer['instagram_url'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Instagram Followers</label>
                                    <input type="number" class="form-input" name="instagram_followers" value="<?php echo $influencer['instagram_followers'] ?? 0; ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><i class="fab fa-tiktok"></i> TikTok URL</label>
                                    <input type="url" class="form-input" name="tiktok_url" value="<?php echo htmlspecialchars($influencer['tiktok_url'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">TikTok Followers</label>
                                    <input type="number" class="form-input" name="tiktok_followers" value="<?php echo $influencer['tiktok_followers'] ?? 0; ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label"><i class="fab fa-youtube"></i> YouTube URL</label>
                                    <input type="url" class="form-input" name="youtube_url" value="<?php echo htmlspecialchars($influencer['youtube_url'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">YouTube Subscribers</label>
                                    <input type="number" class="form-input" name="youtube_followers" value="<?php echo $influencer['youtube_followers'] ?? 0; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><i class="fab fa-facebook"></i> Facebook URL</label>
                                <input type="url" class="form-input" name="facebook_url" value="<?php echo htmlspecialchars($influencer['facebook_url'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Pricing (Not visible to brands)</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Instagram Post (€)</label>
                                    <input type="number" step="0.01" class="form-input" name="price_instagram_post" value="<?php echo $influencer['price_instagram_post'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Instagram Story (€)</label>
                                    <input type="number" step="0.01" class="form-input" name="price_instagram_story" value="<?php echo $influencer['price_instagram_story'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Instagram Reel (€)</label>
                                    <input type="number" step="0.01" class="form-input" name="price_instagram_reel" value="<?php echo $influencer['price_instagram_reel'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">TikTok Post (€)</label>
                                    <input type="number" step="0.01" class="form-input" name="price_tiktok_post" value="<?php echo $influencer['price_tiktok_post'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Location & Categories -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Location & Categories</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Cities Available In</label>
                                <input type="text" class="form-input" name="cities_available" value="<?php echo htmlspecialchars($influencer['cities_available'] ?? ''); ?>" placeholder="e.g., Helsinki, Espoo, Tampere">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Categories (up to 3)</label>
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                                    <?php foreach ($allCategories as $cat): ?>
                                    <label class="form-check">
                                        <input type="checkbox" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>" <?php echo in_array($cat, $selectedCategories) ? 'checked' : ''; ?>>
                                        <span style="font-size: 0.875rem;"><?php echo htmlspecialchars($cat); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
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
