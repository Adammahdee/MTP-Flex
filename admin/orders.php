<?php
require_once 'init.php';

$page_title = 'Manage Orders';

// Get filter status from URL
$filter_status = $_GET['status'] ?? 'all';

$orders = [];
$error = '';

try {
    $sql = "SELECT id, customer_name, total_amount, status, created_at FROM orders";
    $params = [];

    if ($filter_status !== 'all' && !empty($filter_status)) {
        $sql .= " WHERE status = :status";
        $params[':status'] = $filter_status;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database query failed: " . $e->getMessage();
}

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
?>

<?php require_once 'assets/header.php'; ?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-shopping-cart"></i> All Orders</h1>
</div>

<!-- Filter Controls -->
<div class="mb-3">
    <a href="orders.php" class="btn btn-sm <?= $filter_status == 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
    <a href="orders.php?status=Pending" class="btn btn-sm <?= $filter_status == 'Pending' ? 'btn-primary' : 'btn-outline-secondary' ?>">Pending</a>
    <a href="orders.php?status=Processing" class="btn btn-sm <?= $filter_status == 'Processing' ? 'btn-primary' : 'btn-outline-secondary' ?>">Processing</a>
    <a href="orders.php?status=Shipped" class="btn btn-sm <?= $filter_status == 'Shipped' ? 'btn-primary' : 'btn-outline-secondary' ?>">Shipped</a>
    <a href="orders.php?status=Completed" class="btn btn-sm <?= $filter_status == 'Completed' ? 'btn-primary' : 'btn-outline-secondary' ?>">Completed</a>
    <a href="orders.php?status=Cancelled" class="btn btn-sm <?= $filter_status == 'Cancelled' ? 'btn-primary' : 'btn-outline-secondary' ?>">Cancelled</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
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
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6" class="text-center">No orders found for this status.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td>₦<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= get_order_status_badge($order['status']) ?></td>
                                <td><?= date("M d, Y, g:i a", strtotime($order['created_at'])) ?></td>
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

<?php require_once __DIR__ . '/assets/footer.php'; ?>
