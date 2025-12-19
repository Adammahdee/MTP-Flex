<?php
/**
 * Admin Guard & Initialization Script
 *
 * This script should be included at the very top of every page in the admin panel.
 * It handles session start, security checks, and establishes the admin database connection.
 */

// 1. Start or resume the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Security Check: Ensure the user is logged in and is an administrator.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    // Redirect non-admins to the public login page with an error message.
    header("Location: ../../logout.php?reason=unauthorized");
    exit;
}

// 3. Establish the high-privilege database connection for admin use.
require_once __DIR__ . '/../config/db.php';
$pdo = get_pdo_connection(DB_ADMIN_USER, DB_ADMIN_PASS);
