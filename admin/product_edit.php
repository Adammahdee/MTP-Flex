<?php
// Centralized initialization
require_once 'init.php';
require_once 'assets/header.php';

$product = null;
$categories = [];
$message = $message_type = '';

// Check if a product ID is provided in the URL
$product_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$product_id) {
    // If no ID is provided, redirect back to the product list
    header("Location: products.php");
    exit;
}

try {
    // 1. Fetch Categories for the dropdown
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Existing Product Data
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $message = "Error: Product not found.";
        $message_type = 'danger';
    } else {
        // 3. Handle Form Submission (Update Logic)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Sanitize and Validate Inputs
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = filter_var($_POST['price'] ?? 0.0, FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);
            $category_id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
            $status = trim($_POST['status'] ?? 'Available');
            $old_image_path = $product['image_path']; // Current image path

            if (empty($name) || $price === false || $stock === false) {
                $message = "Please fill out all required fields correctly.";
                $message_type = 'danger';
            } else {
                // --- Image URL Handling ---
                $new_image_path = filter_var(trim($_POST['image_url'] ?? ''), FILTER_VALIDATE_URL);
                if (empty($_POST['image_url'])) {
                    $new_image_path = null; // Allow empty URL
                } elseif ($new_image_path === false) {
                    $message = "The provided image URL is not valid.";
                    $message_type = 'danger';
                    $new_image_path = $old_image_path; // Keep old path on error
                }
                
                // Only proceed to database if there was no upload error
                if ($message_type !== 'danger') {
                    try {
                        // Update product in the database
                        $stmt = $pdo->prepare("
                            UPDATE products SET 
                                name = :name, 
                                description = :description, 
                                price = :price, 
                                stock = :stock, 
                                category_id = :category_id, 
                                status = :status, 
                                image_path = :image_path
                            WHERE id = :id
                        ");
                        
                        $stmt->execute([
                            'name' => $name,
                            'description' => $description,
                            'price' => $price,
                            'stock' => $stock,
                            'category_id' => $category_id,
                            'status' => $status,
                            'image_path' => $new_image_path,
                            'id' => $product_id
                        ]);

                        // Set success message only if no warning was issued before
                        if ($message_type !== 'warning') {
                            $message = "Product '{$name}' updated successfully!";
                            $message_type = 'success';
                        }
                        
                        // Re-fetch the updated product data to refresh the form
                        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
                        $stmt->execute(['id' => $product_id]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);

                    } catch (PDOException $e) {
                        $message = "Database Error: Failed to update product. " . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
            }
        }
    }
    
} catch (PDOException $e) {
    $message = "Database Error: Could not load data. Ensure tables ('products', 'categories') exist.";
    $message_type = 'danger';
}
?>

    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Edit Product: <?= htmlspecialchars($product['name'] ?? 'N/A') ?></h1>
    </div>

    <?php if ($message): ?>
        <div class="card" style="margin-bottom: 20px; padding: 15px; border-left: 5px solid <?= $message_type === 'success' ? '#16a34a' : ($message_type === 'warning' ? '#ffc107' : '#dc2626') ?>; background: <?= $message_type === 'success' ? '#dcfce7' : ($message_type === 'warning' ? '#fffbe7' : '#f8d7da') ?>; color: #333;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($product): ?>
        <div class="card">
            <form action="product_edit.php?id=<?= $product_id ?>" method="POST">
                
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group">
                            <label for="name">Product Name *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="6"><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                 <div class="form-group">
                                    <label for="price">Price (₦) *</label>
                                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($product['price']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-7">
                                 <div class="form-group">
                                    <label for="stock">Stock Quantity *</label>
                                    <input type="number" id="stock" name="stock" class="form-control" min="0" value="<?= htmlspecialchars($product['stock']) ?>" required>
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
                                        <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <small class="form-text text-muted">
                                    No categories found. <a href="category_add.php">Add a new category</a> to assign one.
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Available" <?= ($product['status'] === 'Available') ? 'selected' : '' ?>>Available</option>
                                <option value="Out of Stock" <?= ($product['status'] === 'Out of Stock') ? 'selected' : '' ?>>Out of Stock</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_url">Product Image URL</label>
                            <input type="url" id="image_url" name="image_url" class="form-control" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($product['image_path'] ?? '') ?>">
                            
                            <?php if ($product['image_path']): ?>
                                <p style="margin-top: 10px;">
                                    <img src="<?= htmlspecialchars($product['image_path']) ?>" alt="Current Product Image" style="max-width: 100px; height: auto; border: 1px solid #ccc; border-radius: 4px;">
                                    Current Image
                                </p>
                            <?php else: ?>
                                <p style="margin-top: 10px; color: #999;">No image uploaded.</p>
                            <?php endif; ?>

                        </div>

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-success" style="width: 100%;"><i class="fas fa-sync-alt"></i> Update Product</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

<?php
require_once "assets/footer.php";
?>