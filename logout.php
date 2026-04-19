<?php
/**
 * Direct Logout Page
 * Alternative method to logout without AJAX
 */

// Start session
session_start();

// Log the logout activity if user is logged in
if (isset($_SESSION['staff_id'])) {
    require_once 'db_connect.php';
    
    try {
        log_activity(
            $_SESSION['staff_id'],
            'LOGOUT',
            'staff',
            $_SESSION['staff_id'],
            'User logged out via direct logout page'
        );
    } catch (Exception $e) {
        // Ignore logging errors, just logout
    }
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: login.php?logout=success");
exit;
?>