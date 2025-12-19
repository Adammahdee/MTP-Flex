<?php
// Includes necessary files for authentication and database connection
require_once 'init.php'; // Handles session, auth, and admin DB connection

// The rest of the script now uses the secure $pdo connection from init.php
// Check if a product ID is provided in the URL
$product_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$product_id) {
    // If no ID is provided, redirect with an error message
    header("Location: products.php?status=error&message=No product ID provided.");
    exit;
}

try {
    // 1. Fetch the product's name and image path before deletion
    $stmt = $pdo->prepare("SELECT name, image_path FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $product_name = $product['name'];
        $image_path = $product['image_path'];

        // 2. Delete the product from the database
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute(['id' => $product_id]);

        // 3. Delete the associated image file from the server
        if ($image_path && file_exists("../" . $image_path)) {
            unlink("../" . $image_path);
        }

        // Success redirection
        header("Location: products.php?status=success&message=Product '{$product_name}' deleted successfully.");
        exit;

    } else {
        // Product not found
        header("Location: products.php?status=error&message=Product not found.");
        exit;
    }

} catch (PDOException $e) {
    // Handle database errors (e.g., integrity constraint issues if it's tied to an order item)
    $error_msg = "Database Error: Cannot delete product. It may be linked to existing orders. (" . urlencode($e->getMessage()) . ")";
    header("Location: products.php?status=error&message={$error_msg}");
    exit;
}
?>