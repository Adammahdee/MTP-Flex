<?php
/**
 * Centralized Database Connection Manager
 *
 * Manages database connections for different application roles (user vs. admin)
 * to enforce security and the Principle of Least Privilege.
 */

// --- Configuration ---
// In a production environment, these should be loaded from a .env file outside the web root.
define('DB_HOST', 'localhost');

// Set to true for development to see detailed errors, false for production.
define('DEBUG', true);

// --- Error Reporting ---
if (defined('DEBUG') && DEBUG) {
    // Development: Show all errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Production: Hide all errors
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}
define('DB_NAME', 'mtp_flex');
define('DB_CHARSET', 'utf8mb4');

// Credentials for the low-privilege public user
define('DB_USER_USER', 'root');
define('DB_USER_PASS', ''); // <-- Your root password, often blank in local development.

// Credentials for the high-privilege admin user
define('DB_ADMIN_USER', 'root');
define('DB_ADMIN_PASS', ''); // <-- Your root password, often blank in local development.

// --- Email Configuration ---
// Email address for admin notifications (e.g., new inquiries)
define('ADMIN_EMAIL', 'admin@example.com'); // IMPORTANT: Change this to your actual admin email

/**
 * Establishes a database connection using PDO.
 *
 * @param string $user The database username.
 * @param string $pass The database password.
 * @return PDO The PDO connection object.
 */
function get_pdo_connection(string $user, string $pass): PDO
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log("Database Connection Failed: " . $e->getMessage());
        http_response_code(500);
        die("A critical database error occurred. Please try again later.");
    }
}

// By default, scripts that include this file will get the low-privilege user connection.
// Admin scripts must explicitly request the admin connection.
$pdo = get_pdo_connection(DB_USER_USER, DB_USER_PASS);