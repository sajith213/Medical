<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/User.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';

secure_session_start();
requireLogin();
requireRole('Admin'); // Only Admins can access this page

$userModel = new User($pdo);
$all_users = $userModel->getAllUsers(); // Fetches users with their role names
// $all_roles = $userModel->getAllRoles(); // Not needed on this page, but in edit_user

$page_title = "Manage Users - Admin Panel";
include APP_ROOT . '/templates/layouts/header.php'; // header.php already has Admin links if user is Admin
?>

<h2>Admin Panel - Manage Users</h2>

<?php if (empty($all_users)): ?>
    <div class="alert alert-info">No users found in the system.</div>
<?php else: ?>
    <table class="claims-list"> <!-- Using existing class for table styling -->
        <thead>
            <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Registered At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                    <td><?php echo $user['is_active'] ? '<span class="badge badge-Approved">Active</span>' : '<span class="badge badge-Rejected">Inactive</span>'; ?></td>
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                    <td class="action-links">
                        <a href="<?php echo BASE_URL; ?>/admin_edit_user.php?id=<?php echo $user['user_id']; ?>" class="edit-link">Edit</a>
                        <!-- Add Activate/Deactivate links or integrate into Edit page -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
