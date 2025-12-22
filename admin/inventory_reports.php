<?php
require_once 'init.php';

$low_stock_threshold = 10;
$alerts = [];

try {
    // 1. Check Standard Products (Low Stock)
    $stmt = $pdo->prepare("
        SELECT id, name, stock, 'Product' as type, NULL as variant_info 
        FROM products 
        WHERE stock <= ?
    ");
    $stmt->execute([$low_stock_threshold]);
    $alerts = array_merge($alerts, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // 2. Check Variants (Low Stock)
    // Joins variants with attributes to show "T-Shirt (Size: M, Color: Red)"
    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.name, 
            v.stock, 
            'Variant' as type,
            GROUP_CONCAT(CONCAT(va.attribute_name, ': ', va.attribute_value) SEPARATOR ', ') as variant_info
        FROM product_variants v
        JOIN products p ON v.product_id = p.id
        LEFT JOIN variant_attributes va ON v.id = va.variant_id
        WHERE v.stock <= ?
        GROUP BY v.id
    ");
    $stmt->execute([$low_stock_threshold]);
    $alerts = array_merge($alerts, $stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$page_title = 'Inventory Alerts';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h1>
    <p class="text-muted">Items with stock level <strong><?= $low_stock_threshold ?></strong> or lower.</p>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($alerts)): ?>
            <div class="alert alert-success">All inventory levels are healthy!</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Product Name</th>
                        <th>Variant Details</th>
                        <th>Current Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $item): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $item['type'] === 'Variant' ? 'info' : 'secondary' ?>">
                                    <?= $item['type'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= $item['variant_info'] ? htmlspecialchars($item['variant_info']) : '<em class="text-muted">-</em>' ?></td>
                            <td class="text-danger font-weight-bold"><?= $item['stock'] ?></td>
                            <td>
                                <a href="product_edit.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">Restock</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'assets/footer.php';
?>
