<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/User.php';
require_once APP_ROOT . '/src/includes/session_management.php';

secure_session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$userModel = new User($pdo);
$error_message = '';
$username = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        $user = $userModel->login($username, $password);
        if ($user && !isset($user['error'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_id'] = $user['role_id']; // Storing role_id
            $_SESSION['role_name'] = $userModel->getUserRole($user['user_id']); // Storing role_name
            $_SESSION['logged_in_at'] = time();
            regenerate_session_id(); // Security measure
            
            $_SESSION['success_message'] = "Login successful. Welcome back, " . htmlspecialchars($user['full_name'] ?: $user['username']) . "!";
            header("Location: " . BASE_URL . "/dashboard.php"); // Redirect to a dashboard page
            exit;
        } elseif (isset($user['error'])) {
             $error_message = $user['error']; // e.g. "Account is deactivated."
        } else {
            $error_message = "Invalid username or password.";
        }
    }
}
$page_title = "Login";
include APP_ROOT . '/templates/layouts/header.php';
?>

<div class="form-container">
    <h2>User Login</h2>
    <div class="form-messages">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
    </div>
    <form id="loginForm" method="POST" action="<?php echo BASE_URL; ?>/login.php">
        <div class="form-group">
            <label for="login_username">Username:</label>
            <input type="text" id="login_username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
        </div>
        <div class="form-group">
            <label for="login_password">Password:</label>
            <input type="password" id="login_password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Login</button>
        <p style="margin-top: 15px;">
            <a href="<?php echo BASE_URL; ?>/forgot_password.php">Forgot Password?</a>
        </p>
    </form>
</div>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
