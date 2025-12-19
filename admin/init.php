<?php
/**
 * Admin Initialization Script
 *
 * This script should be included at the very top of every page in the admin panel.
 * It handles session start, security checks, and establishes the admin database connection.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Security Check: Ensure the user is logged in and is an administrator.
if (!isset($_SESSION['user_id'], $_SESSION['is_admin'])) {
    header("Location: login.php");
    exit;
}

if ((int)$_SESSION['is_admin'] !== 1) {
    http_response_code(403);
    exit("Administrator access only.");
}

require_once __DIR__ . '/../config/db.php';
$pdo = get_pdo_connection(DB_ADMIN_USER, DB_ADMIN_PASS);