<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php'; // For requireLogin()

secure_session_start();
requireLogin(); // Redirects if not logged in

$page_title = "Dashboard";
include APP_ROOT . '/templates/layouts/header.php';
?>

<h2>Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</p>
<p>Your Role: <?php echo htmlspecialchars($_SESSION['role_name']); ?></p>
<p>This is your main dashboard. More features will be added here.</p>

<!-- Links to other sections based on role could go here -->

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
