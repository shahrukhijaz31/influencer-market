<?php
/**
 * Casters.fi - Admin: Managers List & Add
 */

require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.html');
}

$showAddForm = isset($_GET['action']) && $_GET['action'] === 'add';

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Don't allow deleting yourself
    if ($_GET['delete'] == $_SESSION['user_id']) {
        $error = "You cannot delete your own account.";
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type IN ('admin', 'manager')");
            $stmt->execute([$_GET['delete']]);
            $success = "Manager deleted successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting manager: " . $e->getMessage();
        }
    }
}

// Handle add form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Email already exists.");
        }

        // Create manager
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, user_type, first_name, last_name, is_active, email_verified)
            VALUES (?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([
            $_POST['email'],
            $password,
            $_POST['user_type'],
            $_POST['first_name'],
            $_POST['last_name']
        ]);

        $success = "Manager created successfully.";
        $showAddForm = false;
    } catch (Exception $e) {
        $error = "Error creating manager: " . $e->getMessage();
    }
}

// Get all managers
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT id, email, user_type, first_name, last_name, is_active, created_at
        FROM users
        WHERE user_type IN ('admin', 'manager')
        ORDER BY created_at DESC
    ");
    $managers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Managers - Admin - Casters.fi</title>
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
                <a href="brands.php" class="sidebar-link">
                    <i class="fas fa-building"></i>
                    <span>Brands</span>
                </a>
                <a href="managers.php" class="sidebar-link active">
                    <i class="fas fa-user-tie"></i>
                    <span>Managers</span>
                </a>

                <div class="sidebar-section">
                    <p class="sidebar-section-title">Content</p>
                </div>
                <a href="campaigns.php" class="sidebar-link">
                    <i class="fas fa-bullhorn"></i>
                    <span>Campaigns</span>
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
                    <h1 class="topbar-title">Managers</h1>
                </div>
                <div class="topbar-right">
                    <?php if ($showAddForm): ?>
                    <a href="managers.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <?php else: ?>
                    <a href="managers.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Manager
                    </a>
                    <?php endif; ?>
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

                <?php if ($showAddForm): ?>
                <!-- Add Manager Form -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Add New Manager</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="dashboard-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-input" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-input" name="last_name" required>
                                </div>
                            </div>
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
                            <div class="form-group">
                                <label class="form-label">Role *</label>
                                <select class="form-select" name="user_type" required>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus"></i> Create Manager
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <!-- Managers List -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">All Managers (<?php echo count($managers); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($managers as $manager): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="sidebar-user-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                            <?php if ($manager['id'] == $_SESSION['user_id']): ?>
                                            <span class="category-tag">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($manager['email']); ?></td>
                                    <td>
                                        <span class="category-tag" style="<?php echo $manager['user_type'] === 'admin' ? 'background: var(--primary-gradient); color: white;' : ''; ?>">
                                            <?php echo $manager['user_type'] === 'admin' ? 'Admin' : 'Manager'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($manager['created_at'])); ?></td>
                                    <td>
                                        <?php if ($manager['is_active']): ?>
                                        <span class="campaign-status status-active">Active</span>
                                        <?php else: ?>
                                        <span class="campaign-status status-completed">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="manager-edit.php?id=<?php echo $manager['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 12px;">Edit</a>
                                        <?php if ($manager['id'] != $_SESSION['user_id']): ?>
                                        <a href="managers.php?delete=<?php echo $manager['id']; ?>" class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 12px; color: #ef4444;" onclick="return confirm('Are you sure you want to delete this manager?')">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

<?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
