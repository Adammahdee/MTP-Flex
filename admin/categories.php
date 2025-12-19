<?php
// Centralized initialization
require_once 'init.php';

// --- PHP LOGIC: Fetch Categories ---
$categories = [];
$error_message = null;

try {
    // Attempt to fetch all categories. Assumes 'categories' table exists.
    $stmt = $pdo->prepare("
        SELECT 
            c.id, c.name, c.description,
            (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
        FROM 
            categories c
        ORDER BY 
            c.name ASC
    ");
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If the table does not exist
    $error_message = "The 'categories' table is missing. Please run the SQL creation script.";
}

?>

<?php require_once 'assets/header.php'; ?>
    <div class="page-header">
        <h1><i class="fas fa-tags"></i> Category Management</h1>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="card" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px;">
            <p><strong>Database Error:</strong> <?= $error_message ?></p>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="row" style="justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; padding: 0 10px;">Category List (<?= count($categories) ?>)</h3>
            <div style="padding: 0 10px;">
                <a href="category_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Category</a>
            </div>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Product Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($category['id']) ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars($category['description'] ?? 'No description provided.') ?></td>
                            <td><span class="badge info"><?= htmlspecialchars($category['product_count']) ?> Products</span></td>
                            <td>
                                <a href="category_edit.php?id=<?= $category['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                <a href="category_delete.php?id=<?= $category['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category? (Products in this category will be uncategorized)');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">
                            <?php if (!$error_message): ?>
                                No categories found.
                            <?php else: ?>
                                Cannot display categories list due to database error above.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php
// Include the structural closing components (Footer)
require_once "assets/footer.php";
?>