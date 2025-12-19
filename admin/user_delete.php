<?php
// Includes necessary files for authentication and database connection
require_once "auth.php"; // Checks login and admin status
require_once "../config/db.php"; // Database connection

// Check if a user ID is provided in the URL
$user_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$current_user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: users.php?status=error&message=No user ID provided.");
    exit;
}

// Prevent an admin from deleting their own currently logged-in account
if ($user_id == $current_user_id) {
    header("Location: users.php?status=error&message=Cannot delete your own active administrator account.");
    exit;
}

try {
    // 1. Fetch the user's name before deletion (for the success message)
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_name = $user['name'];

        // 2. Delete the user from the database
        // NOTE: The user_id on the orders table is set to ON DELETE SET NULL, 
        // preserving order history while removing the user record.
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);

        // Success redirection
        header("Location: users.php?status=success&message=User '{$user_name}' deleted successfully.");
        exit;

    } else {
        // User not found
        header("Location: users.php?status=error&message=User not found.");
        exit;
    }

} catch (PDOException $e) {
    // Handle database errors
    $error_msg = "Database Error: Cannot delete user. Please check database constraints. (" . urlencode($e->getMessage()) . ")";
    header("Location: users.php?status=error&message={$error_msg}");
    exit;
}
?>