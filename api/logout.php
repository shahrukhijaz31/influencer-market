<?php
/**
 * Casters.fi - Logout API
 */

require_once '../includes/config.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: " . SITE_URL . "/login.html");
exit;
?>
