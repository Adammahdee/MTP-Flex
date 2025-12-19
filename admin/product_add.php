<?php
// Centralized initialization
require_once 'init.php';
require_once 'assets/header.php';

$name = $description = $price = $stock = $category_id = $status = $image_path = '';
$categories = [];
$message = $message_type = '';

try {
    // 1. Fetch Categories for the dropdown
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $message = "Database Error: Could not load categories. Ensure 'categories' table exists.";
    $message_type = 'danger';
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $message_type !== 'danger') {
    
    // Sanitize and Validate Inputs
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? 0.0, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);
    $category_id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
    $status = trim($_POST['status'] ?? 'Available');
    // Basic validation, add more robust checks in a real app
    
    if (empty($name) || $price === false || $stock === false) {
        $message = "Please fill out all required fields correctly.";
        $message_type = 'danger';
    } else {
        // --- Image URL Handling ---
        $image_path = filter_var(trim($_POST['image_url'] ?? ''), FILTER_VALIDATE_URL);
        if (empty($_POST['image_url'])) {
            $image_path = null; // Allow empty URL
        } elseif ($image_path === false) {
            $message = "The provided image URL is not valid.";
            $message_type = 'danger';
        }
        
        // Only proceed to database if there was no upload error
        if ($message_type !== 'danger') {
            try {
                // Insert product into the database
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, price, stock, category_id, status, image_path)
                    VALUES (:name, :description, :price, :stock, :category_id, :status, :image_path)
                ");
                
                $stmt->execute([
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'stock' => $stock,
                    'category_id' => $category_id,
                    'status' => $status,
                    'image_path' => $image_path
                ]);

                $message = "Product '{$name}' added successfully!";
                $message_type = 'success';
                
                // Clear form fields on success
                $name = $description = $price = $stock = $category_id = $status = $image_path = '';

            } catch (PDOException $e) {
                $message = "Database Error: Failed to add product. " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}
?>

    <div class="page-header">
        <h1><i class="fas fa-plus-circle"></i> Add New Product</h1>
    </div>

    <?php if ($message): ?>
        <div class="card" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid <?= $message_type === 'success' ? '#16a34a' : ($message_type === 'warning' ? '#ffc107' : '#dc2626') ?>; background: <?= $message_type === 'success' ? '#dcfce7' : ($message_type === 'warning' ? '#fffbe7' : '#f8d7da') ?>; color: #333;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form action="product_add.php" method="POST">
            
            <div class="row">
                <div class="col-md-7">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="6"><?= htmlspecialchars($description) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-5">
                             <div class="form-group">
                                <label for="price">Price (₦) *</label>
                                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($price ?: '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-7">
                             <div class="form-group">
                                <label for="stock">Stock Quantity *</label>
                                <input type="number" id="stock" name="stock" class="form-control" min="0" value="<?= htmlspecialchars($stock ?: '') ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                     <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option 
                                    value="<?= $cat['id'] ?>" 
                                    <?= ($category_id == $cat['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($categories)): ?>
                            <small class="form-text text-muted">
                                No categories found. <a href="category_add.php">Add a new category</a> first.
                            </small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Available" <?= ($status === 'Available' || empty($status)) ? 'selected' : '' ?>>Available</option>
                            <option value="Out of Stock" <?= ($status === 'Out of Stock') ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="image_url">Product Image URL</label>
                        <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($image_path ?? '') ?>">
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-save"></i> Save Product</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php
require_once "assets/include/footer.php";
?>