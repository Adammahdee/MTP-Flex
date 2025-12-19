<?php
// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includes database connection (needed for potential later stock checks)
require_once __DIR__ . "/../config/db.php"; 

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
$status_message = $_GET['message'] ?? '';
$status_type = $_GET['status'] ?? '';

// Calculate total items in cart for the header
$cart_item_count = 0;
if (!empty($cart)) {
    $cart_item_count = array_sum(array_column($cart, 'quantity'));
}

// Calculate total subtotal
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Handler for updating or removing items (via POST request from the table)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    
    foreach ($_POST['quantities'] as $id => $new_quantity) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $new_quantity = filter_var($new_quantity, FILTER_VALIDATE_INT);

        if ($id && $new_quantity !== false) {
            if ($new_quantity > 0) {
                // Update quantity (assuming you'd re-check stock here in a real app)
                $_SESSION['cart'][$id]['quantity'] = $new_quantity;
            } else {
                // Remove item if quantity is zero
                unset($_SESSION['cart'][$id]);
            }
        }
    }
    // Recalculate subtotal after updates
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Redirect to clear POST data and show status
    header("Location: cart.php?status=success&message=Cart updated successfully.");
    exit;
}

// Handler for clearing the entire cart
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    unset($_SESSION['cart']);
    header("Location: cart.php?status=success&message=Your shopping cart has been cleared.");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MTP Flex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --color-primary: #2563eb;
            --color-success: #10b981;
            --color-danger: #ef4444;
            --bg-light: #f4f6f9;
            --text-dark: #1f2937;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--bg-light); color: var(--text-dark); margin: 0; line-height: 1.6; padding-top: 60px; }
        .header { background-color: var(--color-primary); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; position: fixed; top: 0; width: 100%; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); z-index: 100; }
        .header h1 { margin: 0; font-size: 1.5rem; }
        .header a { color: white; text-decoration: none; margin-left: 20px; }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        h2 { border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }

        /* Alert Styling */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

        /* Cart Table Styling */
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .cart-table th, .cart-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .cart-table th { background-color: #f9fafb; font-weight: bold; }
        .cart-table td input[type="number"] { width: 60px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
        
        /* Summary & Buttons */
        .cart-summary { text-align: right; margin-bottom: 30px; }
        .cart-summary h3 { margin: 0; font-size: 1.5rem; color: var(--color-primary); }
        .cart-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-update { background-color: #ffc107; color: var(--text-dark); border: none; cursor: pointer; }
        .btn-update:hover { background-color: #e0a800; }
        .btn-checkout { background-color: var(--color-success); color: white; }
        .btn-checkout:hover { background-color: #059669; }
        .btn-clear { background-color: var(--color-danger); color: white; }
        .btn-clear:hover { background-color: #dc2626; }
        .btn-back { background-color: #ccc; color: var(--text-dark); }
        .btn-back:hover { background-color: #bbb; }
    </style>
</head>
<body>
    <header class="header">
        <h1>MTP Flex Store</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="store.php">Shop</a>
            <a href="cart.php" style="font-weight: bold;"><i class="fas fa-shopping-cart"></i> Cart (<?= $cart_item_count ?>)</a>
            <a href="../user/login.php">Login</a>
        </nav>
    </header>

    <div class="container">
        <h2>Your Shopping Cart</h2>

        <?php if ($status_message): ?>
            <div class="alert alert-<?= ($status_type == 'success' ? 'success' : 'error') ?>">
                <?= htmlspecialchars($status_message) ?>
            </div>
        <?php endif; ?>

        <?php if (count($cart) > 0): ?>
            <form method="POST" action="cart.php">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $id => $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td>₦<?= number_format($item['price'], 2) ?></td>
                                <td>
                                    <input type="number" name="quantities[<?= $id ?>]" value="<?= htmlspecialchars($item['quantity']) ?>" min="0">
                                </td>
                                <td>₦<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-summary">
                    <h3>Subtotal: ₦<?= number_format($subtotal, 2) ?></h3>
                </div>

                <div class="cart-actions">
                    <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                    <div class="action-group">
                        <button type="submit" name="update_cart" class="btn btn-update"><i class="fas fa-sync-alt"></i> Update Cart</button>
                        <a href="cart.php?action=clear" class="btn btn-clear" onclick="return confirm('Are you sure you want to clear your entire cart?');"><i class="fas fa-trash-alt"></i> Clear Cart</a>
                        <a href="checkout.php" class="btn btn-checkout"><i class="fas fa-money-check-alt"></i> Proceed to Checkout</a>
                    </div>
                </div>
            </form>

        <?php else: ?>
            <div class="alert alert-info" style="text-align: center;">
                Your shopping cart is currently empty.
                <p style="margin-top: 15px;"><a href="index.php" class="btn btn-primary" style="background-color: var(--color-primary); color: white;">Start Shopping</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>