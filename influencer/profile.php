<?php
/**
 * Casters.fi - Influencer Profile (Step 2)
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isInfluencer()) {
    redirect('login.html');
}

// Get influencer data
try {
    $pdo = getDBConnection();

    // Get profile
    $stmt = $pdo->prepare("
        SELECT u.*, ip.*
        FROM users u
        LEFT JOIN influencer_profiles ip ON u.id = ip.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();

    // Check profile completion for progress
    $completionItems = [
        'photo' => !empty($profile['profile_photo']),
        'bio' => !empty($profile['bio']),
        'social' => !empty($profile['instagram_username']) || !empty($profile['tiktok_username']) || !empty($profile['youtube_username']),
        'categories' => !empty($profile['categories']),
        'cities' => !empty($profile['cities_available'])
    ];
    $completedCount = count(array_filter($completionItems));
    $totalItems = count($completionItems);
    $completionPercent = round(($completedCount / $totalItems) * 100);

} catch (PDOException $e) {
    $error = $e->getMessage();
}

// Available categories
$categories = [
    'art_design' => ['Art & Design', 'fa-palette'],
    'beauty' => ['Beauty & Cosmetics', 'fa-spa'],
    'beer_wine_spirits' => ['Beer & Wine & Spirits', 'fa-wine-glass-alt'],
    'business_careers' => ['Business & Careers', 'fa-briefcase'],
    'cars_motorbikes' => ['Cars & Motorbikes', 'fa-car'],
    'coffee_tea_beverages' => ['Coffee & Tea & Beverages', 'fa-coffee'],
    'electronics_computers' => ['Electronics & Computers', 'fa-laptop'],
    'fashion' => ['Fashion', 'fa-tshirt'],
    'fitness' => ['Fitness & Yoga', 'fa-dumbbell'],
    'friends_family' => ['Friends & Family & Relationships', 'fa-users'],
    'gaming' => ['Gaming', 'fa-gamepad'],
    'healthcare_medicine' => ['Healthcare & Medicine', 'fa-heartbeat'],
    'healthy_lifestyle' => ['Healthy Lifestyle', 'fa-heart'],
    'home_garden' => ['Homedecor & Furniture & Garden', 'fa-couch'],
    'lifestyle' => ['Lifestyle', 'fa-star'],
    'luxury_goods' => ['Luxury Goods', 'fa-gem'],
    'music' => ['Music', 'fa-music'],
    'pets' => ['Pets', 'fa-paw'],
    'photography_ugc' => ['Photography & UGC', 'fa-camera'],
    'food_restaurant' => ['Restaurant & Foods & Grocery', 'fa-utensils'],
    'sports' => ['Sports', 'fa-futbol'],
    'sustainability' => ['Sustainability', 'fa-leaf'],
    'tv_film_books' => ['TV & Film & Books', 'fa-film'],
    'toys_children' => ['Toys & Children & Baby', 'fa-baby'],
    'travel' => ['Travel', 'fa-plane'],
    'wedding' => ['Wedding', 'fa-ring']
];

$selectedCategories = !empty($profile['categories']) ? explode(',', $profile['categories']) : [];

// Finnish cities list
$finnishCities = [
    'Helsinki', 'Espoo', 'Tampere', 'Vantaa', 'Oulu', 'Turku', 'Jyväskylä', 'Lahti',
    'Kuopio', 'Pori', 'Joensuu', 'Lappeenranta', 'Hämeenlinna', 'Vaasa', 'Rovaniemi',
    'Seinäjoki', 'Mikkeli', 'Kotka', 'Salo', 'Porvoo', 'Kouvola', 'Hyvinkää', 'Nurmijärvi',
    'Järvenpää', 'Rauma', 'Tuusula', 'Kirkkonummi', 'Kajaani', 'Kerava', 'Savonlinna'
];

$selectedCities = !empty($profile['cities_available']) ? explode(',', $profile['cities_available']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/chat.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        /* Compact Layout */
        .dashboard-content {
            padding: 1.5rem !important;
        }

        /* Form Inputs - More Compact */
        .form-input {
            padding: 0.625rem 0.875rem !important;
            font-size: 0.9rem !important;
        }

        .form-label {
            font-size: 0.8125rem !important;
            margin-bottom: 0.375rem !important;
            font-weight: 600 !important;
        }

        .form-group {
            margin-bottom: 0.875rem !important;
        }

        /* Step 2 Header */
        .step2-header {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .step2-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .step2-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .step-badges {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .step-badge {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .step-badge.completed {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .step-badge.current {
            background: var(--primary-gradient);
            color: white;
        }

        .progress-bar-container {
            max-width: 300px;
            margin: 0 auto;
        }

        .progress-bar {
            height: 8px;
            background: var(--bg-secondary);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 0.375rem;
        }

        /* Profile Sections */
        .profile-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
        }

        .profile-section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.95rem;
        }

        .section-info h3 {
            font-size: 1.05rem;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .section-info p {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            margin: 0;
        }

        /* Profile Photo */
        .profile-photo-upload {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.25rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            margin-bottom: 0;
        }

        .profile-photo-preview {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-full);
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 3px solid white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
        }

        .profile-photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo-preview i {
            font-size: 2.5rem;
            color: var(--text-light);
        }

        .photo-upload-info h4 {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .photo-upload-info p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
        }

        /* Social Media Cards */
        .social-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .social-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 1rem;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .social-card:focus-within {
            border-color: var(--primary-color);
            background: white;
        }

        .social-card-header {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 0.75rem;
        }

        .social-card-header i {
            font-size: 1.25rem;
        }

        .social-card-header .fa-instagram { color: #E4405F; }
        .social-card-header .fa-tiktok { color: #000000; }
        .social-card-header .fa-youtube { color: #FF0000; }
        .social-card-header .fa-facebook { color: #1877F2; }

        .social-card-header span {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .social-card-inputs {
            display: flex;
            gap: 0.5rem;
        }

        .social-card-inputs input:first-child {
            width: 75%;
        }

        .social-card-inputs input:last-child {
            width: 25%;
        }

        /* Category Grid */
        .category-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.625rem;
        }

        .category-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            font-size: 0.8125rem;
        }

        .category-item i {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .category-item span {
            color: var(--text-primary);
        }

        .category-item:hover {
            border-color: var(--primary-color);
            background: rgba(232, 121, 249, 0.05);
        }

        .category-item.selected {
            background: var(--primary-gradient);
            border-color: transparent;
            box-shadow: 0 2px 8px rgba(232, 121, 249, 0.3);
        }

        .category-item.selected i,
        .category-item.selected span {
            color: white;
        }

        /* Pricing Grid */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .pricing-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 1rem;
            text-align: center;
        }

        .pricing-card label {
            display: block;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .pricing-card .input-wrapper {
            position: relative;
        }

        .pricing-card .currency {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 12px;
        }

        .pricing-card input {
            width: 100%;
            text-align: center;
            padding-left: 28px;
        }

        /* Save Button - Fixed Top Right */
        .save-button-fixed {
            position: fixed;
            top: 90px;
            right: 2rem;
            z-index: 999;
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(232, 121, 249, 0.35);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .save-button-fixed:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(232, 121, 249, 0.5);
        }

        .save-button-fixed i {
            font-size: 1rem;
        }

        @media (max-width: 1024px) {
            .save-button-fixed {
                top: 75px;
                right: 1.5rem;
                padding: 0.65rem 1.5rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .save-button-fixed {
                position: fixed;
                bottom: 1rem;
                top: auto;
                right: 1rem;
                left: 1rem;
                width: calc(100% - 2rem);
                justify-content: center;
            }

            .social-cards,
            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .profile-photo-upload {
                flex-direction: column;
                text-align: center;
            }

            .step-badges {
                flex-wrap: wrap;
            }

            .pricing-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--multiple {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.5rem;
            min-height: 48px;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--primary-color);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background: var(--primary-gradient);
            border: none;
            color: white;
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            margin: 0.25rem;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 0.5rem;
            font-weight: bold;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .select2-dropdown {
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: var(--primary-color);
        }

        .select2-container--default .select2-search--inline .select2-search__field {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/influencer-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php $pageTitle = 'My Profile'; include '../includes/influencer-topbar.php'; ?>

            <div class="dashboard-content">
                <!-- Step 2 Header - Only show if profile is not complete -->
                <?php if ($completionPercent < 100): ?>
                <div class="step2-header">
                    <h1>Complete Your Profile</h1>
                    <p>Add your social media accounts, categories, and pricing to start receiving campaign offers</p>

                    <div class="step-badges">
                        <span class="step-badge completed">
                            <i class="fas fa-check"></i> Step 1: Basic Info
                        </span>
                        <span class="step-badge current">
                            <i class="fas fa-edit"></i> Step 2: Profile Details
                        </span>
                    </div>

                    <div class="progress-bar-container">
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: <?php echo $completionPercent; ?>%"></div>
                        </div>
                        <div class="progress-text"><?php echo $completionPercent; ?>% Complete</div>
                    </div>
                </div>
                <?php endif; ?>

                <form id="profileForm" enctype="multipart/form-data">
                    <!-- Profile Photo & Bio -->
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <div class="section-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="section-info">
                                <h3>Profile Photo & Bio</h3>
                                <p>Help brands get to know you better</p>
                            </div>
                        </div>

                        <div class="profile-photo-upload">
                            <div class="profile-photo-preview" id="photoPreview">
                                <?php if (!empty($profile['profile_photo'])): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($profile['profile_photo']); ?>" alt="Profile">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="photo-upload-info">
                                <h4>Profile Photo</h4>
                                <p>Upload a professional photo. JPG or PNG, max 2MB.</p>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display: none;">
                                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('profile_photo').click()">
                                    <i class="fas fa-camera"></i> Upload Photo
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Bio *</label>
                            <textarea class="form-input" name="bio" rows="3" placeholder="Tell brands about yourself, your content style, and what makes you unique..." style="resize: vertical; min-height: 80px;"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Social Media Accounts -->
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <div class="section-icon">
                                <i class="fas fa-share-alt"></i>
                            </div>
                            <div class="section-info">
                                <h3>Social Media Accounts</h3>
                                <p>Add at least one social media account</p>
                            </div>
                        </div>

                        <div class="social-cards">
                            <div class="social-card">
                                <div class="social-card-header">
                                    <i class="fab fa-instagram"></i>
                                    <span>Instagram</span>
                                </div>
                                <div class="social-card-inputs">
                                    <input type="text" class="form-input" name="instagram_username" placeholder="Username" value="<?php echo htmlspecialchars($profile['instagram_username'] ?? ''); ?>">
                                    <input type="number" class="form-input" name="instagram_followers" placeholder="Followers" value="">
                                </div>
                            </div>

                            <div class="social-card">
                                <div class="social-card-header">
                                    <i class="fab fa-tiktok"></i>
                                    <span>TikTok</span>
                                </div>
                                <div class="social-card-inputs">
                                    <input type="text" class="form-input" name="tiktok_username" placeholder="Username" value="<?php echo htmlspecialchars($profile['tiktok_username'] ?? ''); ?>">
                                    <input type="number" class="form-input" name="tiktok_followers" placeholder="Followers" value="">
                                </div>
                            </div>

                            <div class="social-card">
                                <div class="social-card-header">
                                    <i class="fab fa-youtube"></i>
                                    <span>YouTube</span>
                                </div>
                                <div class="social-card-inputs">
                                    <input type="text" class="form-input" name="youtube_username" placeholder="Channel name" value="<?php echo htmlspecialchars($profile['youtube_username'] ?? ''); ?>">
                                    <input type="number" class="form-input" name="youtube_followers" placeholder="Subscribers" value="<?php echo !empty($profile['youtube_followers']) && $profile['youtube_followers'] > 0 ? htmlspecialchars($profile['youtube_followers']) : ''; ?>">
                                </div>
                            </div>

                            <div class="social-card">
                                <div class="social-card-header">
                                    <i class="fab fa-facebook"></i>
                                    <span>Facebook</span>
                                </div>
                                <div class="social-card-inputs">
                                    <input type="text" class="form-input" name="facebook_url" placeholder="Page URL" value="<?php echo htmlspecialchars($profile['facebook_url'] ?? ''); ?>">
                                    <input type="number" class="form-input" name="facebook_likes" placeholder="Likes" value="<?php echo !empty($profile['facebook_likes']) && $profile['facebook_likes'] > 0 ? htmlspecialchars($profile['facebook_likes']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <div class="section-icon">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div class="section-info">
                                <h3>Categories</h3>
                                <p>Select up to 3 categories that best describe your content</p>
                            </div>
                        </div>

                        <div class="category-grid">
                            <?php foreach ($categories as $value => $data): ?>
                                <div class="category-item <?php echo in_array($value, $selectedCategories) ? 'selected' : ''; ?>" data-value="<?php echo $value; ?>">
                                    <i class="fas <?php echo $data[1]; ?>"></i>
                                    <span><?php echo $data[0]; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="categories" id="selectedCategories" value="<?php echo htmlspecialchars($profile['categories'] ?? ''); ?>">
                    </div>

                    <!-- Cities Available -->
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <div class="section-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="section-info">
                                <h3>Cities You're Available In</h3>
                                <p>Select the cities where you can create content for campaigns</p>
                            </div>
                        </div>

                        <select class="form-input" id="citiesSelect" name="cities_available" multiple="multiple" style="width: 100%;">
                            <?php foreach ($finnishCities as $city): ?>
                                <option value="<?php echo $city; ?>" <?php echo in_array($city, $selectedCities) ? 'selected' : ''; ?>>
                                    <?php echo $city; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Pricing -->
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <div class="section-icon">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <div class="section-info">
                                <h3>Pricing</h3>
                                <p>Set your rates for different content types (optional)</p>
                            </div>
                        </div>

                        <div class="pricing-grid">
                            <div class="pricing-card">
                                <label>Instagram Post</label>
                                <div class="input-wrapper">
                                    <span class="currency">€</span>
                                    <input type="number" class="form-input" name="price_instagram_post" placeholder="0" value="">
                                </div>
                            </div>
                            <div class="pricing-card">
                                <label>Instagram Story</label>
                                <div class="input-wrapper">
                                    <span class="currency">€</span>
                                    <input type="number" class="form-input" name="price_instagram_story" placeholder="0" value="">
                                </div>
                            </div>
                            <div class="pricing-card">
                                <label>Instagram Reel</label>
                                <div class="input-wrapper">
                                    <span class="currency">€</span>
                                    <input type="number" class="form-input" name="price_instagram_reel" placeholder="0" value="">
                                </div>
                            </div>
                            <div class="pricing-card">
                                <label>TikTok Video</label>
                                <div class="input-wrapper">
                                    <span class="currency">€</span>
                                    <input type="number" class="form-input" name="price_tiktok" placeholder="0" value="<?php echo !empty($profile['price_tiktok']) && $profile['price_tiktok'] > 0 ? htmlspecialchars($profile['price_tiktok']) : ''; ?>">
                                </div>
                            </div>
                            <div class="pricing-card">
                                <label>YouTube Video</label>
                                <div class="input-wrapper">
                                    <span class="currency">€</span>
                                    <input type="number" class="form-input" name="price_youtube" placeholder="0" value="<?php echo !empty($profile['price_youtube']) && $profile['price_youtube'] > 0 ? htmlspecialchars($profile['price_youtube']) : ''; ?>">
                                </div>
                            </div>
                            <div class="pricing-card">
                                <label>Blog Post</label>
                                <div class="input-wrapper">
                                    <span class="currency">€</span>
                                    <input type="number" class="form-input" name="price_blog" placeholder="0" value="<?php echo !empty($profile['price_blog']) && $profile['price_blog'] > 0 ? htmlspecialchars($profile['price_blog']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Save Button (Top Right) -->
                    <button type="submit" class="save-button-fixed">
                        <i class="fas fa-save"></i>
                        Save Profile
                    </button>
                </form>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
    <script>
        // Initialize Select2 for cities with tags support
        $(document).ready(function() {
            $('#citiesSelect').select2({
                tags: true,
                placeholder: 'Select or type cities...',
                allowClear: true,
                tokenSeparators: [','],
                createTag: function (params) {
                    var term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: term,
                        text: term,
                        newTag: true
                    };
                }
            });
        });

        // Profile photo preview
        document.getElementById('profile_photo').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Category selection
        const categoryItems = document.querySelectorAll('.category-item');
        const categoriesInput = document.getElementById('selectedCategories');
        let selectedCategories = categoriesInput.value ? categoriesInput.value.split(',') : [];

        categoryItems.forEach(item => {
            item.addEventListener('click', function() {
                const value = this.dataset.value;

                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    selectedCategories = selectedCategories.filter(v => v !== value);
                } else {
                    if (selectedCategories.length < 3) {
                        this.classList.add('selected');
                        selectedCategories.push(value);
                    } else {
                        showToast('You can select up to 3 categories', 'error');
                    }
                }

                categoriesInput.value = selectedCategories.join(',');
            });
        });

        // Form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            showToast('Saving profile...', 'info');

            fetch('../api/update-profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Profile saved successfully! Redirecting to dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1500);
                } else {
                    showToast(data.error || 'Failed to save profile', 'error');
                }
            })
            .catch(error => {
                showToast('Connection error. Please try again.', 'error');
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
