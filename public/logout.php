<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/session_management.php';

secure_session_start();

$_SESSION = array(); 

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Optional: Add a message before redirecting
// secure_session_start(); // Start a new session for the message
// $_SESSION['info_message'] = "You have been logged out successfully.";

header("Location: " . BASE_URL . "/login.php");
exit;
?>
