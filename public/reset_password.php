<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/User.php';
require_once APP_ROOT . '/src/includes/session_management.php';

secure_session_start();
$userModel = new User($pdo);

$token = $_GET['token'] ?? null;
// $error_message = ''; // Using session flash messages for redirect
// $success_message = ''; // Using session flash messages for redirect

if (!$token) {
    $_SESSION['error_message'] = "No reset token provided.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$user_for_reset = $userModel->verifyPasswordResetToken($token); // Changed variable name
if (!$user_for_reset) {
    $_SESSION['error_message'] = "Invalid or expired password reset token.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$page_error_message = ''; // For errors displayed on the reset_password.php page itself

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // Token should be resubmitted with the form to ensure it's the same user session
    $submitted_token = $_POST['token'] ?? '';

    if ($submitted_token !== $token) {
        // Token mismatch, possibly tampered or stale form
        $_SESSION['error_message'] = "Token mismatch. Please try the reset process again.";
        header("Location: " . BASE_URL . "/forgot_password.php");
        exit;
    }

    if (empty($new_password) || strlen($new_password) < 6) {
        $page_error_message = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $page_error_message = "Passwords do not match.";
    } else {
        if ($userModel->resetPassword($token, $new_password)) {
            $_SESSION['success_message'] = "Your password has been reset successfully. Please login.";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        } else {
            // This error will be shown on the current page (reset_password.php)
            $page_error_message = "Failed to reset password. The token might have just expired or an internal error occurred. Please try requesting a new reset link.";
        }
    }
}

$page_title = "Reset Password";
include APP_ROOT . '/templates/layouts/header.php'; // This will display session messages if any were set before a redirect.
?>

<div class="form-container">
    <h2>Reset Your Password</h2>
    <div class="form-messages">
        <?php if (!empty($page_error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($page_error_message); ?></div>
        <?php endif; ?>
    </div>
    <form id="resetPasswordForm" method="POST" action="<?php echo BASE_URL; ?>/reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="form-group">
            <label for="reset_new_password">New Password:</label>
            <input type="password" id="reset_new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="reset_confirm_password">Confirm New Password:</label>
            <input type="password" id="reset_confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
</div>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
