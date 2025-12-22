<?php
// Centralized initialization
require_once 'init.php';

// --- PHP LOGIC ---
$products = [];
$error_message = null;
$success_message = null;

// Check for status messages from other pages (e.g., delete)
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success' && isset($_GET['message'])) {
        $success_message = htmlspecialchars($_GET['message']);
    } elseif ($_GET['status'] === 'error' && isset($_GET['message'])) {
        $error_message = htmlspecialchars($_GET['message']);
    }
}

// Helper function for status badges
function get_product_status_badge($status) {
    switch ($status) {
        case 'Available': return '<span class="badge success">Available</span>';
        case 'Out of Stock': return '<span class="badge warning">Out of Stock</span>';
        default: return '<span class="badge info">' . htmlspecialchars($status) . '</span>';
    }
}

try {
    // Schema Update: Ensure category_id exists and is a foreign key
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT NULL");
    } catch (PDOException $e) { /* Ignore */ }
    try {
        $pdo->exec("ALTER TABLE products ADD CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
    } catch (PDOException $e) { /* Ignore */ }

    // Fetch all products with their category names
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.name, p.price, p.stock, p.status, p.image_path,
            c.name AS category_name
        FROM 
            products p
        LEFT JOIN 
            categories c ON p.category_id = c.id
        ORDER BY 
            p.created_at DESC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "The 'products' table is missing. Please run the SQL creation script.";
}

?>

<?php require_once 'assets/header.php'; ?>
    <div class="page-header">
        <h1><i class="fas fa-box"></i> Product Management</h1>
    </div>

    <?php if ($success_message): ?>
        <div class="card" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid #16a34a; background: #dcfce7; color: #333;">
            <?= $success_message ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="card" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid #dc2626; background: #f8d7da; color: #333;">
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="row" style="justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; padding: 0 10px;">Product List (<?= count($products) ?>)</h3>
            <div style="padding: 0 10px;">
                <a href="product_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($product['image_path'] ?? '../assets/images/placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            </td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td>₦<?= number_format($product['price'], 2) ?></td>
                            <td><?= htmlspecialchars($product['stock']) ?></td>
                            <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                            <td><?= get_product_status_badge($product['status']) ?></td>
                            <td>
                                <a href="product_edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="product_delete.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">
                            No products found. Click "Add New Product" to get started.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php
require_once "assets/footer.php";
?>
