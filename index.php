<?php
// Start session to access cart data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path is relative from /index.php to /config/db.php
// We include db.php just in case we later want to pull featured products dynamically.
require_once "config/db.php"; 

// Calculate total items in cart
$cart_item_count = 0;
if (!empty($_SESSION['cart'])) {
    $cart_item_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Fetch featured products (e.g., the 8 most recent)
$featured_products = [];
try {
    $stmt = $pdo->query("
        SELECT id, name, price, image_path 
        FROM products 
        WHERE status = 'Available' 
        ORDER BY created_at DESC 
        LIMIT 8
    ");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently fail or log error; don't block the page from rendering.
}
?>

<?php 
$page_title = 'Welcome to MTP Flex - Online Store';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_header.php'; 
?>

    <style>
        /* --- Hero Section --- */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            padding: 10rem 0;
            text-align: center;
            color: white;
        }
        .hero-section h2 {
            font-size: 3rem;
            margin: 0 0 1rem 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            font-weight: 700;
        }
        .hero-section p { font-size: 1.25rem; margin-bottom: 2rem; }
        .btn-shop {
            padding: 0.8rem 2.5rem;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            font-weight: 600;
            border-radius: 50px;
            transition: background-color 0.3s;
        }
        .btn-shop:hover { background-color: #374151; }

        /* --- Section Styling --- */
        .section { padding: 4rem 0; }
        .section-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 2.5rem;
        }

        /* --- Category Cards --- */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .category-card {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        .category-card img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .category-card:hover img { transform: scale(1.05); }
        .category-card .label {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background-color: rgba(0,0,0,0.6);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
        }

        /* --- Product Grid --- */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .product-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
        .product-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }
        .product-info { padding: 1rem; }
        .product-info h4 { margin: 0 0 0.5rem 0; font-size: 1rem; font-weight: 500; }
        .product-info .price { font-size: 1.1rem; font-weight: 600; color: var(--primary-color); }

    </style>

        <section class="hero-section">
            <h2>Wear Confidence. Flex Your Style.</h2>
            <p>Discover the latest trends in fashion, crafted for you.</p>
            <a href="store.php" class="btn-shop">Shop Now</a>
        </section>

        <section class="section container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="category-grid">
                <a href="store.php?category=1" class="category-card">
                    <img src="https://images.unsplash.com/photo-1503341504253-dff4815485f1?auto=format&fit=crop&w=600&q=80" alt="Men's Fashion">
                    <div class="label">Men</div>
                </a>
                <a href="store.php?category=2" class="category-card">
                    <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=600&q=80" alt="Women's Fashion">
                    <div class="label">Women</div>
                </a>
                <a href="store.php?category=3" class="category-card">
                    <img src="https://images.unsplash.com/photo-1519457431-44ccd64a579b?auto=format&fit=crop&w=600&q=80" alt="Kids' Fashion">
                    <div class="label">Kids</div>
                </a>
                <a href="store.php?category=4" class="category-card">
                    <img src="https://images.unsplash.com/photo-1511556820780-d912e42b4980?auto=format&fit=crop&w=600&q=80" alt="Accessories">
                    <div class="label">Accessories</div>
                </a>
            </div>
        </section>

        <section class="section container">
            <h2 class="section-title">Featured Products</h2>
            <?php if (!empty($featured_products)): ?>
                <div class="product-grid">
                    <?php foreach ($featured_products as $product): ?>
                        <a href="product_detail.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;">
                            <div class="product-card">
                                <img src="<?= htmlspecialchars($product['image_path'] ?? 'assets/images/placeholder.png') ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <div class="product-info">
                                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                                    <p class="price">₦<?= number_format($product['price'], 2) ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center;">No featured products available at the moment.</p>
            <?php endif; ?>
        </section>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'store_footer.php'; ?>