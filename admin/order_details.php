<?php
require_once 'init.php';

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$error = '';
$success = '';
$order = null;
$order_items = [];

if (!$order_id) {
    header("Location: orders.php");
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $valid_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

    if (in_array($new_status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $new_status, 'id' => $order_id]);
            $success = "Order status updated to '{$new_status}'.";
        } catch (PDOException $e) {
            $error = "Failed to update status: " . $e->getMessage();
        }
    } else {
        $error = "Invalid status selected.";
    }
}

// Fetch order details
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute(['id' => $order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $error = "Order not found.";
    } else {
        // Fetch order items
        $items_stmt = $pdo->prepare("
            SELECT oi.*, p.name AS product_name 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $items_stmt->execute(['order_id' => $order_id]);
        $order_items = $items_stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$page_title = "Order Details #" . htmlspecialchars($order_id);
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-file-invoice-dollar"></i> Order Details #<?= htmlspecialchars($order_id) ?></h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>

<?php if ($order): ?>
<div class="row">
    <!-- Order Details Column -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Items in this Order</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price at Order</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td>₦<?= number_format($item['price_at_order'], 2) ?></td>
                            <td>₦<?= number_format($item['price_at_order'] * $item['quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <h4>Total: ₦<?= number_format($order['total_amount'], 2) ?></h4>
            </div>
        </div>
    </div>

    <!-- Customer & Status Column -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h5>Customer Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong><br><?= htmlspecialchars($order['customer_name']) ?></p>
                <p><strong>Email:</strong><br><?= htmlspecialchars($order['customer_email']) ?></p>
                <p><strong>Shipping Address:</strong><br><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Update Status</h5>
            </div>
            <div class="card-body">
                <form action="order_details.php?id=<?= $order_id ?>" method="POST">
                    <div class="mb-3">
                        <label for="status" class="form-label">Order Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php 
                                $statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
                                foreach ($statuses as $status): 
                            ?>
                                <option value="<?= $status ?>" <?= ($order['status'] == $status) ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <p>The requested order could not be found.</p>
<?php endif; ?>

<div class="mt-4">
    <a href="orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to All Orders</a>
</div>

<?php require_once 'assets/footer.php'; ?>
