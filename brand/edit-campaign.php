<?php
/**
 * Casters.fi - Edit Campaign Page
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isBrand()) {
    redirect('login.html');
}

$campaignId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$campaignId) {
    redirect('campaigns.php');
}

$profile = null;
$campaign = null;

try {
    $pdo = getDBConnection();

    // Get brand profile
    $stmt = $pdo->prepare("SELECT * FROM brand_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $brandProfile = $stmt->fetch();
    $profile = $brandProfile;

    // Get campaign
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND brand_id = ?");
    $stmt->execute([$campaignId, $brandProfile['id']]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        $_SESSION['error'] = 'Campaign not found';
        redirect('campaigns.php');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $budget = floatval($_POST['budget'] ?? 0);
        $timing_start = $_POST['timing_start'] ?? '';
        $timing_end = $_POST['timing_end'] ?? '';
        $influencers_needed = intval($_POST['influencers_needed'] ?? 1);
        $what_is_expected = trim($_POST['what_is_expected'] ?? '');
        $what_is_offered = trim($_POST['what_is_offered'] ?? '');
        $target_sex = $_POST['target_sex'] ?? 'any';
        $target_age_min = intval($_POST['target_age_min'] ?? 18);
        $target_age_max = intval($_POST['target_age_max'] ?? 65);
        $target_location = trim($_POST['target_location'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $is_public = isset($_POST['is_public']) ? 1 : 0;

        // Validate
        if (empty($name)) {
            $error = 'Campaign name is required';
        } else {
            // Update campaign
            $stmt = $pdo->prepare("
                UPDATE campaigns SET
                    name = ?,
                    description = ?,
                    category = ?,
                    budget = ?,
                    timing_start = ?,
                    timing_end = ?,
                    influencers_needed = ?,
                    what_is_expected = ?,
                    what_is_offered = ?,
                    target_sex = ?,
                    target_age_min = ?,
                    target_age_max = ?,
                    target_location = ?,
                    status = ?,
                    is_public = ?
                WHERE id = ? AND brand_id = ?
            ");
            $stmt->execute([
                $name, $description, $category, $budget,
                $timing_start, $timing_end, $influencers_needed,
                $what_is_expected, $what_is_offered,
                $target_sex, $target_age_min, $target_age_max, $target_location,
                $status, $is_public,
                $campaignId, $brandProfile['id']
            ]);

            $_SESSION['success'] = 'Campaign updated successfully';
            redirect('campaigns.php');
        }
    }

} catch (PDOException $e) {
    $error = $e->getMessage();
}

$categories = ['Fashion', 'Beauty', 'Lifestyle', 'Food', 'Travel', 'Fitness', 'Technology', 'Gaming', 'Music', 'Art', 'Business', 'Education', 'Entertainment', 'Health', 'Sports', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign - Casters.fi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/dashboard-pro.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-form-container { max-width: 100%; }

        .form-layout {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
        }

        .form-layout .form-card.full-width { grid-column: 1 / -1; }
        .form-layout .form-card.span-2 { grid-column: span 2; }
        .form-layout .form-card.span-3 { grid-column: span 3; }

        .page-header { margin-bottom: 0.75rem; }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            color: #666;
            text-decoration: none;
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .back-link:hover { color: #e879f9; }

        .campaign-header {
            background: white;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        .campaign-header-image {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .campaign-header-image img { width: 100%; height: 100%; object-fit: cover; }
        .campaign-header-image i { font-size: 1rem; color: white; }

        .campaign-header-info { flex: 1; }
        .campaign-header-info h1 { font-size: 0.9375rem; font-weight: 700; color: #111; margin: 0; }

        .campaign-header-meta { display: flex; gap: 1rem; }
        .campaign-header-meta span { display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: #666; }
        .campaign-header-meta i { color: #e879f9; font-size: 0.75rem; }

        .campaign-status-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.6875rem; font-weight: 600; }
        .campaign-status-badge.active { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .campaign-status-badge.draft { background: rgba(107, 114, 128, 0.1); color: #4b5563; }
        .campaign-status-badge.paused { background: rgba(245, 158, 11, 0.1); color: #d97706; }
        .campaign-status-badge.completed { background: rgba(59, 130, 246, 0.1); color: #2563eb; }

        .header-actions { display: flex; gap: 0.5rem; margin-left: auto; }

        .form-card {
            background: white;
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            height: fit-content;
        }

        .form-card-title {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #111;
            margin: 0 0 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .form-card-title-icon {
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6875rem;
        }
        .form-card-title-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .form-card-title-icon.pink { background: rgba(232, 121, 249, 0.1); color: #e879f9; }
        .form-card-title-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .form-card-title-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .form-card-title-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
        .form-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
        .form-group { margin-bottom: 0; }
        .form-group.full-width { grid-column: 1 / -1; }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 0.75rem;
        }
        .form-group label span { color: #e879f9; }

        .form-control {
            width: 100%;
            padding: 0.4rem 0.5rem;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            font-size: 0.8125rem;
            font-family: inherit;
            background: #fafafa;
        }
        .form-control:focus {
            outline: none;
            border-color: #e879f9;
            background: white;
            box-shadow: 0 0 0 2px rgba(232, 121, 249, 0.1);
        }

        textarea.form-control { min-height: 50px; resize: vertical; }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23999'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 0.75rem;
            padding-right: 1.5rem;
        }

        .form-hint { font-size: 0.6875rem; color: #999; margin-top: 0.25rem; }

        .status-options { display: flex; gap: 0.5rem; }
        .status-option { flex: 1; position: relative; }
        .status-option input { display: none; }
        .status-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            padding: 0.4rem;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.6875rem;
            background: #fafafa;
        }
        .status-option label i { font-size: 0.75rem; opacity: 0.5; }
        .status-option input:checked + label { border-color: #e879f9; background: white; }
        .status-option input:checked + label i { opacity: 1; }
        .status-option input:checked + label.status-active { border-color: #10b981; color: #059669; }
        .status-option input:checked + label.status-active i { color: #10b981; }
        .status-option input:checked + label.status-draft { border-color: #6b7280; color: #4b5563; }
        .status-option input:checked + label.status-draft i { color: #6b7280; }
        .status-option input:checked + label.status-paused { border-color: #f59e0b; color: #d97706; }
        .status-option input:checked + label.status-paused i { color: #f59e0b; }
        .status-option input:checked + label.status-completed { border-color: #3b82f6; color: #2563eb; }
        .status-option input:checked + label.status-completed i { color: #3b82f6; }

        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            background: #fafafa;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
        .toggle-group-label { display: flex; align-items: center; gap: 0.5rem; }
        .toggle-group-label i { color: #e879f9; font-size: 0.875rem; }
        .toggle-group-label span { font-weight: 600; color: #333; font-size: 0.75rem; }
        .toggle-group-label small { display: block; font-weight: 400; color: #666; font-size: 0.6875rem; }

        .toggle-switch { position: relative; width: 36px; height: 20px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #e5e5e5;
            transition: .3s;
            border-radius: 20px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        .toggle-switch input:checked + .toggle-slider { background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%); }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(16px); }

        .form-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.5rem;
            border-top: 1px solid #f0f0f0;
            margin-top: 0.5rem;
        }
        .form-actions-right { display: flex; gap: 0.5rem; }

        .btn {
            padding: 0.4rem 0.75rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            text-decoration: none;
        }
        .btn-primary { background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%); color: white; }
        .btn-primary:hover { box-shadow: 0 2px 8px rgba(232, 121, 249, 0.4); }
        .btn-secondary { background: #f5f5f5; color: #666; }
        .btn-secondary:hover { background: #e5e5e5; }

        .alert { padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; }
        .alert i { font-size: 0.875rem; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        .inline-fields { display: flex; gap: 0.5rem; }
        .inline-fields .form-group { flex: 1; }

        @media (max-width: 1200px) {
            .form-layout { grid-template-columns: repeat(2, 1fr); }
            .form-layout .form-card.span-2, .form-layout .form-card.span-3 { grid-column: span 1; }
        }
        @media (max-width: 768px) {
            .form-layout { grid-template-columns: 1fr; }
            .form-grid, .form-grid-4 { grid-template-columns: 1fr; }
            .status-options { flex-wrap: wrap; }
            .status-option { flex: 1 1 45%; }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include '../includes/brand-sidebar.php'; ?>

        <main class="dashboard-main">
            <?php $pageTitle = 'Edit Campaign'; include '../includes/brand-topbar.php'; ?>

            <div class="dashboard-content">
                <div class="edit-form-container">
                    <div class="page-header">
                        <a href="campaigns.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Campaigns
                        </a>

                        <div class="campaign-header">
                            <div class="campaign-header-image">
                                <?php if (!empty($campaign['image'])): ?>
                                <img src="<?php echo htmlspecialchars($campaign['image']); ?>" alt="">
                                <?php else: ?>
                                <i class="fas fa-bullhorn"></i>
                                <?php endif; ?>
                            </div>
                            <div class="campaign-header-info">
                                <h1><?php echo htmlspecialchars($campaign['name']); ?></h1>
                                <div class="campaign-header-meta">
                                    <span><i class="fas fa-euro-sign"></i> <?php echo number_format($campaign['budget']); ?> Budget</span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($campaign['created_at'])); ?></span>
                                    <span class="campaign-status-badge <?php echo $campaign['status']; ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="header-actions">
                                <a href="campaign-applications.php?id=<?php echo $campaignId; ?>" class="btn btn-secondary"><i class="fas fa-users"></i> Applications</a>
                                <a href="campaigns.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" form="campaign-form" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="" id="campaign-form">
                        <div class="form-layout">
                            <!-- Basic Info -->
                            <div class="form-card span-2" id="basic">
                                <h3 class="form-card-title">
                                    <span class="form-card-title-icon blue"><i class="fas fa-info-circle"></i></span>
                                    Basic Information
                                </h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Campaign Name <span>*</span></label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($campaign['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="category" class="form-control">
                                            <option value="">Select</option>
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat; ?>" <?php echo $campaign['category'] === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Budget (EUR)</label>
                                        <input type="number" name="budget" class="form-control" value="<?php echo $campaign['budget']; ?>" min="0" step="0.01">
                                    </div>
                                    <div class="form-group">
                                        <label>Influencers Needed</label>
                                        <input type="number" name="influencers_needed" class="form-control" value="<?php echo $campaign['influencers_needed']; ?>" min="1">
                                    </div>
                                    <div class="form-group full-width">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($campaign['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Timing & Target -->
                            <div class="form-card span-2" id="timing">
                                <h3 class="form-card-title">
                                    <span class="form-card-title-icon orange"><i class="fas fa-calendar-alt"></i></span>
                                    Timing & Target
                                </h3>
                                <div class="form-grid-4">
                                    <div class="form-group">
                                        <label>Start Date</label>
                                        <input type="date" name="timing_start" class="form-control" value="<?php echo $campaign['timing_start']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>End Date</label>
                                        <input type="date" name="timing_end" class="form-control" value="<?php echo $campaign['timing_end']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="target_sex" class="form-control">
                                            <option value="any" <?php echo $campaign['target_sex'] === 'any' ? 'selected' : ''; ?>>Any</option>
                                            <option value="male" <?php echo $campaign['target_sex'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $campaign['target_sex'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Location</label>
                                        <input type="text" name="target_location" class="form-control" value="<?php echo htmlspecialchars($campaign['target_location']); ?>" placeholder="Finland">
                                    </div>
                                    <div class="form-group">
                                        <label>Min Age</label>
                                        <input type="number" name="target_age_min" class="form-control" value="<?php echo $campaign['target_age_min'] ?? 18; ?>" min="13" max="100">
                                    </div>
                                    <div class="form-group">
                                        <label>Max Age</label>
                                        <input type="number" name="target_age_max" class="form-control" value="<?php echo $campaign['target_age_max'] ?? 65; ?>" min="13" max="100">
                                    </div>
                                </div>
                            </div>

                            <!-- Requirements -->
                            <div class="form-card span-2" id="requirements">
                                <h3 class="form-card-title">
                                    <span class="form-card-title-icon green"><i class="fas fa-tasks"></i></span>
                                    Requirements
                                </h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>What is Expected</label>
                                        <textarea name="what_is_expected" class="form-control" rows="3"><?php echo htmlspecialchars($campaign['what_is_expected']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>What We Offer</label>
                                        <textarea name="what_is_offered" class="form-control" rows="3"><?php echo htmlspecialchars($campaign['what_is_offered']); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Status & Visibility -->
                            <div class="form-card span-2" id="status">
                                <h3 class="form-card-title">
                                    <span class="form-card-title-icon pink"><i class="fas fa-cog"></i></span>
                                    Status & Visibility
                                </h3>
                                <div class="status-options">
                                    <div class="status-option">
                                        <input type="radio" name="status" id="status-draft" value="draft" <?php echo $campaign['status'] === 'draft' ? 'checked' : ''; ?>>
                                        <label for="status-draft" class="status-draft"><i class="fas fa-file-alt"></i> Draft</label>
                                    </div>
                                    <div class="status-option">
                                        <input type="radio" name="status" id="status-active" value="active" <?php echo $campaign['status'] === 'active' ? 'checked' : ''; ?>>
                                        <label for="status-active" class="status-active"><i class="fas fa-play-circle"></i> Active</label>
                                    </div>
                                    <div class="status-option">
                                        <input type="radio" name="status" id="status-paused" value="paused" <?php echo $campaign['status'] === 'paused' ? 'checked' : ''; ?>>
                                        <label for="status-paused" class="status-paused"><i class="fas fa-pause-circle"></i> Paused</label>
                                    </div>
                                    <div class="status-option">
                                        <input type="radio" name="status" id="status-completed" value="completed" <?php echo $campaign['status'] === 'completed' ? 'checked' : ''; ?>>
                                        <label for="status-completed" class="status-completed"><i class="fas fa-check-circle"></i> Done</label>
                                    </div>
                                </div>
                                <div class="toggle-group">
                                    <div class="toggle-group-label">
                                        <i class="fas fa-globe"></i>
                                        <div><span>Public Campaign</span><small>Visible to all influencers</small></div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="is_public" <?php echo $campaign['is_public'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
