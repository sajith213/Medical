<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/Claim.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';

secure_session_start();
requireLogin();
// requireRole('Employee'); // Or allow HR/Admin to see this if they impersonate or via different view

$claimModel = new Claim($pdo);
$user_id = getCurrentUserId();
$my_claims = $claimModel->getClaimsByUserId($user_id);

$page_title = "My Medical Claims";
include APP_ROOT . '/templates/layouts/header.php';
?>

<h2>My Submitted Claims</h2>
<a href="<?php echo BASE_URL; ?>/submit_claim.php" class="btn btn-primary" style="margin-bottom:20px;">Submit New Claim</a>

<?php if (empty($my_claims)): ?>
    <div class="alert alert-info">You have not submitted any claims yet.</div>
<?php else: ?>
    <table class="claims-list">
        <thead>
            <tr>
                <th>Claim ID</th>
                <th>Claim Date</th>
                <th>Description</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($my_claims as $claim): ?>
                <tr>
                    <td><?php echo htmlspecialchars($claim['claim_id']); ?></td>
                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($claim['claim_date']))); ?></td>
                    <td><?php echo htmlspecialchars(substr($claim['description'], 0, 50) . (strlen($claim['description']) > 50 ? '...' : '')); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format((float)$claim['total_amount'], 2)); ?></td>
                    <td>
                        <span class="badge badge-<?php echo htmlspecialchars(str_replace(' ', '', $claim['status'])); ?>">
                            <?php echo htmlspecialchars($claim['status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($claim['created_at']))); ?></td>
                    <td class="action-links">
                        <a href="<?php echo BASE_URL; ?>/view_claim.php?id=<?php echo $claim['claim_id']; ?>" class="view-link">View</a>
                        <?php if (in_array($claim['status'], ['Submitted', 'Needs Information'])): ?>
                            <!-- <a href="<?php echo BASE_URL; ?>/edit_claim.php?id=<?php echo $claim['claim_id']; ?>" class="edit-link">Edit</a> 
                                 Edit Claim page to be built later if time permits or requirement is strong -->
                            <!-- <a href="<?php echo BASE_URL; ?>/delete_claim.php?id=<?php echo $claim['claim_id']; ?>" class="delete-link delete-claim-link">Delete</a>
                                 Delete Claim functionality to be built later -->
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
