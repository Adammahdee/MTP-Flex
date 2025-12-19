<?php
// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Includes database connection
require_once __DIR__ . "/../config/db.php"; 

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
$error = '';
$user_id = $_SESSION['user_id'] ?? null;
$logged_in_user_name = '';
$logged_in_user_email = '';

if ($user_id) {
    $user_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = :user_id");
    $user_stmt->execute(['user_id' => $user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $logged_in_user_name = $user_data['name'];
        $logged_in_user_email = $user_data['email'];
    }
}

// Calculate initial subtotal
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Redirect if cart is empty
if (count($cart) === 0) {
    header("Location: cart.php?status=warning&message=Your cart is empty. Please add items before checking out.");
    exit;
}

// --- PHP LOGIC: Handle Order Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Sanitize and Validate Customer Data
    $name = trim($_POST['customer_name'] ?? '');
    $email = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $address = trim($_POST['shipping_address'] ?? '');

    if (empty($name) || !$email || empty($address)) {
        $error = "Please fill in all required fields (Name, Email, and Shipping Address).";
    } else {
        // Start Transaction for data integrity
        $pdo->beginTransaction();

        try {
            // 2. Create the main entry in the 'orders' table
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, customer_name, customer_email, shipping_address, total_amount, status)
                VALUES (:user_id, :name, :email, :address, :total, 'Pending')
            ");
            
            $stmt->execute([
                'user_id' => $user_id,
                'name' => $name,
                'email' => $email,
                'address' => $address,
                'total' => $subtotal
            ]);

            $order_id = $pdo->lastInsertId();

            // 3. Insert items into the 'order_items' table and update product stock
            $stock_error = false;
            foreach ($cart as $product_id => $item) {
                
                // Re-check current stock to prevent race conditions (simple approach)
                $stock_stmt = $pdo->prepare("SELECT stock FROM products WHERE id = :id");
                $stock_stmt->execute(['id' => $product_id]);
                $current_stock = $stock_stmt->fetchColumn();

                if ($item['quantity'] > $current_stock) {
                    $stock_error = true;
                    $error = "Stock Error: Not enough stock for {$item['name']}. Available: {$current_stock}.";
                    break; 
                }

                // Insert item detail
                $item_stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price_at_order)
                    VALUES (:order_id, :product_id, :quantity, :price)
                ");
                $item_stmt->execute([
                    'order_id' => $order_id,
                    'product_id' => $product_id,
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);

                // Update product stock (decrement)
                $update_stmt = $pdo->prepare("
                    UPDATE products SET stock = stock - :quantity WHERE id = :id
                ");
                $update_stmt->execute([
                    'quantity' => $item['quantity'],
                    'id' => $product_id
                ]);
            }

            // 4. Commit or Rollback
            if ($stock_error) {
                $pdo->rollBack();
            } else {
                $pdo->commit();
                
                // 5. Success: Clear cart and redirect
                unset($_SESSION['cart']);
                header("Location: checkout_success.php?order_id={$order_id}");
                exit;
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "System Error: Could not finalize order. Please try again. (" . $e->getMessage() . ")";
        }
    }
}
?>

<?php 
$page_title = 'Checkout - MTP Flex Store';
$cart_item_count = count($cart); // Needed for the header
require_once __DIR__ . '/../user/user_header.php'; 
?>
    <style>
        .container {
            padding-top: 2rem;
            padding-bottom: 2rem;
            display: flex;
            gap: 30px;
        }
        .checkout-form { flex: 2; }
        .order-summary { flex: 1; background-color: #f9fafb; padding: 20px; border-radius: 8px; height: fit-content;}
        h2 { border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.8rem; }
        
        /* Form & Input Styling */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-secondary); }
        .form-control { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; box-sizing: border-box; }
        
        /* Error Alert */
        .alert-error { background-color: #fee2e2; color: #991b1b; padding: 15px; border: 1px solid #fecaca; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        
        /* Summary Table */
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-table td { padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
        .summary-table .total-row td { font-weight: bold; border-top: 2px solid var(--border-color); font-size: 1.1rem; color: var(--primary-color); }

        /* Button */
        .btn-place-order { 
            width: 100%; 
            padding: 12px; 
            background-color: #10b981; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            font-size: 1.1rem; 
            cursor: pointer; 
            transition: background-color 0.3s;
        }
        .btn-place-order:hover { background-color: #059669; }
    </style>

    <div class="container">
        <div class="checkout-form">
            <h2>Shipping Information</h2>
            
            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="checkout.php">
                
                <h3>Contact Details</h3>
                <div class="form-group">
                    <label for="customer_name">Full Name <span style="color: red;">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" value="<?= htmlspecialchars($_POST['customer_name'] ?? $logged_in_user_name) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_email">Email Address <span style="color: red;">*</span></label>
                    <input type="email" id="customer_email" name="customer_email" class="form-control" value="<?= htmlspecialchars($_POST['customer_email'] ?? $logged_in_user_email) ?>" required>
                </div>

                <h3>Delivery Address</h3>
                <div class="form-group">
                    <label for="shipping_address">Shipping Address <span style="color: red;">*</span></label>
                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="4" required><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <p style="margin: 5px 0; font-weight: bold;">Cash on Delivery (COD) - Placeholder</p>
                </div>
                
                <button type="submit" class="btn-place-order"><i class="fas fa-check-circle"></i> Place Order</button>
            </form>
        </div>

        <div class="order-summary">
            <h3>Your Order (<?= count($cart) ?> Items)</h3>
            <table class="summary-table">
                <tbody>
                    <?php foreach ($cart as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?> x <?= htmlspecialchars($item['quantity']) ?></td>
                            <td style="text-align: right;">₦<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td style="text-align: right;">₦<?= number_format($subtotal, 2) ?></td>
                    </tr>
                </tbody>
            </table>
            <a href="cart.php" style="display: block; text-align: center; color: var(--accent-color); text-decoration: none; font-weight: bold;">
                <i class="fas fa-arrow-left"></i> Edit Cart
            </a>
        </div>
    </div>
<?php require_once __DIR__ . '/../assets/store_footer.php'; ?>