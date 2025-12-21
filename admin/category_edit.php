<?php
require_once 'init.php';

$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$category_id) {
    header("Location: categories.php");
    exit;
}

// --- Schema Migration ---
// Ensure all required columns exist before any other logic runs.
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (PDOException $e) { /* Ignore if exists */ }
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch (PDOException $e) { /* Ignore if exists */ }
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN updated_by INT NULL");
} catch (PDOException $e) { /* Ignore if column exists */ }
try {
    $pdo->exec("ALTER TABLE categories ADD CONSTRAINT fk_cat_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL");
} catch (PDOException $e) { /* Ignore if constraint exists */ }
// --- End Schema Migration ---
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN parent_id INT NULL, ADD CONSTRAINT fk_parent_category FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE");
} catch (PDOException $e) { /* Ignore if column/key exists */ }


$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: null;

    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } elseif ($parent_id == $category_id) {
        $error = "A category cannot be its own parent.";
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM categories WHERE name = ? AND parent_id <=> ? AND id != ?");
        $stmt->execute([$name, $parent_id, $category_id]);
        if ($stmt->fetch()) {
            $error = "Another category with that name already exists under the same parent.";
        } else {
            $update_stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, parent_id = ?, updated_by = ? WHERE id = ?");
            if ($update_stmt->execute([$name, $description, $parent_id, $_SESSION['user_id'], $category_id])) {
                $success = "Category updated successfully.";
            } else {
                $error = "Failed to update category.";
            }
        }
    }
}

// Fetch all categories for parent dropdown and tree building
$all_categories_stmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id ASC, name ASC");
$all_categories = $all_categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$category_tree = [];
$category_map = [];
foreach ($all_categories as $cat) { $category_map[$cat['id']] = $cat; $category_map[$cat['id']]['children'] = []; }
foreach ($category_map as $id => &$cat) {
    if ($cat['parent_id']) { $category_map[$cat['parent_id']]['children'][] =& $cat; } else { $category_tree[] =& $cat; }
}

$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.description, c.parent_id, c.created_at, c.updated_at, u.name as updated_by_name
    FROM categories c
    LEFT JOIN users u ON c.updated_by = u.id
    WHERE c.id = ?
");
$stmt->execute([$category_id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("Location: categories.php");
    exit;
}

// Recursive function to display categories in a dropdown
function display_categories_options($categories, $level = 0, $selected_id = null, $disabled_id = null) {
    $indent = str_repeat('&nbsp;&nbsp;', $level);
    foreach ($categories as $cat) {
        $selected = ($cat['id'] == $selected_id) ? 'selected' : '';
        $disabled = ($cat['id'] == $disabled_id) ? 'disabled' : ''; // Prevent selecting self
        echo '<option value="' . $cat['id'] . '" ' . $selected . ' ' . $disabled . '>' . $indent . htmlspecialchars($cat['name']) . '</option>';
        if (!empty($cat['children'])) { display_categories_options($cat['children'], $level + 1, $selected_id, $disabled_id); }
    }
}

$page_title = 'Edit Category';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> Edit Category</h1>
    <a href="categories.php" class="btn btn-outline-secondary">Back to Categories</a>
</div>

<?php if ($error) echo "<div class='alert alert-danger'>".htmlspecialchars($error)."</div>"; ?>
<?php if ($success) echo "<div class='alert alert-success'>".htmlspecialchars($success)."</div>"; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Category Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="parent_id" class="form-label">Parent Category</label>
                <select class="form-select" id="parent_id" name="parent_id">
                    <option value="">-- None (Top Level) --</option>
                    <?php
                    // Pass the current category ID to disable it in the options
                    display_categories_options($category_tree, 0, $category['parent_id'], $category['id']);
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
    <div class="card-footer text-muted">
        <small>
            Created on: <?= date('M j, Y, g:i a', strtotime($category['created_at'])) ?>.
            <?php if ($category['updated_by_name']): ?>
                Last updated on: <?= date('M j, Y, g:i a', strtotime($category['updated_at'])) ?> by <?= htmlspecialchars($category['updated_by_name']) ?>.
            <?php else: ?>
                Last updated on: <?= date('M j, Y, g:i a', strtotime($category['updated_at'])) ?>.
            <?php endif; ?>
        </small>
    </div>
</div>

<?php require_once 'assets/footer.php'; ?>