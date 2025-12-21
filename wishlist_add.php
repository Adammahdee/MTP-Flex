<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in to add to wishlist
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=product_detail&id=" . ($_GET['id'] ?? ''));
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$product_id) {
    header("Location: store.php?status=error&message=Invalid product.");
    exit;
}

try {
    // Check if item is already in wishlist
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetch()) {
        // Already exists, redirect to wishlist page with a notice
        header("Location: wishlist.php?status=info&message=Item is already in your wishlist.");
        exit;
    }

    // Add item to wishlist
    $insert_stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $insert_stmt->execute([$user_id, $product_id]);

    header("Location: wishlist.php?status=success&message=Item added to your wishlist!");
    exit;

} catch (PDOException $e) {
    // Handle potential errors, like product not existing in products table (foreign key constraint)
    if ($e->getCode() === '42S02') { // Table doesn't exist, try to create it
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS wishlist (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_user_product (user_id, product_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            // Retry the insert
            $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$user_id, $product_id]);
            header("Location: wishlist.php?status=success&message=Item added to your wishlist!");
            exit;
        } catch (PDOException $ex) {
            header("Location: product_detail.php?id={$product_id}&status=error&message=Could not initialize wishlist.");
            exit;
        }
    }
    header("Location: product_detail.php?id={$product_id}&status=error&message=Could not add item to wishlist.");
    exit;
}
