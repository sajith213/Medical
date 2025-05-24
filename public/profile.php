<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/User.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';

secure_session_start();
requireLogin();

$userModel = new User($pdo);
$current_user_id = getCurrentUserId();
$user_data = $userModel->findById($current_user_id);

$update_errors = [];
// $update_success_message = ''; // Using session flash messages instead
$change_password_errors = [];
// $change_password_success_message = ''; // Using session flash messages instead

if (!$user_data) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($full_name)) $update_errors[] = "Full name cannot be empty.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $update_errors[] = "Invalid email address.";

    if (empty($update_errors)) {
        try {
            if ($userModel->updateProfile($current_user_id, $full_name, $email)) {
                $_SESSION['full_name'] = $full_name; // Update session
                $user_data['full_name'] = $full_name; // Update current page data
                $user_data['email'] = $email;
                $_SESSION['success_message'] = "Profile updated successfully.";
                header("Location: " . BASE_URL . "/profile.php"); // Refresh to show message
                exit;
            } else {
                $update_errors[] = "Profile update failed. Please try again.";
            }
        } catch (Exception $e) {
            $update_errors[] = $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if(empty($current_password)) $change_password_errors[] = "Current password is required.";
    if(empty($new_password) || strlen($new_password) < 6) $change_password_errors[] = "New password must be at least 6 characters.";
    if($new_password !== $confirm_new_password) $change_password_errors[] = "New passwords do not match.";

    if(empty($change_password_errors)) {
        if($userModel->changePassword($current_user_id, $current_password, $new_password)) {
             $_SESSION['success_message'] = "Password changed successfully.";
             header("Location: " . BASE_URL . "/profile.php"); // Refresh to show message
             exit;
        } else {
            $change_password_errors[] = "Failed to change password. Current password might be incorrect or an error occurred.";
        }
    }
}


$page_title = "My Profile";
include APP_ROOT . '/templates/layouts/header.php';
?>

<h2>My Profile</h2>

<div class="form-container" style="width:60%;">
    <h3>Update Information</h3>
    <div class="form-messages">
         <?php if (!empty($update_errors)): ?>
            <div class="alert alert-danger"><ul><?php foreach ($update_errors as $err) echo '<li>'.htmlspecialchars($err).'</li>'; ?></ul></div>
        <?php endif; ?>
    </div>
    <form id="profileForm" method="POST" action="<?php echo BASE_URL; ?>/profile.php">
        <div class="form-group">
            <label for="profile_username">Username:</label>
            <input type="text" id="profile_username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly disabled>
        </div>
        <div class="form-group">
            <label for="profile_full_name">Full Name:</label>
            <input type="text" id="profile_full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="profile_email">Email:</label>
            <input type="email" id="profile_email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>
        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<div class="form-container" style="width:60%; margin-top: 30px;">
    <h3>Change Password</h3>
     <div class="form-messages">
        <?php if (!empty($change_password_errors)): ?>
            <div class="alert alert-danger"><ul><?php foreach ($change_password_errors as $err) echo '<li>'.htmlspecialchars($err).'</li>'; ?></ul></div>
        <?php endif; ?>
    </div>
    <form id="changePasswordForm" method="POST" action="<?php echo BASE_URL; ?>/profile.php">
        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_new_password">Confirm New Password:</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" required>
        </div>
        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
    </form>
</div>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
