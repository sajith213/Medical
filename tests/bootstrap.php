<?php
// Define a base path for the application if not already defined by test runner
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__)); // Points to project root
}

// Load configuration (needed for DB credentials, paths etc.)
require_once APP_ROOT . '/config/config.php';

// Database Connection (Potentially connect to a test database)
// For simplicity in this example, we'll use the main DB connection.
// In a real setup, you'd use a separate test DB and manage its state.
require_once APP_ROOT . '/src/includes/db_connection.php'; // Provides $pdo

// Autoload models or include them directly
// Simple autoloader for this example:
spl_autoload_register(function ($class_name) {
    // Adjust path for namespaces if used, e.g. App\Services\MailerService
    if (strpos($class_name, 'App\\') === 0) { // Note the double backslash for namespace separator in a string
        $class_file = APP_ROOT . '/src/' . str_replace(['App\\', '\\'], ['', '/'], $class_name) . '.php';
    } else {
        // Assuming models are directly in src/models without namespace for this example
        $class_file = APP_ROOT . '/src/models/' . $class_name . '.php';
    }

    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// If using PHPUnit, this file would be specified in phpunit.xml as the bootstrap file.
?>
