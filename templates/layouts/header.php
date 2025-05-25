<?php
if (session_status() == PHP_SESSION_NONE) {
    // session_start(); // Session should be started by the calling script or a global include
    // For this task, assume session is started by each main PHP file (e.g. register.php, login.php)
    // or via a central bootstrap file in future.
    // For now, we will call `secure_session_start()` from `session_management.php` in each main public file.
}
require_once dirname(dirname(__DIR__)) . '/config/config.php';
// Autoload or include auth_helpers if needed for conditional display in header
// require_once APP_ROOT . '/src/includes/auth_helpers.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><a href="<?php echo BASE_URL; ?>/index.php"><?php echo SITE_NAME; ?></a></h1>
            </div>
            <nav>
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard.php">Dashboard</a></li>
                        
                        <?php if (hasRole('Employee')): ?>
                            <li><a href="<?php echo BASE_URL; ?>/my_claims.php">My Claims</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/submit_claim.php">Submit Claim</a></li>
                        <?php endif; ?>

                        <?php if (hasAnyRole(['Admin', 'HR Staff'])): ?>
                            <li><a href="<?php echo BASE_URL; ?>/hr_claims.php">Manage Claims</a></li>
                        <?php endif; ?>
                        
                        <li><a href="<?php echo BASE_URL; ?>/profile.php">Profile</a></li>
                        
                        <?php if (hasRole('Admin')): ?>
                            <li><a href="<?php echo BASE_URL; ?>/admin_users.php">Manage Users</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/admin_config.php">System Configuration</a></li>
                        <?php endif; ?>
                        
                        <li><a href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>/login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container main-content">
        <?php
        // Display session messages (flash messages)
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['info_message'])) {
            echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info_message']) . '</div>';
            unset($_SESSION['info_message']);
        }
        // Need to ensure auth_helpers are loaded for hasRole/hasAnyRole
        if (function_exists('getCurrentUserRole') === false) { // Check if already loaded
             require_once APP_ROOT . '/src/includes/auth_helpers.php';
        }
        ?>
