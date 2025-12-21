<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/db.php";

// Calculate total items in cart for the header
$cart_item_count = 0;
if (!empty($_SESSION['cart'])) {
    $cart_item_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

$product = null;
$error = '';

// 1. Get Product ID from URL and validate it
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    $error = "Invalid product ID specified.";
} else {
    try {
        // 2. Fetch product details from the database
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.description, p.price, p.image_path, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id AND p.status = 'Available'
        ");
        $stmt->execute(['id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $error = "Product not found or is no longer available.";
        }
    } catch (PDOException $e) {
        $error = "Database error: Could not retrieve product details.";
    }
}

$page_title = $product ? htmlspecialchars($product['name']) : 'Product Not Found';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php';
?>

<style>
.product-detail-container {
    padding: 3rem 0;
}
.product-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    background: var(--card-bg);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
}
.product-image img {
    width: 100%;
    height: auto;
    max-height: 500px;
    object-fit: cover;
    border-radius: 8px;
}
.product-info .category {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
}
.product-info h2 {
    font-size: 2.5rem;
    margin: 0 0 1rem 0;
}
.product-info .price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}
.product-info .description {
    line-height: 1.8;
    color: var(--text-secondary);
    margin-bottom: 2rem;
}
.action-buttons { display: flex; gap: 1rem; align-items: center; }
.add-to-cart-form input[type="number"] {
    width: 80px;
    padding: 0.75rem;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 1rem;
}
.btn-add-cart {
    padding: 0.75rem 1.5rem;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}
.btn-add-cart:hover { background-color: #374151; }
.btn-wishlist {
    padding: 0.75rem 1.5rem;
    background-color: transparent;
    color: var(--primary-color);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.btn-wishlist:hover { background-color: var(--bg-light); border-color: var(--primary-color); }
.error-message { text-align: center; padding: 4rem; }
</style>

<div class="container product-detail-container">
    <?php if ($error): ?>
        <div class="error-message">
            <h2><?= htmlspecialchars($error) ?></h2>
            <p><a href="store.php">Return to Shop</a></p>
        </div>
    <?php elseif ($product): ?>
        <div class="product-wrapper">
            <div class="product-image">
                <img src="<?= htmlspecialchars($product['image_path'] ?? 'assets/images/placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>
            <div class="product-info">
                <p class="category"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></p>
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <p class="price">₦<?= number_format($product['price'], 2) ?></p>
                <p class="description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                <div class="action-buttons">
                    <form action="cart_add.php" method="POST" style="display: contents;">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" aria-label="Quantity">
                        <button type="submit" class="btn-add-cart"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                    </form>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="wishlist_add.php?id=<?= $product['id'] ?>" class="btn-wishlist"><i class="far fa-heart"></i> Add to Wishlist</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php'; ?>
