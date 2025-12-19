<?php
// Includes necessary files for authentication and database connection
require_once 'init.php'; // Handles session, auth, and admin DB connection

// The rest of the script now uses the secure $pdo connection from init.php
// Check if a category ID is provided in the URL
$category_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$category_id) {
    // If no ID is provided, redirect with an error message
    header("Location: categories.php?status=error&message=No category ID provided.");
    exit;
}

try {
    // 1. Fetch the category's name before deletion (for the success message)
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :id");
    $stmt->execute(['id' => $category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        $category_name = $category['name'];

        // 2. Delete the category from the database
        // NOTE: The foreign key constraint on products.category_id is set to 
        // ON DELETE SET NULL, meaning products will be safely uncategorized.
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute(['id' => $category_id]);

        // Success redirection
        header("Location: categories.php?status=success&message=Category '{$category_name}' deleted successfully. Products were uncategorized.");
        exit;

    } else {
        // Category not found
        header("Location: categories.php?status=error&message=Category not found.");
        exit;
    }

} catch (PDOException $e) {
    // Handle database errors
    $error_msg = "Database Error: Cannot delete category. (" . urlencode($e->getMessage()) . ")";
    header("Location: categories.php?status=error&message={$error_msg}");
    exit;
}
?>