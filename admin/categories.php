<?php
require_once 'init.php';

$error = '';
$success = '';

// --- Handle POST requests ---

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

    if ($category_id) {
        // 1. Restrict deletion: Check for active products
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $product_count = $stmt->fetchColumn();

        if ($product_count > 0) {
            $error = "Cannot delete category. Reassign " . $product_count . " product(s) to another category first.";
        } else {
            // 2. Proceed with deletion
            $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($delete_stmt->execute([$category_id])) {
                $success = "Category deleted successfully.";
                // Audit Note: A full audit trail would log this deletion event to a separate 'audit_logs' table.
            } else {
                $error = "Failed to delete category.";
            }
        }
    }
}

// Handle ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) : null;

    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = "A category with that name already exists.";
        } else {
            $insert_stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
            if ($insert_stmt->execute([$name, $description, $parent_id])) {
                $success = "Category added successfully.";
            } else {
                $error = "Failed to add category.";
            }
        }
    }
}

// --- Fetch data for display ---
$categories = [];
try {
    $stmt = $pdo->query("
        SELECT c.*, p.name as parent_name, (SELECT COUNT(*) FROM products prod WHERE prod.category_id = c.id) as product_count 
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

$page_title = 'Manage Categories';
require_once 'assets/header.php';
?>

<div class="page-header"><h1><i class="fas fa-tags"></i> Manage Categories</h1></div>

<?php if ($error) echo "<div class='alert alert-danger'>".htmlspecialchars($error)."</div>"; ?>
<?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><strong>Existing Categories</strong></div>
            <div class="card-body">
                <table class="table table-hover table-sm">
                    <thead><tr><th>ID</th><th>Name</th><th>Parent</th><th>Description</th><th>Products</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= $cat['id'] ?></td>
                            <td><?= htmlspecialchars($cat['name']) ?></td>
                            <td><?= htmlspecialchars($cat['parent_name'] ?? '-') ?></td>
                            <td>
                                <?php 
                                    $desc = htmlspecialchars($cat['description'] ?? '');
                                    echo strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : ($desc ?: '<em class="text-muted small">N/A</em>');
                                ?>
                            </td>
                            <td><?= $cat['product_count'] ?></td>
                            <td>
                                <a href="category_edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this category? This cannot be undone.');">
                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                    <button type="submit" name="delete_category" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Add New Category</strong></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category</label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($categories as $p_cat): ?>
                                <option value="<?= $p_cat['id'] ?>"><?= htmlspecialchars($p_cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>