<?php
// Centralized initialization
require_once 'init.php';
require_once 'assets/header.php';

$category = null;
$message = $message_type = '';

// Check if a category ID is provided in the URL
$category_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$category_id) {
    // If no ID is provided, redirect back to the category list
    header("Location: categories.php");
    exit;
}

try {
    // 1. Fetch Existing Category Data
    $stmt = $pdo->prepare("SELECT id, name, description FROM categories WHERE id = :id");
    $stmt->execute(['id' => $category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        $message = "Error: Category not found.";
        $message_type = 'danger';
        // Use a label to skip form processing if the category isn't found
        goto end_of_logic; 
    }
    
} catch (PDOException $e) {
    $message = "Database Error: Could not load category data. Ensure 'categories' table exists.";
    $message_type = 'danger';
    goto end_of_logic;
}

// 2. Handle Form Submission (Update Logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message_type !== 'danger') {
    
    // Sanitize and Validate Inputs
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $message = "Category Name is required.";
        $message_type = 'danger';
    } else {
        try {
            // Update category in the database
            $stmt = $pdo->prepare("
                UPDATE categories SET 
                    name = :name, 
                    description = :description
                WHERE id = :id
            ");
            
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'id' => $category_id
            ]);

            $message = "Category '{$name}' updated successfully!";
            $message_type = 'success';
            
            // Re-fetch the updated category data to refresh the form
            $stmt = $pdo->prepare("SELECT id, name, description FROM categories WHERE id = :id");
            $stmt->execute(['id' => $category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Check for unique constraint violation (Category name must be unique)
            if ($e->getCode() == '23000') {
                 $message = "Database Error: A category with the name '{$name}' already exists.";
            } else {
                 $message = "Database Error: Failed to update category. " . $e->getMessage();
            }
            $message_type = 'danger';
        }
    }
}

end_of_logic: // Target for the goto statement
?>

    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Category: <?= htmlspecialchars($category['name'] ?? 'N/A') ?></h1>
    </div>

    <?php if ($message): ?>
        <div class="card" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid <?= $message_type === 'success' ? '#16a34a' : ($message_type === 'danger' ? '#dc2626' : '#ffc107') ?>; background: <?= $message_type === 'success' ? '#dcfce7' : ($message_type === 'danger' ? '#f8d7da' : '#fffbe7') ?>; color: #333;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($category): ?>
        <div class="card">
            <form action="category_edit.php?id=<?= $category_id ?>" method="POST">
                
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($category['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description (Optional)</label>
                            <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($category['description']) ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-success" style="width: 100%;"><i class="fas fa-sync-alt"></i> Update Category</button>
                        </div>
                        <div class="form-group">
                            <a href="categories.php" class="btn info" style="width: 100%; text-align: center;"><i class="fas fa-arrow-left"></i> Back to Categories</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

<?php
require_once "assets/include/footer.php";
?>