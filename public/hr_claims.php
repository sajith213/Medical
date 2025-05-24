<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/Claim.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';

secure_session_start();
requireLogin();
requireAnyRole(['Admin', 'HR Staff']); // Only HR and Admin

$claimModel = new Claim($pdo);

// Filter by status
$filter_status = $_GET['status'] ?? null;
$all_statuses = Claim::getClaimStatuses(); // Get all possible statuses for filter dropdown

if ($filter_status && !in_array($filter_status, $all_statuses)) {
    $_SESSION['error_message'] = "Invalid filter status provided: " . htmlspecialchars($filter_status);
    $filter_status = null; // Invalid status, so ignore filter and show message
}

$all_claims = $claimModel->getAllClaims($filter_status);

$page_title = "Manage All Claims";
include APP_ROOT . '/templates/layouts/header.php'; // This will also display any session messages
?>

<h2>All Submitted Claims <?php if ($filter_status) echo "(Status: " . htmlspecialchars($filter_status) . ")"; ?></h2>

<form method="GET" action="<?php echo BASE_URL; ?>/hr_claims.php" style="margin-bottom: 20px; background-color:#f8f9fa; padding:15px; border-radius:5px;">
    <div class="form-group" style="display:inline-block; margin-right:10px;">
        <label for="status_filter">Filter by Status:</label>
        <select name="status" id="status_filter" class="form-control" style="width:auto; display:inline-block;">
            <option value="">All Statuses</option>
            <?php foreach ($all_statuses as $status_option): ?>
                <option value="<?php echo htmlspecialchars($status_option); ?>" <?php echo ($filter_status == $status_option ? 'selected' : ''); ?>>
                    <?php echo htmlspecialchars($status_option); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Filter</button>
     <a href="<?php echo BASE_URL; ?>/hr_claims.php" class="btn" style="margin-left:5px;">Clear Filter</a>
</form>


<?php if (empty($all_claims)): ?>
    <div class="alert alert-info">No claims found<?php if ($filter_status) echo " with status '" . htmlspecialchars($filter_status) . "'"; ?>.</div>
<?php else: ?>
    <table class="claims-list">
        <thead>
            <tr>
                <th>Claim ID</th>
                <th>Employee</th>
                <th>Claim Date</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Submitted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all_claims as $claim): ?>
                <tr>
                    <td><?php echo htmlspecialchars($claim['claim_id']); ?></td>
                    <td><?php echo htmlspecialchars($claim['user_full_name'] ?: $claim['username']); ?> (ID: <?php echo htmlspecialchars($claim['user_id']); ?>)</td>
                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($claim['claim_date']))); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format((float)$claim['total_amount'], 2)); ?></td>
                    <td>
                        <span class="badge badge-<?php echo htmlspecialchars(str_replace(' ', '', $claim['status'])); ?>">
                            <?php echo htmlspecialchars($claim['status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($claim['created_at']))); ?></td>
                    <td class="action-links">
                        <a href="<?php echo BASE_URL; ?>/view_claim.php?id=<?php echo $claim['claim_id']; ?>" class="view-link">View/Process</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
