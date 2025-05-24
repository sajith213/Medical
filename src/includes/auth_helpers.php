<?php
// secure_session_start(); // Ensure session started if not already

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role_name'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Redirect to login page (use BASE_URL from config)
        // header('Location: ' . BASE_URL . '/login.php'); // Actual page will be part of frontend step
        // exit;
        // For now, can die or echo message
        die("Access Denied. Please login.");
    }
}

function hasRole($roleName) {
    if (!isLoggedIn()) return false;
    // Assumes role_name is stored in session during login
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $roleName;
}

function hasAnyRole(array $roles) {
    if (!isLoggedIn()) return false;
    return isset($_SESSION['role_name']) && in_array($_SESSION['role_name'], $roles);
}

function requireRole($roleName) {
    requireLogin();
    if (!hasRole($roleName)) {
        // Redirect to an unauthorized page or show error
        // header('Location: ' . BASE_URL . '/unauthorized.php');
        // exit;
        die("Access Denied. You do not have the required role: " . htmlspecialchars($roleName));
    }
}

function requireAnyRole(array $roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        die("Access Denied. You do not have any of the required roles: " . htmlspecialchars(implode(', ', $roles)));
    }
}
?>
