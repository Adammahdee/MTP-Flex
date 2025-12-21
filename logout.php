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

// Clear Remember Me (DB and Cookie)
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/config/db.php';
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) { /* Ignore */ }
}

if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/", "", false, true);
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