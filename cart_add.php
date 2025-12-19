<?php
// Start the session to use $_SESSION variable
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includes database connection
require_once "config/db.php";

$product_id = filter_var($_REQUEST['id'] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT); // Default quantity is 1

// Ensure product ID is valid and quantity is at least 1
if (!$product_id || $quantity < 1) {
    // Redirect back to the store with an error (or to a specific product page)
    header("Location: store.php?status=error&message=Invalid product or quantity selection.");
    exit;
}

try {
    // 1. Fetch product details and current stock from the database
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = :id AND status = 'Available'");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: store.php?status=error&message=Product not available.");
        exit;
    }

    // Initialize the cart array in the session if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Calculate total quantity (current in cart + new addition)
    $current_cart_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
    $new_total_qty = $current_cart_qty + $quantity;

    // 2. Check stock availability (basic check)
    if ($new_total_qty > $product['stock']) {
        header("Location: product_detail.php?id={$product_id}&status=error&message=Not enough stock. You have {$current_cart_qty} in cart, and only {$product['stock']} are available.");
        exit;
    }

    // 3. Add or Update the item in the cart session
    if (isset($_SESSION['cart'][$product_id])) {
        // Item already in cart, update quantity
        $_SESSION['cart'][$product_id]['quantity'] = $new_total_qty;
        $message = "Added {$quantity} more of {$product['name']} to your cart.";
    } else {
        // New item, add it to the cart
        $_SESSION['cart'][$product_id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
        ];
        $message = "{$product['name']} added to your cart.";
    }

    // Redirect the user to the cart page to confirm the addition
    header("Location: cart.php?status=success&message=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    // Handle database error
    header("Location: store.php?status=error&message=A database error occurred.");
    exit;
}
?>