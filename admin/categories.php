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

        // Restrict deletion: Check for sub-categories
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$category_id]);
        $child_count = $stmt->fetchColumn();

        if ($product_count > 0) {
            $error = "Cannot delete category. Reassign " . $product_count . " product(s) to another category first.";
        } elseif ($child_count > 0) {
            $error = "Cannot delete category. It has " . $child_count . " sub-categories. Please delete or reassign them first.";
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
    $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: null;
    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE name = ? AND parent_id <=> ?");
        $stmt->execute([$name, $parent_id]);
        if ($stmt->fetch()) {
            $error = "A category with that name already exists under the same parent.";
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
$flat_categories = [];
try {
    // Ensure table and audit columns exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, description TEXT NULL, parent_id INT NULL) ENGINE=InnoDB;");
    
    // Drop old unique index on 'name' and add a composite unique index to allow same names under different parents
    try {
        $pdo->exec("ALTER TABLE categories DROP INDEX name");
    } catch (PDOException $e) { /* Ignore if index 'name' doesn't exist */ }
    try {
        $pdo->exec("ALTER TABLE categories ADD UNIQUE `uq_category_name_parent` (name, parent_id)");
    } catch (PDOException $e) { /* Ignore if index already exists */ }
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN description TEXT NULL");
    } catch (PDOException $e) { /* Ignore if column exists */ }
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN parent_id INT NULL");
    } catch (PDOException $e) { /* Ignore if column exists */ }
    try {
        $pdo->exec("ALTER TABLE categories ADD CONSTRAINT fk_parent_category FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE");
    } catch (PDOException $e) { /* Ignore if column/key exists */ }
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    } catch (PDOException $e) { /* Ignore if column exists */ }
    try {
        $pdo->exec("ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    } catch (PDOException $e) { /* Ignore if column exists */ }

    $stmt = $pdo->query("
        SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count 
        FROM categories c 
        ORDER BY c.parent_id ASC, c.name ASC
    ");
    $flat_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Build a hierarchical tree from the flat list
$category_tree = [];
$category_map = [];
foreach ($flat_categories as $category) {
    $category_map[$category['id']] = $category;
    $category_map[$category['id']]['children'] = [];
}
foreach ($category_map as $id => &$category) {
    if ($category['parent_id']) {
        $category_map[$category['parent_id']]['children'][] =& $category;
    } else {
        $category_tree[] =& $category;
    }
}

// Recursive function to display categories in the table
function display_categories_rows($categories, $level = 0) {
    $indent = str_repeat('&mdash; ', $level);
    foreach ($categories as $cat) {
        echo '<tr>';
        echo '<td>' . $indent . htmlspecialchars($cat['name']) . '</td>';
        echo '<td>';
        $desc = htmlspecialchars($cat['description'] ?? '');
        echo strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : ($desc ?: '<em class="text-muted small">N/A</em>');
        echo '</td>';
        echo '<td>' . $cat['product_count'] . '</td>';
        echo '<td>';
        echo '<a href="category_edit.php?id=' . $cat['id'] . '" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i> Edit</a>';
        echo '<form method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this category? This cannot be undone.\');">';
        echo '<input type="hidden" name="category_id" value="' . $cat['id'] . '">';
        echo '<button type="submit" name="delete_category" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>';
        echo '</form>';
        echo '</td>';
        echo '</tr>';
        if (!empty($cat['children'])) {
            display_categories_rows($cat['children'], $level + 1);
        }
    }
}

// Recursive function to display categories in a dropdown
function display_categories_options($categories, $level = 0, $selected_id = null) {
    $indent = str_repeat('&nbsp;&nbsp;', $level);
    foreach ($categories as $cat) {
        $selected = ($cat['id'] == $selected_id) ? 'selected' : '';
        echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . $indent . htmlspecialchars($cat['name']) . '</option>';
        if (!empty($cat['children'])) {
            display_categories_options($cat['children'], $level + 1, $selected_id);
        }
    }
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
                    <thead><tr><th>Name</th><th>Description</th><th>Products</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        display_categories_rows($category_tree);
                        ?>
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
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">-- None (Top Level) --</option>
                            <?php
                            display_categories_options($category_tree);
                            ?>
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