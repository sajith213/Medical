<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';

function secure_session_start() {
    if (session_status() == PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0, // 0 = until browser closes
            'path' => '/',
            'domain' => '', // Current domain
            'secure' => defined('SESSION_SECURE') ? SESSION_SECURE : false,
            'httponly' => defined('SESSION_HTTP_ONLY') ? SESSION_HTTP_ONLY : true,
            'samesite' => 'Lax' // Or 'Strict'
        ]);
        session_start();
    }
}

// Function to regenerate session ID periodically to prevent session fixation
function regenerate_session_id() {
    if (isset($_SESSION['last_regen']) && (time() - $_SESSION['last_regen'] > (15 * 60))) { // Regenerate every 15 minutes
        session_regenerate_id(true);
        $_SESSION['last_regen'] = time();
    } elseif (!isset($_SESSION['last_regen'])) {
        $_SESSION['last_regen'] = time();
    }
}

// Call secure_session_start() at the beginning of scripts that need session
// Call regenerate_session_id() on sensitive pages or after login/logout
?>
