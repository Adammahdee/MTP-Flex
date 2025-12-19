<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the reason for logout to display a message on the login page
$reason = $_GET['reason'] ?? '';
$message = '';
if ($reason === 'timeout') {
    $message = 'You have been logged out due to inactivity.';
} else if ($reason === 'unauthorized') {
    $message = 'You must be logged in as an administrator to access that page.';
}


// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Determine the correct login page to redirect to.
if ($reason === 'unauthorized') {
    // 'unauthorized' is an admin-specific reason, so redirect to the admin login.
    $location = 'admin/login.php';
} else {
    // For all other cases (standard user logout, timeout), redirect to the public login.
    $location = 'login.php';
}

if (!empty($message)) {
    $location .= '?message=' . urlencode($message);
}
header("Location: " . $location);
exit;
?>