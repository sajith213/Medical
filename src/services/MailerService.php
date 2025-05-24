<?php
namespace App\Services;

// Placeholder for PHPMailer class.
// In a real setup with Composer:
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailerService {
    private $mail; // This would be an instance of PHPMailer

    public function __construct() {
        // Conceptual PHPMailer setup.
        // Ensure config constants are loaded if this class is used standalone.
        // Typically, config.php is loaded by the entry script.
        if (!defined('MAIL_HOST')) {
             // This indicates config.php might not have been loaded.
             // For this task, we assume it's loaded. If not, this would be a critical error.
             error_log("MailerService: Configuration constants like MAIL_HOST are not defined. Ensure config.php is loaded.");
        }

        // if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        //     // In a real app, this might throw an error or log if PHPMailer is missing.
        //     // For this task, we proceed with a mock/conceptual setup.
        //     error_log("PHPMailer class not found. Email sending will be simulated.");
        // }

        // $this->mail = new PHPMailer(true); // Passing `true` enables exceptions

        // Server settings from config.php
        // $this->mail->isSMTP(); // Send using SMTP
        // $this->mail->Host       = MAIL_HOST;
        // $this->mail->SMTPAuth   = true;
        // $this->mail->Username   = MAIL_USERNAME;
        // $this->mail->Password   = MAIL_PASSWORD;
        // $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Or ENCRYPTION_SMTPS
        // $this->mail->Port       = MAIL_PORT;

        //Recipients
        // $this->mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    }

    /**
     * Sends an email.
     * In this simulated environment, it will log the email content instead of sending.
     *
     * @param string $toEmail Recipient's email address.
     * @param string $toName Recipient's name.
     * @param string $subject Email subject.
     * @param string $htmlBody HTML content of the email.
     * @param string $plainTextBody Plain text alternative content (optional).
     * @return bool True if email "sent" (logged), false on error.
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlBody, $plainTextBody = '') {
        if (empty($toEmail) || empty($subject) || empty($htmlBody)) {
            error_log("Email sending failed: Missing required parameters (To: $toEmail, Subject: $subject).");
            return false;
        }
        
        // Ensure config constants are available for logging the "From" address
        $from_name = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Default Sender';
        $from_address = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@example.com';


        // --- Actual PHPMailer sending logic would be here ---
        /*
        try {
            //Recipients
            $this->mail->addAddress($toEmail, $toName);

            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $htmlBody;
            $this->mail->AltBody = $plainTextBody ?: strip_tags($htmlBody);

            $this->mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log("PHPMailer Error: {$this->mail->ErrorInfo}");
            return false;
        } finally {
            // Clear addresses and attachments for the next email
            if (method_exists($this->mail, 'clearAddresses')) $this->mail->clearAddresses();
            if (method_exists($this->mail, 'clearAttachments')) $this->mail->clearAttachments();
        }
        */

        // --- Simulation for this environment ---
        $logMessage = "---- EMAIL SIMULATION ----
" .
                      "To: " . htmlspecialchars($toName) . " <" . htmlspecialchars($toEmail) . ">
" .
                      "From: " . htmlspecialchars($from_name) . " <" . htmlspecialchars($from_address) . ">
" .
                      "Subject: " . htmlspecialchars($subject) . "
" .
                      "---- HTML Body ----
$htmlBody
"; // HTML body is kept as is, assuming it's safe for logging or already escaped.
        if ($plainTextBody) {
            $logMessage .= "---- Plain Text Body ----
" . htmlspecialchars($plainTextBody) . "
";
        }
        $logMessage .= "---- END EMAIL SIMULATION ----
";

        error_log($logMessage); // Log to PHP error log
        
        // Assume success for simulation if parameters are present
        return true;
    }

    // --- Template Methods for Specific Notifications ---

    public function sendRegistrationSuccessEmail($userEmail, $userName, $loginUrl) {
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Our Website';
        $subject = "Welcome to " . $site_name . "!";
        $htmlBody = "<p>Hello " . htmlspecialchars($userName) . ",</p>" .
                    "<p>Thank you for registering with " . $site_name . ". Your account has been successfully created.</p>" .
                    "<p>You can login here: <a href='" . htmlspecialchars($loginUrl) . "'>" . htmlspecialchars($loginUrl) . "</a></p>" .
                    "<p>Regards,<br>" . $site_name . " Team</p>";
        $plainTextBody = "Hello " . htmlspecialchars($userName) . ",
Thank you for registering with " . $site_name . ".
Login here: " . htmlspecialchars($loginUrl) . "

Regards,
" . $site_name . " Team";
        return $this->sendEmail($userEmail, $userName, $subject, $htmlBody, $plainTextBody);
    }

    public function sendPasswordResetEmail($userEmail, $userName, $resetLink) {
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Our Website';
        $subject = "Password Reset Request - " . $site_name;
        $htmlBody = "<p>Hello " . htmlspecialchars($userName) . ",</p>" .
                    "<p>You requested a password reset for your account on " . $site_name . ".</p>" .
                    "<p>Please click the link below to reset your password (link valid for 1 hour):<br>" .
                    "<a href='" . htmlspecialchars($resetLink) . "'>" . htmlspecialchars($resetLink) . "</a></p>" .
                    "<p>If you did not request this, please ignore this email.</p>" .
                    "<p>Regards,<br>" . $site_name . " Team</p>";
        $plainTextBody = "Hello " . htmlspecialchars($userName) . ",
Password reset link (valid 1 hour): " . htmlspecialchars($resetLink) . "

If you did not request this, ignore this email.

Regards,
" . $site_name . " Team";
        return $this->sendEmail($userEmail, $userName, $subject, $htmlBody, $plainTextBody);
    }

    public function sendClaimSubmissionConfirmationEmail($userEmail, $userName, $claimId, $claimDetailsUrl) {
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Our Website';
        $subject = "Claim #" . htmlspecialchars($claimId) . " Submitted - " . $site_name;
        $htmlBody = "<p>Hello " . htmlspecialchars($userName) . ",</p>" .
                    "<p>Your medical claim (ID: #" . htmlspecialchars($claimId) . ") has been successfully submitted to " . $site_name . ".</p>" .
                    "<p>You can view your claim details here: <a href='" . htmlspecialchars($claimDetailsUrl) . "'>" . htmlspecialchars($claimDetailsUrl) . "</a></p>" .
                    "<p>We will notify you once there is an update on its status.</p>" .
                    "<p>Regards,<br>" . $site_name . " Team</p>";
        $plainTextBody = "Hello " . htmlspecialchars($userName) . ",
Your medical claim (ID: #" . htmlspecialchars($claimId) . ") has been successfully submitted to " . $site_name . ".
View your claim: " . htmlspecialchars($claimDetailsUrl) . "

Regards,
" . $site_name . " Team";
        return $this->sendEmail($userEmail, $userName, $subject, $htmlBody, $plainTextBody);
    }
            
    public function sendClaimStatusUpdateEmail($userEmail, $userName, $claimId, $newStatus, $claimDetailsUrl, $hrComments = '') {
        $site_name = defined('SITE_NAME') ? SITE_NAME : 'Our Website';
        $subject = "Claim #" . htmlspecialchars($claimId) . " Status Updated to '" . htmlspecialchars($newStatus) . "' - " . $site_name;
        $htmlBody = "<p>Hello " . htmlspecialchars($userName) . ",</p>" .
                    "<p>The status of your medical claim (ID: #" . htmlspecialchars($claimId) . ") has been updated to: <strong>" . htmlspecialchars($newStatus) . "</strong>.</p>";
        if (!empty($hrComments)) {
            $htmlBody .= "<p><strong>Comments from HR:</strong><br>" . nl2br(htmlspecialchars($hrComments)) . "</p>";
        }
        $htmlBody .= "<p>You can view your claim details here: <a href='" . htmlspecialchars($claimDetailsUrl) . "'>" . htmlspecialchars($claimDetailsUrl) . "</a></p>" .
                     "<p>Regards,<br>" . $site_name . " Team</p>";
        $plainTextBody = "Hello " . htmlspecialchars($userName) . ",
The status of your medical claim (ID: #" . htmlspecialchars($claimId) . ") has been updated to: " . htmlspecialchars($newStatus) . ".";
        if (!empty($hrComments)) {
            $plainTextBody .= "\nComments from HR:\n" . htmlspecialchars($hrComments);
        }
        $plainTextBody .= "\n\nView your claim: " . htmlspecialchars($claimDetailsUrl) . "

Regards,
" . $site_name . " Team";
        return $this->sendEmail($userEmail, $userName, $subject, $htmlBody, $plainTextBody);
    }
}
?>
