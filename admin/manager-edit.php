<?php
/**
 * Casters.fi - Admin: Edit Manager
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

        // Update user
        $stmt = $pdo->prepare("
            UPDATE users SET
                first_name = ?,
                last_name = ?,
                email = ?,
                user_type = ?,
                is_active = ?,
                updated_at = NOW()
            WHERE id = ? AND user_type IN ('admin', 'manager')
        ");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['user_type'],
            isset($_POST['is_active']) ? 1 : 0,
            $id
        ]);

        // Update password if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$password, $id]);
        }

        $success = "Manager updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating manager: " . $e->getMessage();
    }
}

// Get manager data
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM users
        WHERE id = ? AND user_type IN ('admin', 'manager')
    ");
    $stmt->execute([$id]);
    $manager = $stmt->fetch();

    if (!$manager) {
        redirect('admin/managers.php');
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
    <title>Edit Manager - Admin - Casters.fi</title>
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
                    <h1 class="topbar-title">Edit Manager</h1>
                </div>
                <div class="topbar-right">
                    <a href="managers.php" class="btn btn-outline">
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

                <div class="dashboard-card">
                    <div class="card-header">
                        <h3 class="card-title">Edit Manager</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="dashboard-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">First Name *</label>
                                    <input type="text" class="form-input" name="first_name" value="<?php echo htmlspecialchars($manager['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Last Name *</label>
                                    <input type="text" class="form-input" name="last_name" value="<?php echo htmlspecialchars($manager['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($manager['email'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" class="form-input" name="password" minlength="8">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Role *</label>
                                <select class="form-select" name="user_type" required>
                                    <option value="manager" <?php echo ($manager['user_type'] ?? '') === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                    <option value="admin" <?php echo ($manager['user_type'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-check">
                                    <input type="checkbox" name="is_active" <?php echo ($manager['is_active'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Account Active</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include '../includes/dashboard-scripts.php'; ?>
</body>
</html>
