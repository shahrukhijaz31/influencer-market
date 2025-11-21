<?php
/**
 * Casters.fi - Campaign Detail Page
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isInfluencer()) {
    redirect('login.html');
}

$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$campaign_id) {
    redirect('campaigns.php');
}

try {
    $pdo = getDBConnection();

    // Get campaign details with brand information
    $stmt = $pdo->prepare("
        SELECT c.*,
               bp.company_name, bp.website_url, bp.instagram_url, bp.tiktok_url,
               u.first_name, u.last_name, u.email, u.profile_picture
        FROM campaigns c
        JOIN brand_profiles bp ON c.brand_id = bp.id
        JOIN users u ON bp.user_id = u.id
        WHERE c.id = ? AND c.status = 'active'
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        $_SESSION['error'] = 'Campaign not found';
        redirect('campaigns.php');
    }

    // Get influencer profile
    $stmt = $pdo->prepare("SELECT id FROM influencer_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $influencer = $stmt->fetch();

    // Check if already applied
    $stmt = $pdo->prepare("
        SELECT id, status, applied_at FROM campaign_applications
        WHERE campaign_id = ? AND influencer_id = ?
    ");
    $stmt->execute([$campaign_id, $influencer['id']]);
    $application = $stmt->fetch();

    // Parse gallery images
    $galleryImages = !empty($campaign['gallery_images']) ? explode(',', $campaign['gallery_images']) : [];

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($campaign['name']); ?> - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Campaign Detail Page Styles - Modern Refined */
        .dashboard-content {
            padding: 1.5rem;
            max-width: 100%;
            background: #f8f8f8;
            margin-top: 0 !important;
        }

        /* Hero Section - Refined Style */
        .campaign-hero {
            position: relative;
            height: 420px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .campaign-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.75) 100%);
            z-index: 1;
        }

        .campaign-hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(to top, rgba(232, 121, 249, 0.15) 0%, transparent 100%);
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 10;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 4rem 2rem 3rem 2rem;
            color: white;
            max-width: 1200px;
            margin: 0 auto;
        }

        .campaign-category {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: white;
            backdrop-filter: blur(10px);
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 700;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            width: fit-content;
        }

        .campaign-category i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .campaign-category span {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .campaign-hero .campaign-category {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.625rem 1.25rem;
            background: white;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            width: fit-content;
            color: #262626;
        }

        .campaign-hero .campaign-category i {
            color: #e879f9;
            font-size: 0.875rem;
        }

        .campaign-title {
            font-size: 3rem;
            font-weight: 900;
            margin: 0 0 1.5rem 0;
            text-shadow: 0 4px 20px rgba(0,0,0,0.6);
            line-height: 1.2;
            letter-spacing: -0.5px;
            color: white;
        }

        .campaign-meta {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 1.25rem 1.75rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
        }

        .meta-item i {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            color: white;
        }

        /* Content Container - Two Column Layout */
        .campaign-container {
            max-width: 100%;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 1.5rem;
            align-items: start;
        }

        /* Left Column - Main Content */
        .campaign-main-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            min-width: 0;
        }

        /* Right Column - Sidebar */
        .campaign-sidebar {
            position: sticky;
            top: 100px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Stats Bar - Modern Minimal */
        .campaign-stats-bar {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .stat-box {
            text-align: center;
            flex: 1;
            padding: 0.5rem;
            border-right: 1px solid #f5f5f5;
        }

        .stat-box:last-child {
            border-right: none;
        }

        .stat-box-value {
            font-size: 1.375rem;
            font-weight: 600;
            color: #262626;
            margin-bottom: 0.375rem;
            display: block;
            letter-spacing: -0.02em;
        }

        .stat-box-label {
            font-size: 0.8125rem;
            color: #8e8e8e;
            font-weight: 400;
            display: block;
            letter-spacing: 0;
        }

        /* Countdown Timer */
        .countdown-timer {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            color: white;
            text-align: center;
            box-shadow: 0 4px 12px rgba(232, 121, 249, 0.25);
        }

        .countdown-label {
            font-size: 0.8125rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 0.75rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .countdown-display {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .countdown-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .countdown-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.375rem;
            font-variant-numeric: tabular-nums;
        }

        .countdown-unit {
            font-size: 0.6875rem;
            font-weight: 500;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .countdown-separator {
            font-size: 1.5rem;
            font-weight: 300;
            opacity: 0.6;
            margin: 0 -0.25rem;
            align-self: center;
            padding-top: 0.5rem;
        }

        /* Brand Info Card - Lighter Style */
        .brand-info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e5e5;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #f5f5f5;
        }

        .brand-logo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }

        .brand-logo-placeholder {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .brand-details {
            flex: 1;
        }

        .brand-details h2 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0 0 0.375rem 0;
            color: #262626;
            letter-spacing: -0.01em;
        }

        .brand-location, .brand-hours {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #8e8e8e;
            font-size: 0.8125rem;
            margin-bottom: 0.25rem;
            font-weight: 400;
        }

        .brand-location i, .brand-hours i {
            color: #8e8e8e;
            font-size: 0.75rem;
        }

        .brand-socials {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .social-link {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.9375rem;
            border: 1px solid #e5e5e5;
        }

        .social-link.instagram {
            background: linear-gradient(135deg, #f58529, #dd2a7b);
            color: white;
            border: none;
        }

        .social-link.tiktok {
            background: #000000;
            color: white;
            border: none;
        }

        .social-link.website {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }

        .social-link.no-link {
            background: #e5e5e5;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .social-link.no-link:hover {
            transform: none;
        }

        .social-link:hover {
            transform: scale(1.08);
        }

        /* Gallery Section - Show 3 Images */
        .gallery-section {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .gallery-item {
            position: relative;
            padding-bottom: 75%;
            background: #f5f5f5;
            overflow: hidden;
            border-radius: 8px;
            cursor: pointer;
        }

        .gallery-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.25s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(5px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 2;
            transition: background 0.3s ease;
        }

        .gallery-item:hover .gallery-item-overlay {
            background: rgba(0, 0, 0, 0.85);
        }

        .gallery-item-overlay-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .gallery-item-overlay-text {
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0.9;
        }

        /* Content Sections - Lighter Style */
        .content-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e5e5;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f5f5f5;
        }

        .section-icon {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.15) 0%, rgba(103, 232, 249, 0.15) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e879f9;
            font-size: 0.8125rem;
        }

        .section-header h3 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0;
            color: #262626;
            letter-spacing: -0.01em;
        }

        .section-content {
            color: #262626;
            line-height: 1.65;
            font-size: 0.9375rem;
            font-weight: 400;
        }

        .section-content ul {
            padding-left: 1.25rem;
            margin: 0.75rem 0;
        }

        .section-content li {
            margin-bottom: 0.5rem;
        }

        .section-content ol {
            counter-reset: item;
            list-style: none;
            padding-left: 0;
        }

        .section-content ol li {
            counter-increment: item;
            margin-bottom: 0.875rem;
            padding-left: 2.5rem;
            position: relative;
            line-height: 1.5;
        }

        .section-content ol li::before {
            content: counter(item);
            position: absolute;
            left: 0;
            top: 0;
            width: 28px;
            height: 28px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8125rem;
        }

        /* Compensation Highlight - Sidebar Style */
        .compensation-box {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            border: 1px solid rgba(232, 121, 249, 0.3);
            border-radius: 8px;
            padding: 1.25rem;
            text-align: center;
        }

        .compensation-amount {
            font-size: 1.375rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .compensation-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .compensation-label i {
            color: #e879f9;
        }

        /* Contact Details Card */
        .contact-details-card {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.08) 0%, rgba(103, 232, 249, 0.08) 100%);
            border: 1px solid rgba(232, 121, 249, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
        }

        .contact-details-header {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 1rem;
        }

        .contact-details-header i {
            width: 32px;
            height: 32px;
            background: var(--primary-gradient);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
        }

        .contact-details-header h3 {
            font-size: 0.9375rem;
            font-weight: 600;
            margin: 0;
            color: #262626;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 0.625rem;
            transition: all 0.2s ease;
        }

        .contact-item:hover {
            transform: translateX(3px);
            box-shadow: 0 2px 8px rgba(232, 121, 249, 0.15);
        }

        .contact-item:last-child {
            margin-bottom: 0;
        }

        .contact-item-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.15) 0%, rgba(103, 232, 249, 0.15) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e879f9;
            font-size: 0.9375rem;
            flex-shrink: 0;
        }

        .contact-item-content {
            flex: 1;
            min-width: 0;
        }

        .contact-item-label {
            font-size: 0.6875rem;
            color: #8e8e8e;
            font-weight: 500;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .contact-item-value {
            font-size: 0.875rem;
            color: #262626;
            font-weight: 500;
            word-wrap: break-word;
        }

        .contact-item-value a {
            color: #e879f9;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .contact-item-value a:hover {
            color: #67e8f9;
        }

        /* Favorite Button */
        .favorite-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .favorite-btn:hover {
            transform: scale(1.1);
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .favorite-btn i {
            font-size: 1.125rem;
            color: #262626;
            transition: all 0.3s ease;
        }

        .favorite-btn.active i {
            color: #e879f9;
            transform: scale(1.2);
        }

        .favorite-btn.active {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.15) 0%, rgba(103, 232, 249, 0.15) 100%);
            border-color: rgba(232, 121, 249, 0.3);
        }

        /* Gallery Lightbox */
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .lightbox.active {
            display: flex;
        }

        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }

        .lightbox-image {
            max-width: 100%;
            max-height: 90vh;
            border-radius: 8px;
        }

        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .lightbox-nav:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .lightbox-prev {
            left: -60px;
        }

        .lightbox-next {
            right: -60px;
        }

        .gallery-item {
            cursor: pointer;
        }

        /* Participate Button - Modern Style */
        .participate-sidebar-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.625rem;
            box-shadow: 0 4px 14px rgba(232, 121, 249, 0.3);
            letter-spacing: -0.01em;
        }

        .participate-sidebar-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(232, 121, 249, 0.4);
        }

        .participate-sidebar-btn:active {
            transform: translateY(0);
        }

        .applied-badge {
            padding: 1rem;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.08) 100%);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #059669;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9375rem;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .applied-badge-content {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .applied-badge-time {
            font-size: 0.75rem;
            font-weight: 500;
            opacity: 0.8;
        }

        /* Back Button - Compact */
        .back-btn {
            position: absolute;
            top: 1rem;
            left: 1rem;
            z-index: 50;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid #dbdbdb;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: white;
            border-color: #b2b2b2;
            transform: translateX(-3px);
        }

        /* Campaign Header Info - Refined */
        .campaign-header-info {
            background: white;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .campaign-header-info .campaign-category {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.08) 0%, rgba(103, 232, 249, 0.08) 100%);
            border: 1px solid rgba(232, 121, 249, 0.2);
            border-radius: 20px;
            font-size: 0.8125rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .campaign-header-info .campaign-category i {
            color: #e879f9;
            font-size: 0.75rem;
        }

        .campaign-header-info .campaign-category span {
            color: #262626;
        }

        .campaign-header-info h1 {
            font-size: 1.625rem;
            font-weight: 600;
            margin: 0 0 0.75rem 0;
            color: #262626;
            line-height: 1.35;
            letter-spacing: -0.02em;
        }

        .campaign-meta-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f5f5f5;
        }

        .meta-inline-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #8e8e8e;
            font-weight: 400;
        }

        .meta-inline-item i {
            color: #e879f9;
            font-size: 0.8125rem;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .campaign-container {
                grid-template-columns: 1fr 340px;
            }
        }

        @media (max-width: 1024px) {
            .campaign-container {
                grid-template-columns: 1fr;
            }

            .campaign-sidebar {
                position: relative;
                top: 0;
            }

            .campaign-stats-bar {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .stat-box {
                border-right: none;
                border-bottom: 1px solid #efefef;
                padding: 0.875rem 0.5rem;
            }

            .stat-box:nth-child(2n) {
                border-right: none;
            }

            .stat-box:nth-last-child(-n+2) {
                border-bottom: none;
            }
        }

        @media (max-width: 768px) {
            .dashboard-content {
                padding: 1rem;
            }

            .campaign-hero {
                height: 250px;
                border-radius: 8px;
            }

            .campaign-title {
                font-size: 1.5rem;
            }

            .campaign-meta {
                padding: 0.875rem;
                font-size: 0.875rem;
            }

            .campaign-stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }

            .stat-box-value {
                font-size: 1rem;
            }

            .stat-box-label {
                font-size: 0.6875rem;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .back-btn {
                padding: 0.5rem 0.875rem;
                font-size: 0.8125rem;
            }

            .content-section {
                padding: 1.25rem;
            }
        }

        /* Notification Modal */
        .notification-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10001;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .notification-modal.active {
            display: flex;
        }

        .notification-modal-content {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            animation: notificationPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes notificationPop {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .notification-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
        }

        .notification-icon.success {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
        }

        .notification-icon.error {
            background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
            color: white;
        }

        .notification-icon.warning {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
        }

        .notification-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.75rem;
        }

        .notification-message {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin: 0 0 1.5rem;
        }

        .notification-btn {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border: none;
            padding: 0.875rem 2.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .notification-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(232, 121, 249, 0.4);
        }

        .notification-btn.success {
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        }

        .notification-btn.success:hover {
            box-shadow: 0 8px 20px rgba(232, 121, 249, 0.4);
        }

        /* Application Modal */
        .apply-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .apply-modal.active {
            display: flex;
        }

        .apply-modal-content {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .apply-modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .apply-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .apply-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #999;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .apply-modal-close:hover {
            background: #f5f5f5;
            color: #333;
        }

        .apply-modal-body {
            padding: 1.5rem;
        }

        .apply-campaign-info {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .apply-campaign-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.125rem;
            color: #262626;
        }

        .apply-campaign-info p {
            margin: 0;
            color: #666;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.9375rem;
        }

        .form-group textarea {
            width: 100%;
            min-height: 150px;
            padding: 0.875rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #e879f9;
        }

        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #999;
            font-size: 0.8125rem;
        }

        .apply-modal-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-cancel {
            padding: 0.75rem 1.5rem;
            background: white;
            color: #666;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #f5f5f5;
            border-color: #bbb;
        }

        .btn-submit {
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(232, 121, 249, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/influencer-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="dashboard-main">
            <?php $pageTitle = 'Campaign Details'; include '../includes/influencer-topbar.php'; ?>

            <div class="dashboard-content">
                <!-- Hero Section -->
                <div class="campaign-hero" style="background-image: url('<?php echo htmlspecialchars($campaign['hero_image'] ?? $campaign['image']); ?>');">
                    <!-- Back Button -->
                    <a href="campaigns.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Campaigns
                    </a>

                    <!-- Favorite Button -->
                    <div class="favorite-btn" onclick="toggleFavorite(<?php echo $campaign_id; ?>)">
                        <i class="far fa-heart"></i>
                    </div>

                    <div class="hero-content">
                        <div class="campaign-category">
                            <i class="fas fa-tag"></i>
                            <span><?php echo htmlspecialchars($campaign['category'] ?? 'General'); ?></span>
                        </div>
                        <h1 class="campaign-title"><?php echo htmlspecialchars($campaign['name']); ?></h1>
                        <div class="campaign-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <?php echo date('M d', strtotime($campaign['timing_start'])); ?> - <?php echo date('M d, Y', strtotime($campaign['timing_end'])); ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($campaign['target_location']); ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-users"></i>
                                <?php echo $campaign['influencers_needed']; ?> Influencers Needed
                            </div>
                        </div>
                    </div>
                </div>

                <div class="campaign-container">
                    <!-- Left Column: Main Content -->
                    <div class="campaign-main-content">
                        <!-- Campaign Header Info -->
                        <div class="campaign-header-info">
                            <span class="campaign-category">
                                <i class="fas fa-tag"></i>
                                <span><?php echo htmlspecialchars($campaign['category'] ?? 'General'); ?></span>
                            </span>
                            <h1><?php echo htmlspecialchars($campaign['name']); ?></h1>
                            <div class="campaign-meta-inline">
                                <div class="meta-inline-item">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M d', strtotime($campaign['timing_start'])); ?> - <?php echo date('M d, Y', strtotime($campaign['timing_end'])); ?>
                                </div>
                                <div class="meta-inline-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($campaign['target_location']); ?>
                                </div>
                                <div class="meta-inline-item">
                                    <i class="fas fa-users"></i>
                                    <?php echo $campaign['influencers_needed']; ?> Influencers Needed
                                </div>
                            </div>
                        </div>

                        <!-- Gallery -->
                        <?php if (!empty($galleryImages)): ?>
                        <div class="gallery-section">
                            <div class="gallery-grid">
                                <?php
                                $totalImages = count($galleryImages);
                                $displayLimit = 3;
                                $displayImages = array_slice($galleryImages, 0, $displayLimit);

                                foreach ($displayImages as $index => $image):
                                    $imageSrc = trim($image);
                                    if (empty($imageSrc)) continue;
                                    $isLastItem = ($index === $displayLimit - 1 && $totalImages > $displayLimit);
                                    $remainingCount = $totalImages - $displayLimit;
                                ?>
                                    <div class="gallery-item" onclick="openLightbox(<?php echo $index; ?>)">
                                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Campaign Image <?php echo $index + 1; ?>">
                                        <?php if ($isLastItem): ?>
                                            <div class="gallery-item-overlay">
                                                <div class="gallery-item-overlay-number">+<?php echo $remainingCount; ?></div>
                                                <div class="gallery-item-overlay-text">View More</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="text-align: center; margin-top: 1rem; font-size: 0.8125rem; color: #8e8e8e;">
                                <?php echo $totalImages; ?> <?php echo $totalImages === 1 ? 'image' : 'images'; ?> • Click to view fullscreen
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Campaign Description -->
                        <div class="content-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <h3>About This Campaign</h3>
                            </div>
                            <div class="section-content">
                                <?php echo nl2br(htmlspecialchars($campaign['description'])); ?>
                            </div>
                        </div>

                        <!-- What is Expected -->
                        <?php if ($campaign['what_is_expected']): ?>
                        <div class="content-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <h3>What We Expect</h3>
                            </div>
                            <div class="section-content">
                                <?php echo nl2br(htmlspecialchars($campaign['what_is_expected'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Instructions -->
                        <?php if ($campaign['instructions']): ?>
                        <div class="content-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <h3>Instructions</h3>
                            </div>
                            <div class="section-content">
                                <?php
                                $instructions = explode("\n", $campaign['instructions']);
                                echo '<ol>';
                                foreach ($instructions as $instruction) {
                                    $instruction = trim($instruction);
                                    $instruction = preg_replace('/^\d+\.\s*/', '', $instruction);
                                    if (!empty($instruction)) {
                                        echo '<li>' . htmlspecialchars($instruction) . '</li>';
                                    }
                                }
                                echo '</ol>';
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right Column: Sidebar -->
                    <div class="campaign-sidebar">
                        <!-- Countdown Timer -->
                        <div class="countdown-timer" id="countdown-timer">
                            <div class="countdown-label">Campaign Ends In</div>
                            <div class="countdown-display">
                                <div class="countdown-item">
                                    <span class="countdown-value" id="days">--</span>
                                    <span class="countdown-unit">Days</span>
                                </div>
                                <span class="countdown-separator">:</span>
                                <div class="countdown-item">
                                    <span class="countdown-value" id="hours">--</span>
                                    <span class="countdown-unit">Hrs</span>
                                </div>
                                <span class="countdown-separator">:</span>
                                <div class="countdown-item">
                                    <span class="countdown-value" id="minutes">--</span>
                                    <span class="countdown-unit">Min</span>
                                </div>
                                <span class="countdown-separator">:</span>
                                <div class="countdown-item">
                                    <span class="countdown-value" id="seconds">--</span>
                                    <span class="countdown-unit">Sec</span>
                                </div>
                            </div>
                        </div>

                        <!-- Apply Button -->
                        <?php if ($application):
                            $finlandTz = new DateTimeZone('Europe/Helsinki');
                            $appliedDate = new DateTime($application['applied_at']);
                            $appliedDate->setTimezone($finlandTz);
                        ?>
                            <div class="applied-badge">
                                <i class="fas fa-check-circle"></i>
                                <div class="applied-badge-content">
                                    <span>Application <?php echo ucfirst($application['status']); ?></span>
                                    <span class="applied-badge-time"><?php echo $appliedDate->format('M d, Y \a\t H:i'); ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <button class="participate-sidebar-btn" onclick="applyToCampaign()">
                                <i class="fas fa-paper-plane"></i>
                                Apply Now
                            </button>
                        <?php endif; ?>

                        <!-- Compensation -->
                        <?php if ($campaign['what_is_offered'] || $campaign['compensation']): ?>
                        <div class="content-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="fas fa-gift"></i>
                                </div>
                                <h3>What We Offer</h3>
                            </div>
                            <div class="section-content">
                                <?php if ($campaign['what_is_offered']): ?>
                                    <?php echo nl2br(htmlspecialchars($campaign['what_is_offered'])); ?>
                                <?php endif; ?>
                                <?php if ($campaign['compensation']): ?>
                                    <div class="compensation-box">
                                        <div class="compensation-amount">
                                            <?php echo htmlspecialchars($campaign['compensation']); ?>
                                        </div>
                                        <div class="compensation-label">
                                            <i class="fas fa-gift"></i> Total Package
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Brand Information -->
                        <div class="brand-info-card">
                            <div class="brand-header">
                                <?php if ($campaign['profile_picture']): ?>
                                    <img src="../<?php echo htmlspecialchars($campaign['profile_picture']); ?>" alt="Brand Logo" class="brand-logo">
                                <?php else: ?>
                                    <div class="brand-logo-placeholder">
                                        <?php echo strtoupper(substr($campaign['company_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="brand-details">
                                    <h2><?php echo htmlspecialchars($campaign['company_name']); ?></h2>
                                    <div class="brand-socials">
                                        <?php if (!empty($campaign['website_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($campaign['website_url']); ?>" target="_blank" class="social-link website">
                                                <i class="fas fa-globe"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="social-link website no-link" title="Website not available">
                                                <i class="fas fa-globe"></i>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($campaign['instagram_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($campaign['instagram_url']); ?>" target="_blank" class="social-link instagram">
                                                <i class="fab fa-instagram"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($campaign['tiktok_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($campaign['tiktok_url']); ?>" target="_blank" class="social-link tiktok">
                                                <i class="fab fa-tiktok"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Details -->
                        <div class="contact-details-card">
                            <div class="contact-details-header">
                                <i class="fas fa-address-card"></i>
                                <h3>Contact Information</h3>
                            </div>
                            <?php if ($campaign['brand_address']): ?>
                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-item-content">
                                    <div class="contact-item-label">Address</div>
                                    <div class="contact-item-value"><?php echo htmlspecialchars($campaign['brand_address']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($campaign['brand_timing']): ?>
                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="contact-item-content">
                                    <div class="contact-item-label">Business Hours</div>
                                    <div class="contact-item-value"><?php echo htmlspecialchars($campaign['brand_timing']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php
                            // Get brand contact info
                            $stmt = $pdo->prepare("SELECT email, phone FROM users WHERE id = (SELECT user_id FROM brand_profiles WHERE id = ?)");
                            $stmt->execute([$campaign['brand_id']]);
                            $brandContact = $stmt->fetch();
                            ?>
                            <?php if ($brandContact && $brandContact['email']): ?>
                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-item-content">
                                    <div class="contact-item-label">Email</div>
                                    <div class="contact-item-value">
                                        <a href="mailto:<?php echo htmlspecialchars($brandContact['email']); ?>">
                                            <?php echo htmlspecialchars($brandContact['email']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($brandContact && $brandContact['phone']): ?>
                            <div class="contact-item">
                                <div class="contact-item-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="contact-item-content">
                                    <div class="contact-item-label">Phone</div>
                                    <div class="contact-item-value">
                                        <a href="tel:<?php echo htmlspecialchars($brandContact['phone']); ?>">
                                            <?php echo htmlspecialchars($brandContact['phone']); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- End Sidebar -->
                </div>
                <!-- End Campaign Container -->
            </div>
        </main>
    </div>

    <!-- Lightbox Modal -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
        <div class="lightbox-content">
            <div class="lightbox-close" onclick="closeLightbox(event)">
                <i class="fas fa-times"></i>
            </div>
            <div class="lightbox-prev" onclick="navigateLightbox(-1, event)">
                <i class="fas fa-chevron-left"></i>
            </div>
            <img src="" alt="Campaign Image" class="lightbox-image" id="lightbox-image">
            <div class="lightbox-next" onclick="navigateLightbox(1, event)">
                <i class="fas fa-chevron-right"></i>
            </div>
        </div>
    </div>

    <!-- Success/Error Notification Modal -->
    <div class="notification-modal" id="notificationModal">
        <div class="notification-modal-content">
            <div class="notification-icon" id="notificationIcon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="notification-title" id="notificationTitle">Success!</h3>
            <p class="notification-message" id="notificationMessage">Your application has been submitted.</p>
            <button class="notification-btn" id="notificationBtn" onclick="closeNotification()">Got it</button>
        </div>
    </div>

    <!-- Application Modal -->
    <div class="apply-modal" id="applyModal">
        <div class="apply-modal-content">
            <div class="apply-modal-header">
                <h2>Apply to Campaign</h2>
                <button class="apply-modal-close" onclick="closeApplyModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="applicationForm" onsubmit="submitApplication(event)">
                <div class="apply-modal-body">
                    <div class="apply-campaign-info">
                        <h3><?php echo htmlspecialchars($campaign['name']); ?></h3>
                        <p>Tell the brand why you're the perfect fit for this campaign</p>
                    </div>

                    <div class="form-group">
                        <label for="applicationDescription">
                            Application Message <span style="color: #e879f9;">*</span>
                        </label>
                        <textarea
                            id="applicationDescription"
                            name="description"
                            placeholder="Describe your experience, audience demographics, previous collaborations, and why you'd be great for this campaign..."
                            required
                            minlength="50"
                        ></textarea>
                        <small>Minimum 50 characters. Be specific and professional.</small>
                    </div>

                    <input type="hidden" id="campaignId" name="campaign_id" value="<?php echo $campaign_id; ?>">
                </div>
                <div class="apply-modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeApplyModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
    <script>
        let shouldReloadOnClose = false;

        // Notification functions
        function showNotification(type, title, message) {
            const modal = document.getElementById('notificationModal');
            const icon = document.getElementById('notificationIcon');
            const titleEl = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');
            const btn = document.getElementById('notificationBtn');

            // Set icon based on type
            icon.className = 'notification-icon ' + type;
            if (type === 'success') {
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                btn.className = 'notification-btn success';
            } else if (type === 'error') {
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                btn.className = 'notification-btn';
            } else if (type === 'warning') {
                icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                btn.className = 'notification-btn';
            }

            titleEl.textContent = title;
            messageEl.textContent = message;

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeNotification() {
            document.getElementById('notificationModal').classList.remove('active');
            document.body.style.overflow = '';
            if (shouldReloadOnClose) {
                shouldReloadOnClose = false;
                location.reload();
            }
        }

        function applyToCampaign() {
            document.getElementById('applyModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeApplyModal() {
            document.getElementById('applyModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        document.getElementById('applyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeApplyModal();
            }
        });

        document.getElementById('notificationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNotification();
            }
        });

        // Submit Application
        function submitApplication(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const campaignId = document.getElementById('campaignId').value;
            const description = document.getElementById('applicationDescription').value;

            // Validate
            if (description.length < 50) {
                showNotification('warning', 'Too Short', 'Please provide at least 50 characters in your application message.');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            // Submit via AJAX
            fetch('api/apply-campaign.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    campaign_id: campaignId,
                    description: description
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Success - close modal and show notification
                    closeApplyModal();
                    shouldReloadOnClose = true;
                    showNotification('success', 'Application Submitted!', 'Your application has been sent to the brand. They will review it and get back to you soon.');
                } else {
                    showNotification('error', 'Submission Failed', data.message || 'Failed to submit application. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error submitting application:', error);
                showNotification('error', 'Error', 'An error occurred while submitting your application. Please try again.');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeApplyModal();
            }
        });

        // Countdown Timer
        function initCountdown() {
            const endDate = new Date('<?php echo date('Y-m-d H:i:s', strtotime($campaign['timing_end'] . ' 23:59:59')); ?>').getTime();

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = endDate - now;

                if (distance < 0) {
                    document.getElementById('countdown-timer').innerHTML = '<div class="countdown-label" style="margin:0;">Campaign Ended</div>';
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('days').textContent = String(days).padStart(2, '0');
                document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
        }

        // Initialize on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCountdown);
        } else {
            initCountdown();
        }

        // Gallery Lightbox
        const galleryImages = <?php echo json_encode(array_map('trim', $galleryImages ?? [])); ?>;
        let currentImageIndex = 0;

        function openLightbox(index) {
            currentImageIndex = index;
            const lightbox = document.getElementById('lightbox');
            const lightboxImage = document.getElementById('lightbox-image');

            lightboxImage.src = galleryImages[currentImageIndex];
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(event) {
            if (event.target.id === 'lightbox' || event.target.closest('.lightbox-close')) {
                const lightbox = document.getElementById('lightbox');
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function navigateLightbox(direction, event) {
            event.stopPropagation();
            currentImageIndex += direction;

            if (currentImageIndex < 0) {
                currentImageIndex = galleryImages.length - 1;
            } else if (currentImageIndex >= galleryImages.length) {
                currentImageIndex = 0;
            }

            document.getElementById('lightbox-image').src = galleryImages[currentImageIndex];
        }

        // Keyboard navigation for lightbox
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('lightbox');
            if (lightbox.classList.contains('active')) {
                if (e.key === 'Escape') {
                    lightbox.classList.remove('active');
                    document.body.style.overflow = '';
                } else if (e.key === 'ArrowLeft') {
                    navigateLightbox(-1, e);
                } else if (e.key === 'ArrowRight') {
                    navigateLightbox(1, e);
                }
            }
        });

        // Favorite functionality
        function toggleFavorite(campaignId) {
            const btn = document.querySelector('.favorite-btn');
            const icon = btn.querySelector('i');

            // Toggle active state
            btn.classList.toggle('active');

            // Toggle icon between regular and solid heart
            if (btn.classList.contains('active')) {
                icon.classList.remove('far');
                icon.classList.add('fas');

                // Save to localStorage
                let favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');
                if (!favorites.includes(campaignId)) {
                    favorites.push(campaignId);
                    localStorage.setItem('favoriteCampaigns', JSON.stringify(favorites));
                }
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');

                // Remove from localStorage
                let favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');
                favorites = favorites.filter(id => id !== campaignId);
                localStorage.setItem('favoriteCampaigns', JSON.stringify(favorites));
            }
        }

        // Check if campaign is already favorited on page load
        (function() {
            const campaignId = <?php echo $campaign_id; ?>;
            const favorites = JSON.parse(localStorage.getItem('favoriteCampaigns') || '[]');

            if (favorites.includes(campaignId)) {
                const btn = document.querySelector('.favorite-btn');
                const icon = btn.querySelector('i');
                btn.classList.add('active');
                icon.classList.remove('far');
                icon.classList.add('fas');
            }
        })();
    </script>
</body>
</html>
