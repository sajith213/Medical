<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/session_management.php';

secure_session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
} else {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
?>
