<?php
require_once 'init.php';

// Initialize stats array
$stats = [
    'products' => 0,
    'categories' => 0,
    'orders' => 0,
    'revenue' => 0,
    'users' => 0,
];
$recent_orders = [];
$error = '';

try {
    // Fetch statistics
    $stats['products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['categories'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $stats['orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['revenue'] = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Completed'")->fetchColumn() ?? 0;
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();

    // Fetch Recent Orders
    $stmt = $pdo->query("
        SELECT o.id, o.customer_name, o.total_amount, o.status, o.created_at 
        FROM orders o 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Helper function for status badges
function get_order_status_badge($status) {
    $status_map = [
        'Completed' => 'success',
        'Pending' => 'warning',
        'Processing' => 'info',
        'Shipped' => 'primary',
        'Cancelled' => 'danger',
    ];
    $class = $status_map[$status] ?? 'secondary';
    return "<span class=\"badge bg-{$class}\">" . htmlspecialchars($status) . "</span>";
}

$page_title = 'Dashboard';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row mb-3" style="--bs-gutter-x: 1.5rem; --bs-gutter-y: 1.5rem;">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-secondary">Total Revenue</h5>
                <p class="card-text fs-2 fw-bold">₦<?= number_format($stats['revenue'], 2) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-secondary">Total Orders</h5>
                <p class="card-text fs-2 fw-bold"><?= number_format($stats['orders']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-secondary">Total Products</h5>
                <p class="card-text fs-2 fw-bold"><?= number_format($stats['products']) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-secondary">Total Users</h5>
                <p class="card-text fs-2 fw-bold"><?= number_format($stats['users']) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Recent Orders</h5>
        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_orders)): ?>
                        <tr><td colspan="6" class="text-center">No recent orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td>₦<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= get_order_status_badge($order['status']) ?></td>
                                <td><?= date("M d, Y", strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>