<?php
// Centralized initialization and security check
require_once 'init.php';

$name = $description = '';
$message = $message_type = '';

// --- PHP LOGIC: Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and Validate Inputs
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $message = "Category Name is required.";
        $message_type = 'danger';
    } else {
        try {
            // Insert category into the database using the admin PDO connection
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, description)
                VALUES (:name, :description)
            ");
            
            $stmt->execute([
                'name' => $name,
                'description' => $description
            ]);

            $message = "Category '{$name}' added successfully!";
            $message_type = 'success';
            
            // Clear form fields on success
            $name = $description = '';

        } catch (PDOException $e) {
            // Check for unique constraint violation (Category name must be unique)
            if ($e->getCode() == '23000') {
                 $message = "Database Error: A category with the name '{$name}' already exists.";
            } else {
                 $message = "Database Error: Failed to add category. " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    }
}

$page_title = 'Add New Category';
require_once 'assets/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tags"></i> Add New Category</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="category_add.php" method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Category Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Category</button>
            <a href="categories.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Categories</a>
        </form>
    </div>
</div>

<?php
require_once 'assets/footer.php';
?>