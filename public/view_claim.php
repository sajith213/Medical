<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/Claim.php';
require_once APP_ROOT . '/src/models/User.php'; // For getting roles
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';

secure_session_start();
requireLogin();

$claim_id = $_GET['id'] ?? null;
if (!$claim_id || !is_numeric($claim_id)) {
    $_SESSION['error_message'] = "Invalid claim ID.";
    header("Location: " . BASE_URL . (hasAnyRole(['Admin', 'HR Staff']) ? "/hr_claims.php" : "/my_claims.php"));
    exit;
}

$claimModel = new Claim($pdo);
$claim = $claimModel->getClaimById((int)$claim_id);

if (!$claim) {
    $_SESSION['error_message'] = "Claim not found.";
     header("Location: " . BASE_URL . (hasAnyRole(['Admin', 'HR Staff']) ? "/hr_claims.php" : "/my_claims.php"));
    exit;
}

// Authorization: User must be owner, or HR Staff, or Admin
$current_user_id = getCurrentUserId();
$current_user_role = getCurrentUserRole(); // From session

if ($claim['user_id'] != $current_user_id && !hasAnyRole(['Admin', 'HR Staff'])) {
    $_SESSION['error_message'] = "You are not authorized to view this claim.";
    header("Location: " . BASE_URL . "/my_claims.php");
    exit;
}

// $userModel = new User($pdo); // Not strictly needed here unless fetching more HR users
$all_statuses = Claim::getClaimStatuses();
// $hr_users = []; // Not needed for this implementation scope


// Handle HR status update
$update_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && hasAnyRole(['Admin', 'HR Staff'])) {
    $new_status = $_POST['status'] ?? '';
    $comments = trim($_POST['comments'] ?? '');
    $hr_staff_id_processing = getCurrentUserId(); // HR staff doing the action

    if (empty($new_status) || !in_array($new_status, $all_statuses)) {
        $update_errors[] = "Invalid status selected.";
    }
    if (($new_status === 'Rejected' || $new_status === 'Needs Information') && empty($comments)) {
        $update_errors[] = "Comments are required for 'Rejected' or 'Needs Information' status.";
    }

    if (empty($update_errors)) {
        if ($claimModel->updateClaimStatus((int)$claim_id, $new_status, $hr_staff_id_processing, $comments)) {
            $_SESSION['success_message'] = "Claim status updated successfully to " . htmlspecialchars($new_status) . ".";
            // TODO: Email notification to employee would go here
            header("Location: " . BASE_URL . "/view_claim.php?id=" . $claim_id); // Refresh to see changes
            exit;
        } else {
            $update_errors[] = "Failed to update claim status. Please check logs or try again.";
        }
    }
}


$page_title = "View Claim #" . htmlspecialchars($claim['claim_id']);
include APP_ROOT . '/templates/layouts/header.php';
?>

<h2>Claim Details: #<?php echo htmlspecialchars($claim['claim_id']); ?></h2>

<dl class="claim-details-grid">
    <dt>Employee Name:</dt><dd><?php echo htmlspecialchars($claim['user_full_name']); ?> (ID: <?php echo htmlspecialchars($claim['user_id']); ?>)</dd>
    <dt>Claim Date:</dt><dd><?php echo htmlspecialchars(date('M d, Y', strtotime($claim['claim_date']))); ?></dd>
    <dt>Submitted At:</dt><dd><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($claim['created_at']))); ?></dd>
    <dt>Total Amount:</dt><dd>$<?php echo htmlspecialchars(number_format((float)$claim['total_amount'], 2)); ?></dd>
    <dt>Status:</dt><dd><span class="badge badge-<?php echo htmlspecialchars(str_replace(' ', '', $claim['status'])); ?>"><?php echo htmlspecialchars($claim['status']); ?></span></dd>
    <dt>Description:</dt><dd><?php echo nl2br(htmlspecialchars($claim['description'])); ?></dd>
    
    <?php if ($claim['hr_staff_id']): ?>
        <dt>Processed By:</dt><dd><?php echo htmlspecialchars($claim['hr_staff_full_name'] ?: 'N/A'); ?> (ID: <?php echo htmlspecialchars($claim['hr_staff_id']); ?>)</dd>
        <dt>Processed At:</dt><dd><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($claim['processed_date']))); ?></dd>
    <?php endif; ?>
    <?php if ($claim['comments']): ?>
        <dt>Comments:</dt><dd><?php echo nl2br(htmlspecialchars($claim['comments'])); ?></dd>
    <?php endif; ?>
</dl>

<!-- Placeholder for displaying uploaded documents -->
<h4>Supporting Documents:</h4>
<p><em>Document display and management will be implemented in a later step.</em></p>

<?php if (hasAnyRole(['Admin', 'HR Staff'])): ?>
    <hr>
    <h3>Process Claim (HR/Admin Action)</h3>
    <div class="form-container" style="width:70%; margin-left:0; padding:15px;">
         <div class="form-messages">
            <?php if (!empty($update_errors)): ?>
                <div class="alert alert-danger"><ul><?php foreach ($update_errors as $error) echo '<li>'.htmlspecialchars($error).'</li>'; ?></ul></div>
            <?php endif; ?>
        </div>
        <form class="hrUpdateClaimForm" method="POST" action="<?php echo BASE_URL; ?>/view_claim.php?id=<?php echo $claim_id; ?>">
            <input type="hidden" name="claim_id" value="<?php echo $claim_id; ?>">
            <div class="form-group">
                <label for="status">Change Status:</label>
                <select id="status" name="status" required>
                    <?php foreach ($all_statuses as $status_option): ?>
                        <option value="<?php echo htmlspecialchars($status_option); ?>" <?php echo ($claim['status'] == $status_option ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($status_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="comments">Comments (Required for 'Rejected' or 'Needs Information'):</label>
                <textarea id="comments" name="comments" rows="3" class="form-control"><?php echo htmlspecialchars($_POST['comments'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
        </form>
    </div>
<?php endif; ?>

<p style="margin-top:20px;">
    <?php if (hasAnyRole(['Admin', 'HR Staff'])): ?>
         <a href="<?php echo BASE_URL; ?>/hr_claims.php" class="btn">Back to Claims List</a>
    <?php else: ?>
         <a href="<?php echo BASE_URL; ?>/my_claims.php" class="btn">Back to My Claims</a>
    <?php endif; ?>
</p>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
