<?php
require_once '../config/config.php';
require_once APP_ROOT . '/src/includes/session_management.php';
require_once APP_ROOT . '/src/includes/auth_helpers.php';
// May need db_connection.php if loading/saving settings from DB in future.
// require_once APP_ROOT . '/src/includes/db_connection.php'; 

secure_session_start();
requireLogin();
requireRole('Admin'); // Only Admins can access

$page_title = "System Configuration - Admin Panel";
include APP_ROOT . '/templates/layouts/header.php';

// In a real application, you would load current settings from config.php or a database table.
// $current_settings = [
//    'site_name' => SITE_NAME,
//    'base_url' => BASE_URL,
//    'ocr_language' => OCR_LANGUAGE,
//    'max_file_size_mb' => MAX_FILE_SIZE / 1024 / 1024,
//    // ... other settings from config or DB
// ];

// Handle form submission if settings were being saved (conceptual for now)
$update_errors = [];
$update_success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // $_SESSION['info_message'] = "Configuration saving is not implemented in this placeholder version.";
    // header("Location: " . BASE_URL . "/admin_config.php");
    // exit;
    
    // Conceptual:
    // $new_site_name = $_POST['site_name'] ?? SITE_NAME;
    // $new_ocr_language = $_POST['ocr_language'] ?? OCR_LANGUAGE;
    // ... validate and save these settings (e.g., update a config file or DB)
    // For now, just show a message.
    $update_success_message = "Settings saving is conceptual. No changes were made.";
}

?>

<h2>Admin Panel - System Configuration</h2>
<div class="form-container" style="width: 70%; margin: 20px auto;"> <!-- Added form-container -->
    <p>This section will allow administrators to manage various system-wide settings.</p>

    <?php if ($update_success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($update_success_message); ?></div>
<?php endif; ?>
<?php if (!empty($update_errors)): ?>
    <div class="alert alert-danger"><ul><?php foreach ($update_errors as $err) echo '<li>'.htmlspecialchars($err).'</li>'; ?></ul></div>
<?php endif; ?>


<form id="systemConfigForm" method="POST" action="<?php echo BASE_URL; ?>/admin_config.php">
    <fieldset>
        <legend>General Settings (Examples - Not Editable Yet)</legend>
        <div class="form-group">
            <label for="site_name">Site Name:</label>
            <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars(SITE_NAME); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="base_url">Base URL:</label>
            <input type="text" id="base_url" name="base_url" value="<?php echo htmlspecialchars(BASE_URL); ?>" readonly>
        </div>
         <div class="form-group">
            <label for="max_file_size">Max Upload File Size (MB):</label>
            <input type="number" id="max_file_size" name="max_file_size" value="<?php echo htmlspecialchars(MAX_FILE_SIZE / 1024 / 1024); ?>" readonly>
        </div>
    </fieldset>

    <fieldset style="margin-top: 20px;">
        <legend>OCR Settings (Placeholders)</legend>
        <div class="form-group">
            <label for="ocr_language">Default OCR Language:</label>
            <input type="text" id="ocr_language" name="ocr_language" value="<?php echo htmlspecialchars(defined('OCR_LANGUAGE') ? OCR_LANGUAGE : 'eng'); ?>" readonly>
            <small>Example: 'eng', 'deu'. Changes here would require updating config and potentially server environment.</small>
        </div>
        <div class="form-group">
            <label for="ocr_confidence_threshold">OCR Confidence Threshold (%):</label>
            <input type="number" id="ocr_confidence_threshold" name="ocr_confidence_threshold" value="75" readonly>
            <small>Placeholder: Minimum confidence score for accepting OCR results.</small>
        </div>
    </fieldset>
    
    <fieldset style="margin-top: 20px;">
        <legend>Email Settings (Placeholders - From config.php)</legend>
         <div class="form-group">
            <label for="mail_from_address">Mail From Address:</label>
            <input type="email" id="mail_from_address" name="mail_from_address" value="<?php echo htmlspecialchars(MAIL_FROM_ADDRESS); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="mail_from_name">Mail From Name:</label>
            <input type="text" id="mail_from_name" name="mail_from_name" value="<?php echo htmlspecialchars(MAIL_FROM_NAME); ?>" readonly>
        </div>
        <!-- Add more email server settings if needed, e.g., MAIL_HOST, MAIL_PORT, displayed as readonly -->
    </fieldset>

    <fieldset style="margin-top: 20px;">
        <legend>Financial Settings (Placeholders)</legend>
        <div class="form-group">
            <label for="default_financial_year_start_month_day">Default Financial Year Start (Month-Day):</label>
            <input type="text" id="default_financial_year_start_month_day" name="default_financial_year_start_month_day" value="01-01" readonly>
            <small>Example: '01-01' for January 1st. Used for auto-creating new quota periods.</small>
        </div>
         <div class="form-group">
            <label for="default_employee_quota">Default Annual Medical Quota Amount:</label>
            <input type="number" step="0.01" id="default_employee_quota" name="default_employee_quota" value="1000.00" readonly>
        </div>
    </fieldset>
    
    <!-- <button type="submit" name="save_settings" class="btn btn-primary" style="margin-top:20px;">Save Settings (Conceptual)</button> -->
    <p style="margin-top:20px; font-weight:bold;">Note: This is a placeholder page. Settings are currently read-only and not saved.</p>
    </form>
</div> <!-- Closed form-container -->

<?php include APP_ROOT . '/templates/layouts/footer.php'; ?>
