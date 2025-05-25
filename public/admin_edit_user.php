<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/User.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';

secure_session_start();
requireLogin();
requireRole('Admin');

$user_id_to_edit = $_GET['id'] ?? null;
if (!$user_id_to_edit || !is_numeric($user_id_to_edit)) {
    $_SESSION['error_message'] = "Invalid user ID specified for editing.";
    header("Location: " . BASE_URL . "/admin_users.php");
    exit;
}

$userModel = new User($pdo);
$user_data = $userModel->findById($user_id_to_edit); // Fetches basic user data

if (!$user_data) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: " . BASE_URL . "/admin_users.php");
    exit;
}

// Fetch all roles for the dropdown
$all_roles = $userModel->getAllRoles();
$current_user_role_id = $user_data['role_id']; // Get current role_id for pre-selection

$errors = [];
// Initialize submitted data with existing data, then override with POST if available
$submitted_username = $user_data['username'];
$submitted_email = $user_data['email'];
$submitted_full_name = $user_data['full_name'];
$submitted_role_id_from_post = $current_user_role_id; // Default to current if not in POST
$submitted_is_active_from_post = $user_data['is_active']; // Default to current if not in POST


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_username = trim($_POST['username'] ?? $user_data['username']);
    $submitted_email = trim($_POST['email'] ?? $user_data['email']);
    $submitted_full_name = trim($_POST['full_name'] ?? $user_data['full_name']);
    $submitted_role_id_from_post = $_POST['role_id'] ?? $current_user_role_id;
    // For checkbox, if 'is_active' is not in POST, it means it was unchecked (value 0)
    // If it is in POST, its value is "1".
    $submitted_is_active_from_post = isset($_POST['is_active']) ? 1 : 0;

    // Validation
    if (empty($submitted_username) || strlen($submitted_username) < 3) $errors[] = "Username must be at least 3 characters long.";
    if (empty($submitted_email) || !filter_var($submitted_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (empty($submitted_full_name)) $errors[] = "Full name cannot be empty.";
    if (empty($submitted_role_id_from_post) || !is_numeric($submitted_role_id_from_post)) $errors[] = "A valid role must be selected.";
    
    // Prevent Admin from deactivating their own account or changing their own role
    $current_admin_id = getCurrentUserId();
    if ($user_id_to_edit == $current_admin_id) {
        $admin_role_id_from_db = null; // Find the ID for 'Admin' role
        foreach($all_roles as $role_obj) { 
            if ($role_obj['role_name'] == 'Admin') {
                $admin_role_id_from_db = $role_obj['role_id']; 
                break;
            }
        }

        if ($submitted_role_id_from_post != $admin_role_id_from_db) {
             $errors[] = "Cannot change your own role from Admin.";
        }
        if (!$submitted_is_active_from_post) {
            $errors[] = "Cannot deactivate your own Admin account.";
        }
    }


    if (empty($errors)) {
        try {
            if ($userModel->updateUserByAdmin($user_id_to_edit, $submitted_username, $submitted_email, $submitted_full_name, $submitted_role_id_from_post, $submitted_is_active_from_post)) {
                $_SESSION['success_message'] = "User details updated successfully for '" . htmlspecialchars($submitted_username) . "'.";
                header("Location: " . BASE_URL . "/admin_users.php");
                exit;
            } else {
                $errors[] = "Failed to update user details. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = "Error updating user: " . $e->getMessage(); // Catches unique constraint violations from model
        }
    }
     // If errors or if not POST, re-populate form with (potentially submitted) values for correction
    $user_data['username'] = $submitted_username;
    $user_data['email'] = $submitted_email;
    $user_data['full_name'] = $submitted_full_name;
    $current_user_role_id = $submitted_role_id_from_post; // Keep the selection from POST
    $user_data['is_active'] = $submitted_is_active_from_post; // Keep the selection from POST
}

$page_title = "Edit User: " . htmlspecialchars($user_data['username']);
include APP_ROOT . '/templates/layouts/header.php';
?>

<div class="form-container">
    <h2>Edit User: <?php echo htmlspecialchars($user_data['username']); ?></h2>
    <div class="form-messages">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo '<li>'.htmlspecialchars($error).'</li>'; ?></ul></div>
        <?php endif; ?>
    </div>

    <form id="adminEditUserForm" method="POST" action="<?php echo BASE_URL; ?>/admin_edit_user.php?id=<?php echo $user_id_to_edit; ?>">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
        </div>
        <div class="form-group">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="role_id">Role:</label>
            <select id="role_id" name="role_id" required>
                <?php foreach ($all_roles as $role): ?>
                    <option value="<?php echo htmlspecialchars($role['role_id']); ?>" <?php echo ($role['role_id'] == $current_user_role_id ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($role['role_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="is_active" style="display: block; margin-bottom: 5px;">Account Status:</label>
            <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($user_data['is_active'] ? 'checked' : ''); ?>>
            <label for="is_active" style="display:inline; margin-left:5px; font-weight:normal;">Active</label>
            <small style="display: block; margin-top: 5px;">(Uncheck to deactivate account)</small>
        </div>
        
        <p style="margin-top: 20px;"><em>Password can only be changed by the user themselves via password reset.</em></p>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="<?php echo BASE_URL; ?>/admin_users.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
    </form>
</div>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
