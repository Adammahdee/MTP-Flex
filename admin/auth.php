<?php
// Check if the session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- SECURITY ENHANCEMENTS ---

// 1. Define the inactivity timeout period in seconds (e.g., 30 minutes)
define('MAX_INACTIVITY_SECONDS', 1800); 

// 2. Check for login status and admin role
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// 3. Check for session timeout due to inactivity
$isTimeout = false;
if ($isLoggedIn && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > MAX_INACTIVITY_SECONDS)) {
    $isTimeout = true;
}

// 4. If any security check fails, redirect to logout with a reason
if (!$isLoggedIn || !$isAdmin) {
    header("Location: ../index.php"); // Redirect to homepage
    exit;
}

// 5. If all checks pass, update the last activity timestamp
$_SESSION['last_activity'] = time();
?>