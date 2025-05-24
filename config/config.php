<?php
// Database Configuration
define('DB_HOST', 'localhost'); // Or your MySQL host
define('DB_USER', 'your_db_user'); // Replace with your DB username
define('DB_PASS', 'your_db_password'); // Replace with your DB password
define('DB_NAME', 'employee_medical_claims'); // Replace with your DB name

// Application Settings
define('APP_ROOT', dirname(dirname(__FILE__))); // Path to the project root
define('BASE_URL', 'http://localhost/medical_claims_app/public'); // Adjust if your app is in a subdirectory
define('SITE_NAME', 'Employee Medical Claim System');

// Session Configuration
define('SESSION_SECURE', false); // Set to true if using HTTPS
define('SESSION_HTTP_ONLY', true);
define('SESSION_USE_ONLY_COOKIES', true);

// File Upload Configuration
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// OCR Settings (placeholders)
define('OCR_LANGUAGE', 'eng');

// Email Configuration (placeholders for now)
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
// Add this line
define('SECRET_KEY', 'your-very-secret-and-long-random-string'); // User should change this
define('MAIL_USERNAME', 'user@example.com');
define('MAIL_PASSWORD', 'password');
define('MAIL_FROM_ADDRESS', 'noreply@example.com');
define('MAIL_FROM_NAME', SITE_NAME);

?>
