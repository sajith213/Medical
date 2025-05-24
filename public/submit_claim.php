<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php';
require_once APP_ROOT . '/src/models/Claim.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';

secure_session_start();
requireLogin(); // Ensure user is logged in
// Optionally, require specific role: requireRole('Employee');

$claimModel = new Claim($pdo);
$errors = [];
$submitted_data = ['claim_date' => '', 'description' => '', 'total_amount' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_data['claim_date'] = $_POST['claim_date'] ?? '';
    $submitted_data['description'] = trim($_POST['description'] ?? '');
    $submitted_data['total_amount'] = trim($_POST['total_amount'] ?? '');
    // Document handling will be added in a later step (OCR integration / Document Management)

    // Server-side validation
    if (empty($submitted_data['claim_date'])) { $errors[] = "Claim date is required."; }
    else {
        $d = DateTime::createFromFormat('Y-m-d', $submitted_data['claim_date']);
        if (!$d || $d->format('Y-m-d') !== $submitted_data['claim_date']) { $errors[] = "Invalid date format. Use YYYY-MM-DD."; }
        // Optional: Check if date is in the past or present
        // if ($d > new DateTime()) { $errors[] = "Claim date cannot be in the future."; }
    }
    if (empty($submitted_data['description']) || strlen($submitted_data['description']) < 10) { $errors[] = "Description must be at least 10 characters long."; }
    if ($submitted_data['total_amount'] === '' || !is_numeric($submitted_data['total_amount']) || (float)$submitted_data['total_amount'] <= 0) {
        $errors[] = "Total amount must be a positive number.";
    }
    // Placeholder for document validation (e.g., if required, if files uploaded)

    if (empty($errors)) {
        $user_id = getCurrentUserId();
        $claim_id = $claimModel->createClaim($user_id, $submitted_data['claim_date'], $submitted_data['description'], $submitted_data['total_amount']);

        if ($claim_id) {
            // TODO: Handle document uploads here, associate with $claim_id
            // For now, just a success message.
            $_SESSION['success_message'] = "Claim submitted successfully! Your Claim ID is: " . $claim_id;
            header("Location: " . BASE_URL . "/my_claims.php"); // Redirect to list of their claims
            exit;
        } else {
            $errors[] = "Failed to submit claim due to a server error. Please try again.";
        }
    }
}

$page_title = "Submit New Claim";
include APP_ROOT . '/templates/layouts/header.php';
?>

<div class="form-container">
    <h2>Submit New Medical Claim</h2>
    <div class="form-messages">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul><?php foreach ($errors as $error) echo '<li>'.htmlspecialchars($error).'</li>'; ?></ul></div>
        <?php endif; ?>
    </div>
    <form id="submitClaimForm" method="POST" action="<?php echo BASE_URL; ?>/submit_claim.php" enctype="multipart/form-data">
        <div class="form-group">
            <label for="claim_date">Claim Date (Date of Service/Purchase):</label>
            <input type="date" id="claim_date" name="claim_date" value="<?php echo htmlspecialchars($submitted_data['claim_date']); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description of Expense:</label>
            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($submitted_data['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="total_amount">Total Amount (e.g., 123.45):</label>
            <input type="text" id="total_amount" name="total_amount" pattern="[0-9]+(\.[0-9]{1,2})?" title="Enter a valid amount e.g. 150 or 150.50" value="<?php echo htmlspecialchars($submitted_data['total_amount']); ?>" required>
        </div>
        
        <!-- Document Upload Placeholder - Full implementation in later step -->
        <div class="form-group">
            <label for="documents">Upload Supporting Documents (Bills, Receipts - PDF, JPG, PNG):</label>
            <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png">
            <small>Max file size: <?php echo (defined('MAX_FILE_SIZE') ? (MAX_FILE_SIZE / 1024 / 1024) : '5'); ?>MB per file. Allowed types: PDF, JPG, PNG.</small>
        </div>

        <button type="submit" class="btn btn-primary">Submit Claim</button>
    </form>
</div>

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
