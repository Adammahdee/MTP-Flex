<?php
require_once 'init.php';

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

// --- Filters ---
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');

$orders = [];
$error = '';

try {
    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];

    if ($status_filter) {
        $sql .= " AND status = :status";
        $params['status'] = $status_filter;
    }
    if ($date_from) {
        $sql .= " AND DATE(created_at) >= :date_from";
        $params['date_from'] = $date_from;
    }
    if ($date_to) {
        $sql .= " AND DATE(created_at) <= :date_to";
        $params['date_to'] = $date_to;
    }
    if ($search) {
        $sql .= " AND (customer_name LIKE :search_name OR id = :search_id)";
        $params['search_name'] = "%$search%";
        $params['search_id'] = $search;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$page_title = 'Manage Orders';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-shopping-cart"></i> Manage Orders</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Order ID or Customer Name" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>Orders List (<?= count($orders) ?>)</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6" class="text-center">No orders found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['id']) ?></td>
                                <td>
                                    <?= htmlspecialchars($order['customer_name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                </td>
                                <td><?= date("M d, Y H:i", strtotime($order['created_at'])) ?></td>
                                <td>₦<?= number_format($order['total_amount'], 2) ?></td>
                                <td><?= get_order_status_badge($order['status']) ?></td>
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
