<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect non-logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=wishlist");
    exit;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'db.php';

$user_id = $_SESSION['user_id'];
$wishlist_items = [];
$error = '';
$success = '';

// Handle removing an item from the wishlist
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['product_id'])) {
    $product_id_to_remove = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);
    if ($product_id_to_remove) {
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id_to_remove]);
            header("Location: wishlist.php?status=success&message=Item removed from wishlist.");
            exit;
        } catch (PDOException $e) {
            $error = "Failed to remove item. " . $e->getMessage();
        }
    }
}

// Handle status messages from redirects
if (isset($_GET['status']) && isset($_GET['message'])) {
    if ($_GET['status'] === 'success') {
        $success = $_GET['message'];
    } else {
        $error = $_GET['message'];
    }
}

// Fetch wishlist items
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.image_path
        FROM products p
        JOIN wishlist w ON p.id = w.product_id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() === '42S02') { // Table doesn't exist
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS wishlist (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    product_id INT UNSIGNED NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_product (user_id, product_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            // Table is new, so no items to fetch
            $wishlist_items = [];
        } catch (PDOException $ex) {
            $error = "Failed to initialize wishlist system: " . $ex->getMessage();
        }
    } else {
        $error = "Database Error: " . $e->getMessage();
    }
}

$page_title = 'My Wishlist';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php';
?>

<style>
    .page-title { font-size: 1.8rem; margin-bottom: 1.5rem; text-align: center; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
    .product-card { background-color: var(--card-bg); border-radius: 12px; box-shadow: var(--shadow); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; position: relative; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
    .product-card a.product-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; height: 100%; }
    .product-card .image-wrap { width: 100%; height: 280px; }
    .product-card img { width: 100%; height: 100%; object-fit: cover; }
    .product-info { padding: 1rem; flex-grow: 1; display: flex; flex-direction: column; }
    .product-info h4 { margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 500; }
    .product-info .price { font-size: 1.1rem; font-weight: 600; color: var(--primary-color); margin-top: auto; }
    .btn-remove-wishlist { position: absolute; top: 10px; right: 10px; background-color: rgba(255, 255, 255, 0.8); color: #ef4444; border: none; border-radius: 50%; width: 35px; height: 35px; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none; }
    .btn-remove-wishlist:hover { background-color: #ef4444; color: white; }
    .empty-state { text-align: center; padding: 3rem; background: var(--card-bg); border-radius: 12px; }
</style>

<div class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
    <h2 class="page-title">My Wishlist</h2>

    <?php if ($error): ?> <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> <?php endif; ?>
    <?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>

    <?php if (empty($wishlist_items)): ?>
        <div class="empty-state">
            <p>Your wishlist is empty.</p>
            <a href="store.php" class="btn-primary" style="display: inline-block; width: auto; margin-top: 1rem; padding: 0.6rem 1.5rem;">Discover Products</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="product-card">
                    <a href="wishlist.php?action=remove&product_id=<?= $item['id'] ?>" class="btn-remove-wishlist" title="Remove from Wishlist" onclick="return confirm('Are you sure you want to remove this item from your wishlist?');"><i class="fas fa-times"></i></a>
                    <a href="product_detail.php?id=<?= $item['id'] ?>" class="product-link">
                        <div class="image-wrap"><img src="<?= htmlspecialchars($item['image_path'] ?? 'assets/images/placeholder.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>"></div>
                        <div class="product-info">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <p class="price">₦<?= number_format($item['price'], 2) ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php'; ?>
