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
$errors = [];
$username = '';
$email = '';
$full_name = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    if (empty($username) || strlen($username) < 3) $errors[] = "Username must be at least 3 characters.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (empty($full_name)) $errors[] = "Full name is required.";

    if (empty($errors)) {
        try {
            if ($userModel->register($username, $password, $email, $full_name)) {
                $_SESSION['success_message'] = "Registration successful! Please login.";
                header("Location: " . BASE_URL . "/login.php");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage(); // e.g., "Username already exists."
        }
    }
}

$page_title = "Register";
include APP_ROOT . '/templates/layouts/header.php';
?>

<div class="form-container">
    <h2>User Registration</h2>
    <div class="form-messages">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <form id="registerForm" method="POST" action="<?php echo BASE_URL; ?>/register.php">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
