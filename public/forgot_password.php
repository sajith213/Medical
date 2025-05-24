<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/User.php';
require_once APP_ROOT . '/src/includes/session_management.php';

secure_session_start();

$userModel = new User($pdo);
$message = ''; // Can be success or error
$message_type = 'info'; // Default message type for session messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: " . BASE_URL . "/forgot_password.php");
        exit;
    } else {
        $user = $userModel->findByEmail($email);
        if ($user) {
            $token = $userModel->createPasswordResetToken($user['user_id']);
            if ($token) {
                // In a real app, send email here
                $reset_link = BASE_URL . "/reset_password.php?token=" . urlencode($token);
                // For now, display link/token for testing
                $_SESSION['info_message'] = "If your email exists in our system, a password reset link has been sent. For testing, token: " . htmlspecialchars($token) . " <br>Link: <a href='" . $reset_link . "'>" . $reset_link . "</a>";
                 header("Location: " . BASE_URL . "/forgot_password.php"); 
                 exit;
            } else {
                $_SESSION['error_message'] = "Could not generate a reset token. Please try again later.";
                header("Location: " . BASE_URL . "/forgot_password.php");
                exit;
            }
        } else {
             // Show generic message even if user not found for security
             $_SESSION['info_message'] = "If your email exists in our system, a password reset link has been sent.";
             header("Location: " . BASE_URL . "/forgot_password.php"); 
             exit;
        }
    }
}

$page_title = "Forgot Password";
include APP_ROOT . '/templates/layouts/header.php'; // This will display session messages
?>

<div class="form-container">
    <h2>Forgot Password</h2>
    <div class="form-messages">
        <?php if (!empty($message)): // This specific message variable is not used anymore due to redirect and flash messages ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
    </div>
    <p>Enter your email address and we'll send you a link to reset your password (conceptually).</p>
    <form id="requestPasswordResetForm" method="POST" action="<?php echo BASE_URL; ?>/forgot_password.php">
        <div class="form-group">
            <label for="reset_email">Email:</label>
            <input type="email" id="reset_email" name="email" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
    </form>
     <p style="margin-top: 15px;"><a href="<?php echo BASE_URL; ?>/login.php">Back to Login</a></p>
</div>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
